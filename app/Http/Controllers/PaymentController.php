<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Http\Controllers\UpgradeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Razorpay\Api\Api;

class PaymentController extends Controller
{
    /**
     * Build order metadata including VPS plan specs if applicable
     */
    private function buildOrderMetadata(\Illuminate\Http\Request $request, ?Service $service = null): array
    {
        $metadata = [];

        // If an OVH product is explicitly selected, prefer that.
        if ($request->filled('ovh_product_id')) {
            $product = \App\Models\OvhProduct::find($request->input('ovh_product_id'));
            if ($product && $product->is_active) {
                $metadata['ovh_product_id'] = $product->id;
                $metadata['ovh_category']   = $product->category;
                $metadata['cpu_cores']      = $product->cpu_cores;
                $metadata['ram_gb']         = $product->ram_gb;
                $metadata['disk_gb']        = $product->disk_gb;
                $metadata['os']             = $request->input('os', session('ovh_selected_os', 'Ubuntu 22.04'));
                $metadata['datacenter']     = $request->input('datacenter');
                $metadata['domain']         = $request->input('domain');
                $metadata['dns_zone']       = $request->input('dns_zone');
            }
        }

        $searchName = trim($request->tier_name ?? '');

        // If no tier_name but we have a service, try service title as fallback
        if (empty($searchName) && $service) {
            $searchName = trim($service->title ?? '');
        }

        // If tier_name (or service title) matches a VPS plan, include specs in metadata
        if (empty($metadata['ovh_product_id']) && !empty($searchName)) {
            $plan = \App\Models\VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$searchName])
                ->orWhereRaw('LOWER(slug) = LOWER(?)', [$searchName])
                ->first();

            if ($plan) {
                $metadata['vps_plan_id'] = $plan->id;
                $metadata['cpu_cores']   = $plan->cpu_cores;
                $metadata['memory_gb']   = $plan->memory_gb;
                $metadata['disk_gb']     = $plan->disk_gb;
                $metadata['disk_type']   = $plan->disk_type;
                $metadata['os']          = session('vps_selected_os', 'Ubuntu 22.04');
                $metadata['iso']         = 'ubuntu-22.04-live-server-amd64.iso';
            }
        }

        // Store quantity for provisioning
        $metadata['quantity'] = max(1, (int) ($request->quantity ?? 1));

        // Store streaming addon context if present
        if (session()->has('streaming_addon_context')) {
            $metadata['streaming_addon_context'] = session('streaming_addon_context');
        }

        return $metadata;
    }

    private function getCashfreeConfig(): array
    {
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        
        $isProduction = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';
        
        return [
            'enabled' => ($settings['cashfree_enabled'] ?? '0') === '1',
            'client_id' => $settings['cashfree_app_id'] ?? '',
            'client_secret' => $settings['cashfree_secret_key'] ?? '',
            'is_production' => $isProduction,
        ];
    }

    /**
     * Make Cashfree API request via system curl (PHP OpenSSL is too old for Cashfree CDN).
     */
    private function makeCashfreeCurlRequest(string $method, string $url, array $config, ?array $payload = null): array
    {
        $tmpFile = sys_get_temp_dir() . '/cf_' . uniqid() . '.json';
        $headers = [
            'Content-Type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . $config['client_id'],
            'x-client-secret: ' . $config['client_secret'],
        ];

        $headerArgs = [];
        foreach ($headers as $h) {
            $headerArgs[] = '-H ' . escapeshellarg($h);
        }

        $cmd = 'curl -sS -o ' . escapeshellarg($tmpFile) . ' -w "%{http_code}" --max-time 60 --tlsv1.2 ';
        $cmd .= '-X ' . strtoupper($method) . ' ';
        $cmd .= implode(' ', $headerArgs) . ' ';

        if ($payload !== null) {
            $cmd .= '-d ' . escapeshellarg(json_encode($payload)) . ' ';
        }

        $cmd .= escapeshellarg($url) . ' 2>/dev/null';

        $httpCode = (int) shell_exec($cmd);
        $body = file_exists($tmpFile) ? file_get_contents($tmpFile) : '';
        @unlink($tmpFile);

        return ['status' => $httpCode, 'body' => $body];
    }

    private function getRazorpayConfig(): array
    {
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');

        return [
            'enabled' => ($settings['razorpay_enabled'] ?? '0') === '1',
            'key_id' => $settings['razorpay_key_id'] ?? '',
            'key_secret' => $settings['razorpay_key_secret'] ?? '',
        ];
    }

    private function getPaypalConfig(): array
    {
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');

        $isProduction = ($settings['paypal_mode'] ?? 'sandbox') === 'production';

        return [
            'enabled' => ($settings['paypal_enabled'] ?? '0') === '1',
            'client_id' => $settings['paypal_client_id'] ?? '',
            'client_secret' => $settings['paypal_client_secret'] ?? '',
            'is_production' => $isProduction,
            'base_url' => $isProduction
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com',
        ];
    }

    private function getPayuConfig(): array
    {
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');

        $isProduction = ($settings['payu_mode'] ?? 'sandbox') === 'production';

        return [
            'enabled' => ($settings['payu_enabled'] ?? '0') === '1',
            'key' => $settings['payu_key'] ?? '',
            'salt' => $settings['payu_salt'] ?? '',
            'is_production' => $isProduction,
            'base_url' => $isProduction
                ? 'https://secure.payu.in'
                : 'https://test.payu.in',
        ];
    }

    public function createCashfreeOrder(Request $request)
    {
        $request->validate([
            'service_id'     => 'required|exists:services,id',
            'tier_name'      => 'nullable|string',
            'amount'         => 'required|numeric|min:1',
            'billing_months' => 'required|integer|min:1',
            'quantity'       => 'nullable|integer|min:1|max:10',
        ]);

        $config = $this->getCashfreeConfig();

        // Amount is already in INR (sent from frontend as totalWithGst * 83)
        $totalAmountInINR = round(floatval($request->amount), 2);

        if (!$config['enabled']) {
            return response()->json(['error' => 'Cashfree payment gateway is disabled'], 400);
        }
        
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return response()->json(['error' => 'Cashfree not configured'], 400);
        }

        $service = Service::findOrFail($request->service_id);
        $user = Auth::user();

        // Calculate GST (18%) - amount includes GST so we calculate backwards
        // total = subtotal + gst (18% of subtotal)
        // total = subtotal * 1.18
        // subtotal = total / 1.18
        $gstRate = 0.18;
        $subtotalInINR = round($totalAmountInINR / (1 + $gstRate), 2);
        $gstAmountInINR = round($totalAmountInINR - $subtotalInINR, 2);

        // Create order in database with GST breakdown
        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'service_name' => $service->title,
            'tier_name' => $request->tier_name,
            'billing_months' => $request->billing_months ?? 1,
            'subtotal' => $subtotalInINR,
            'gst_amount' => $gstAmountInINR,
            'gst_rate' => $gstRate,
            'amount' => $totalAmountInINR,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'cashfree',
            'metadata' => $this->buildOrderMetadata($request, $service),
        ]);

        try {
            $baseUrl = $config['is_production'] 
                ? 'https://api.cashfree.com/pg/orders' 
                : 'https://sandbox.cashfree.com/pg/orders';

            $payload = [
                'order_amount' => $totalAmountInINR,
                'order_currency' => 'INR',
                'order_id' => (string) $order->order_number,
                'customer_details' => [
                    'customer_id' => 'CUST_' . $user->id,
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->phone ?? '9999999999',
                ],
                'order_meta' => [
                    'return_url' => route('payment.cashfree.callback') . '?order_id={order_id}&cf_order_id={cf_order_id}',
                    'notify_url' => route('payment.cashfree.webhook'),
                ],
                'order_note' => 'Payment for ' . $service->title,
            ];

            // Use system curl (PHP OpenSSL is too old for Cashfree CDN)
            $response = $this->makeCashfreeCurlRequest('POST', $baseUrl, $config, $payload);
            $statusCode = $response['status'];
            $responseBody = $response['body'];

            Log::info('Cashfree API response', [
                'status' => $statusCode,
                'body' => $responseBody,
            ]);

            if ($statusCode < 200 || $statusCode >= 300) {
                Log::error('Cashfree API error: HTTP ' . $statusCode . ' - ' . $responseBody);
                return response()->json([
                    'error' => 'Cashfree API error (HTTP ' . $statusCode . '): ' . substr($responseBody, 0, 500),
                ], 400);
            }

            $cfOrder = json_decode($responseBody, true);

            if (empty($cfOrder['payment_session_id'])) {
                Log::error('Cashfree no payment_session_id', ['response' => $cfOrder]);
                return response()->json([
                    'error' => 'Cashfree did not return a payment session. Response: ' . substr($responseBody, 0, 500),
                ], 400);
            }

            // Update order with Cashfree order ID
            $order->update([
                'payment_id' => $cfOrder['cf_order_id'] ?? $cfOrder['order_id'],
            ]);

            return response()->json([
                'payment_session_id' => $cfOrder['payment_session_id'],
                'order_id' => $order->id,
                'cf_order_id' => $cfOrder['cf_order_id'] ?? $cfOrder['order_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Cashfree order creation failed: ' . $e->getMessage());
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create payment order: ' . $e->getMessage()], 500);
        }
    }

    public function cashfreeCallback(Request $request)
    {
        $orderId   = $request->get('order_id');
        $cfOrderId = $request->get('cf_order_id');

        // Cashfree sometimes sends literal "{cf_order_id}" if template not replaced
        if ($cfOrderId === '{cf_order_id}' || empty($cfOrderId)) {
            $cfOrderId = null;
        }

        // order_id is the order_number (e.g. ORD-XXXX), find by order_number
        $order = Order::where('order_number', $orderId)->first();

        // Fallback: try numeric ID
        if (!$order && is_numeric($orderId)) {
            $order = Order::find($orderId);
        }

        if (!$order) {
            Log::error('Cashfree callback: order not found for order_id: ' . $orderId);
            return redirect()->route('client.orders')->with('error', 'Order not found.');
        }

        // Check if payment_id exists or use cf_order_id from callback
        // payment_id stores the Cashfree cf_order_id (numeric)
        $cashfreeOrderId = $cfOrderId ?? $order->payment_id;

        // If still empty, try using order_number directly (Cashfree order_id = our order_number)
        if (empty($cashfreeOrderId)) {
            $cashfreeOrderId = $orderId; // Use our order_number as Cashfree order_id
        }

        if (empty($cashfreeOrderId)) {
            Log::error('Cashfree callback: no cashfree order ID available for order: ' . $orderId);
            return redirect()->route('client.orders')->with('error', 'Payment order not initialized. Please try again or contact support.');
        }

        $config = $this->getCashfreeConfig();

        try {
            $baseUrl = $config['is_production']
                ? 'https://api.cashfree.com/pg/orders/' . $cashfreeOrderId
                : 'https://sandbox.cashfree.com/pg/orders/' . $cashfreeOrderId;

            $response = $this->makeCashfreeCurlRequest('GET', $baseUrl, $config);

            if ($response['status'] < 200 || $response['status'] >= 300) {
                Log::error('Cashfree callback API error: HTTP ' . $response['status'] . ' - ' . $response['body']);
                throw new \Exception('Failed to verify order. HTTP ' . $response['status']);
            }

            $cfOrder = json_decode($response['body'], true);

            if ($cfOrder['order_status'] === 'PAID') {
                $order->markAsPaid(
                    'cashfree',
                    $cfOrder['cf_order_id'],
                    $cfOrder['order_id'],
                    $cfOrder
                );

                // Process upgrade if this is an upgrade order
                if (session('upgrade_hosting_id')) {
                    $upgradeController = new UpgradeController();
                    $upgradeController->processUpgrade($order);
                }

                return redirect()->route('client.orders')->with('success', 'Payment successful!');
            } else {
                $order->update(['status' => 'failed']);
                return redirect()->route('client.orders')->with('error', 'Payment failed. Status: ' . ($cfOrder['order_status'] ?? 'unknown'));
            }

        } catch (\Exception $e) {
            Log::error('Cashfree callback verification failed for order ' . $orderId . ': ' . $e->getMessage(), [
                'order_id' => $orderId,
                'cf_order_id' => $cfOrderId,
                'payment_id' => $order->payment_id,
                'exception' => $e,
            ]);

            // Check if it's a 404 - order not found error
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, '404') !== false || strpos($errorMessage, 'order_not_found') !== false) {
                // Order not found on Cashfree - may have expired or been cleared
                // Mark order as failed and show user-friendly message
                $order->update(['status' => 'failed', 'notes' => 'Payment order expired or not found on payment gateway']);
                return redirect()->route('client.orders')->with('error', 'Payment session expired. Please try placing the order again.');
            }

            return redirect()->route('client.orders')->with('error', 'Payment verification failed. Please contact support if amount was deducted.');
        }
    }

    public function cashfreeWebhook(Request $request)
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? $request->all();
        
        Log::info('Cashfree webhook received', ['payload' => $payload]);

        $config = $this->getCashfreeConfig();

        // Verify webhook signature (works for both test and live)
        $timestamp = $request->header('x-webhook-timestamp');
        $signature = $request->header('x-webhook-signature');
        
        if ($timestamp && $signature && !empty($config['client_secret'])) {
            $signedPayload = $timestamp . $rawBody;
            $expectedSignature = base64_encode(hash_hmac('sha256', $signedPayload, $config['client_secret'], true));
            
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Cashfree webhook: invalid signature');
                return response()->json(['status' => 'invalid_signature'], 401);
            }
        }

        // Handle new Cashfree webhook format (v2023-08-01)
        $eventType   = $payload['type'] ?? $payload['event'] ?? null;
        $orderData   = $payload['data']['order'] ?? $payload['data'] ?? $payload;
        $paymentData = $payload['data']['payment'] ?? [];
        $orderId     = $orderData['order_id'] ?? $payload['orderId'] ?? null;
        $cfOrderId   = $orderData['cf_order_id'] ?? $payload['cf_order_id'] ?? null;
        $orderStatus = strtoupper($orderData['order_status'] ?? $payload['orderStatus'] ?? '');
        $paymentStatus = strtoupper($paymentData['payment_status'] ?? '');

        // Support both event formats
        $isPaid = in_array($eventType, [
                'PAYMENT_SUCCESS',
                'PAYMENT_SUCCESS_WEBHOOK',  // Cashfree actual webhook type
                'ORDER_PAID',
                'payment.captured',
            ])
            || $orderStatus === 'PAID'
            || $paymentStatus === 'SUCCESS';

        if (!$isPaid) {
            Log::info('Cashfree webhook: not a paid event', ['type' => $eventType, 'status' => $orderStatus]);
            return response()->json(['status' => 'ignored']);
        }

        // Find order by order_number (ORD-XXXX format) or payment_id
        $order = Order::where('order_number', $orderId)->first()
            ?? Order::where('payment_id', $cfOrderId)->first()
            ?? Order::where('payment_id', $orderId)->first()
            ?? Order::where('order_number', $cfOrderId)->first();

        if (!$order) {
            Log::error('Cashfree webhook: order not found', ['order_id' => $orderId, 'cf_order_id' => $cfOrderId]);
            return response()->json(['status' => 'order_not_found'], 404);
        }

        if ($order->isPaid()) {
            Log::info('Cashfree webhook: order already paid', ['order_id' => $order->id]);
            return response()->json(['status' => 'already_processed']);
        }

        // Mark as paid and trigger VM provisioning
        $order->markAsPaid(
            'cashfree',
            $cfOrderId ?? $orderId,
            $orderId,
            $payload
        );

        Log::info('Cashfree webhook: order marked paid, VM provisioning triggered', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return response()->json(['status' => 'success']);
    }

    public function createRazorpayOrder(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tier_name' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'billing_months' => 'required|integer|min:1',
        ]);

        $config = $this->getRazorpayConfig();
        
        if (!$config['enabled']) {
            return response()->json(['error' => 'Razorpay payment gateway is disabled'], 400);
        }
        
        if (empty($config['key_id']) || empty($config['key_secret'])) {
            return response()->json(['error' => 'Razorpay not configured'], 400);
        }

        $service = Service::findOrFail($request->service_id);
        $user = Auth::user();

        // Amount already includes GST from frontend
        $totalAmount = floatval($request->amount);

        // Calculate GST (18%) - amount includes GST so we calculate backwards
        $gstRate = 0.18;
        $subtotal = round($totalAmount / (1 + $gstRate), 2);
        $gstAmount = round($totalAmount - $subtotal, 2);

        // Convert amount to paise (Razorpay uses smallest currency unit)
        $amountInPaise = $totalAmount * 100;

        // Create order in database with GST breakdown
        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'service_name' => $service->title,
            'tier_name' => $request->tier_name,
            'billing_months' => $request->billing_months ?? 1,
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'gst_rate' => $gstRate,
            'amount' => $totalAmount,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'razorpay',
            'metadata' => $this->buildOrderMetadata($request, $service),
        ]);

        try {
            $api = new Api($config['key_id'], $config['key_secret']);

            $razorpayOrder = $api->order->create([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $order->order_number,
                'notes' => [
                    'service_id' => $service->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                ],
            ]);

            $order->update(['payment_id' => $razorpayOrder['id']]);

            return response()->json([
                'key_id' => $config['key_id'],
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'order_id' => $razorpayOrder['id'],
                'name' => config('app.name'),
                'description' => $service->title,
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact' => $user->phone ?? '',
                ],
                'notes' => [
                    'internal_order_id' => $order->id,
                ],
                'callback_url' => route('payment.razorpay.callback'),
            ]);

        } catch (\Exception $e) {
            Log::error('Razorpay order creation failed: ' . $e->getMessage());
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create payment order'], 500);
        }
    }

    public function razorpayCallback(Request $request)
    {
        $config = $this->getRazorpayConfig();
        
        try {
            $api = new Api($config['key_id'], $config['key_secret']);

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $order = Order::where('payment_id', $request->razorpay_order_id)->firstOrFail();
            // Process upgrade if this is an upgrade order
            if (session('upgrade_hosting_id')) {
                $upgradeController = new UpgradeController();
                $upgradeController->processUpgrade($order);
            }

            
            $order->markAsPaid(
                'razorpay',
                $request->razorpay_payment_id,
                $request->razorpay_order_id,
                $request->all()
            );

            return redirect()->route('client.orders')->with('success', 'Payment successful!');

        } catch (\Exception $e) {
            Log::error('Razorpay verification failed: ' . $e->getMessage());
            return redirect()->route('client.orders')->with('error', 'Payment verification failed.');
        }
    }

    public function razorpayWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('Razorpay webhook received', $payload);

        if (isset($payload['event']) && $payload['event'] === 'payment.captured') {
            $notes = $payload['payload']['payment']['entity']['notes'] ?? [];
            $internalOrderId = $notes['internal_order_id'] ?? null;

            if ($internalOrderId) {
                $order = Order::find($internalOrderId);

                if ($order && !$order->isPaid()) {
                    $order->markAsPaid(
                        'razorpay',
                        $payload['payload']['payment']['entity']['id'],
                        $payload['payload']['payment']['entity']['order_id'],
                        $payload
                    );

                    // Process upgrade if this is an upgrade order
                    // Note: In webhook context, session won't be available, so we check order notes
                    if ($order->notes && str_contains($order->notes, 'Upgrade from')) {
                        $upgradeController = new UpgradeController();
                        $upgradeController->processUpgrade($order);
                    }
                }
            }
        }

        return response()->json(['status' => 'received']);
    }

    // PayPal Methods
    public function createPaypalOrder(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tier_name' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'billing_months' => 'required|integer|min:1',
        ]);

        $config = $this->getPaypalConfig();

        if (!$config['enabled']) {
            return response()->json(['error' => 'PayPal payment gateway is disabled'], 400);
        }

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return response()->json(['error' => 'PayPal not configured'], 400);
        }

        $service = Service::findOrFail($request->service_id);
        $user = Auth::user();

        // Amount already includes GST from frontend
        $totalAmount = floatval($request->amount);

        // Calculate GST (18%) - amount includes GST so we calculate backwards
        $gstRate = 0.18;
        $subtotal = round($totalAmount / (1 + $gstRate), 2);
        $gstAmount = round($totalAmount - $subtotal, 2);

        // Create order in database with GST breakdown
        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'service_name' => $service->title,
            'tier_name' => $request->tier_name,
            'billing_months' => $request->billing_months ?? 1,
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'gst_rate' => $gstRate,
            'amount' => $totalAmount,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_gateway' => 'paypal',
            'metadata' => $this->buildOrderMetadata($request, $service),
        ]);

        try {
            // Get PayPal access token
            $tokenResponse = Http::withBasicAuth($config['client_id'], $config['client_secret'])
                ->asForm()
                ->post($config['base_url'] . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Create PayPal order
            $paypalOrder = Http::withToken($accessToken)
                ->post($config['base_url'] . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => (string) $order->order_number,
                            'description' => 'Payment for ' . $service->title,
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format($totalAmount, 2, '.', ''),
                            ],
                        ]
                    ],
                    'application_context' => [
                        'return_url' => route('payment.paypal.callback') . '?order_id=' . $order->id,
                        'cancel_url' => route('client.orders'),
                        'brand_name' => config('app.name'),
                        'landing_page' => 'BILLING',
                        'user_action' => 'PAY_NOW',
                    ]
                ]);

            if (!$paypalOrder->successful()) {
                throw new \Exception('Failed to create PayPal order: ' . $paypalOrder->body());
            }

            $paypalData = $paypalOrder->json();

            $order->update(['payment_id' => $paypalData['id']]);

            // Find approval URL
            $approvalUrl = null;
            foreach ($paypalData['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            return response()->json([
                'order_id' => $order->id,
                'paypal_order_id' => $paypalData['id'],
                'approval_url' => $approvalUrl,
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal order creation failed: ' . $e->getMessage());
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create PayPal order: ' . $e->getMessage()], 500);
        }
    }

    public function paypalCallback(Request $request)
    {
        $orderId = $request->get('order_id');
        $token = $request->get('token'); // PayPal order ID

        $order = Order::findOrFail($orderId);
        $config = $this->getPaypalConfig();

        try {
            // Get PayPal access token
            $tokenResponse = Http::withBasicAuth($config['client_id'], $config['client_secret'])
                ->asForm()
                ->post($config['base_url'] . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Capture the payment
            $captureResponse = Http::withToken($accessToken)
                ->post($config['base_url'] . '/v2/checkout/orders/' . $token . '/capture');

            if (!$captureResponse->successful()) {
                throw new \Exception('Failed to capture PayPal payment');
            }

            $captureData = $captureResponse->json();

            if ($captureData['status'] === 'COMPLETED') {
                $order->markAsPaid(
                    'paypal',
                    $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? $token,
                    $token,
                    $captureData
                );

                // Process upgrade if this is an upgrade order
                if (session('upgrade_hosting_id')) {
                    $upgradeController = new UpgradeController();
                    $upgradeController->processUpgrade($order);
                }

                return redirect()->route('client.orders')->with('success', 'Payment successful!');
            } else {
                $order->update(['status' => 'failed']);
                return redirect()->route('client.orders')->with('error', 'Payment failed. Status: ' . $captureData['status']);
            }

        } catch (\Exception $e) {
            Log::error('PayPal callback verification failed: ' . $e->getMessage());
            return redirect()->route('client.orders')->with('error', 'Payment verification failed.');
        }
    }

    /**
     * PayPal Webhook Handler
     * Events: PAYMENT.CAPTURE.COMPLETED, CHECKOUT.ORDER.APPROVED
     */
    public function paypalWebhook(Request $request)
    {
        $rawBody  = $request->getContent();
        $payload  = json_decode($rawBody, true) ?? [];
        $eventType = $payload['event_type'] ?? '';

        Log::info('PayPal webhook received', ['event_type' => $eventType, 'payload' => $payload]);

        // Handle payment completed events
        $isPaid = in_array($eventType, [
            'PAYMENT.CAPTURE.COMPLETED',
            'CHECKOUT.ORDER.APPROVED',
            'PAYMENT.SALE.COMPLETED',
        ]);

        if (!$isPaid) {
            return response()->json(['status' => 'ignored']);
        }

        // Extract order reference from PayPal payload
        $referenceId = $payload['resource']['purchase_units'][0]['reference_id']
            ?? $payload['resource']['invoice_id']
            ?? null;

        $paypalOrderId = $payload['resource']['id']
            ?? $payload['resource']['supplementary_data']['related_ids']['order_id']
            ?? null;

        // Find our order
        $order = null;
        if ($referenceId) {
            $order = Order::where('order_number', $referenceId)->first()
                ?? Order::where('payment_id', $referenceId)->first();
        }
        if (!$order && $paypalOrderId) {
            $order = Order::where('payment_id', $paypalOrderId)->first();
        }

        if (!$order) {
            Log::error('PayPal webhook: order not found', ['reference_id' => $referenceId, 'paypal_order_id' => $paypalOrderId]);
            return response()->json(['status' => 'order_not_found'], 404);
        }

        if ($order->isPaid()) {
            return response()->json(['status' => 'already_processed']);
        }

        $captureId = $payload['resource']['id'] ?? $paypalOrderId;

        $order->markAsPaid('paypal', $captureId, $paypalOrderId, $payload);

        Log::info('PayPal webhook: order marked paid', ['order_id' => $order->id, 'order_number' => $order->order_number]);

        return response()->json(['status' => 'success']);
    }

    // PayU Methods
    public function createPayuOrder(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tier_name' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'billing_months' => 'required|integer|min:1',
        ]);

        $config = $this->getPayuConfig();

        if (!$config['enabled']) {
            return response()->json(['error' => 'PayU payment gateway is disabled'], 400);
        }

        if (empty($config['key']) || empty($config['salt'])) {
            return response()->json(['error' => 'PayU not configured'], 400);
        }

        $service = Service::findOrFail($request->service_id);
        $user = Auth::user();

        // Amount already includes GST from frontend
        $totalAmount = floatval($request->amount);

        // Calculate GST (18%) - amount includes GST so we calculate backwards
        $gstRate = 0.18;
        $subtotal = round($totalAmount / (1 + $gstRate), 2);
        $gstAmount = round($totalAmount - $subtotal, 2);

        // Convert to INR
        $totalAmountInINR = $totalAmount * 83;

        // Create order in database with GST breakdown
        $order = Order::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'service_name' => $service->title,
            'tier_name' => $request->tier_name,
            'billing_months' => $request->billing_months ?? 1,
            'subtotal' => $subtotal * 83,
            'gst_amount' => $gstAmount * 83,
            'gst_rate' => $gstRate,
            'amount' => $totalAmountInINR,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'payu',
            'metadata' => $this->buildOrderMetadata($request, $service),
        ]);

        try {
            $txnid = (string) $order->order_number;
            $productinfo = $service->title;
            $firstname = $user->name;
            $email = $user->email;
            $phone = $user->phone ?? '9999999999';
            $amount = number_format($totalAmountInINR, 2, '.', '');

            // Generate hash
            $hashString = $config['key'] . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|||||||||||' . $config['salt'];
            $hash = strtolower(hash('sha512', $hashString));

            return response()->json([
                'key' => $config['key'],
                'txnid' => $txnid,
                'amount' => $amount,
                'productinfo' => $productinfo,
                'firstname' => $firstname,
                'email' => $email,
                'phone' => $phone,
                'surl' => route('payment.payu.callback'),
                'furl' => route('payment.payu.callback'),
                'hash' => $hash,
                'base_url' => $config['base_url'],
                'order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            Log::error('PayU order creation failed: ' . $e->getMessage());
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create PayU order: ' . $e->getMessage()], 500);
        }
    }

    public function payuCallback(Request $request)
    {
        $config = $this->getPayuConfig();

        $status = $request->get('status');
        $txnid = $request->get('txnid');
        $payuMoneyId = $request->get('payuMoneyId');
        $hash = $request->get('hash');

        try {
            // Verify hash
            $reverseHashString = $config['salt'] . '|' . $status . '|||||||||||' . $request->get('email') . '|' . $request->get('firstname') . '|' . $request->get('productinfo') . '|' . $request->get('amount') . '|' . $txnid . '|' . $config['key'];
            $reverseHash = strtolower(hash('sha512', $reverseHashString));

            if ($hash !== $reverseHash) {
                throw new \Exception('PayU hash verification failed');
            }

            $order = Order::where('order_number', $txnid)->firstOrFail();

            if ($status === 'success') {
                $order->markAsPaid(
                    'payu',
                    $payuMoneyId,
                    $txnid,
                    $request->all()
                );

                // Process upgrade if this is an upgrade order
                if (session('upgrade_hosting_id')) {
                    $upgradeController = new UpgradeController();
                    $upgradeController->processUpgrade($order);
                }

                return redirect()->route('client.orders')->with('success', 'Payment successful!');
            } else {
                $order->update(['status' => 'failed']);
                return redirect()->route('client.orders')->with('error', 'Payment failed. Status: ' . $status);
            }

        } catch (\Exception $e) {
            Log::error('PayU callback verification failed: ' . $e->getMessage());
            return redirect()->route('client.orders')->with('error', 'Payment verification failed.');
        }
    }
}
