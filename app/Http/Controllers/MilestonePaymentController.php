<?php

namespace App\Http\Controllers;

use App\Models\AgreementMilestone;
use App\Models\AgreementHistory;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class MilestonePaymentController extends Controller
{
    public function createPayment(AgreementMilestone $milestone)
    {
        // Ensure user has access
        if ($milestone->agreement->client_id !== Auth::id()) {
            abort(403);
        }

        // Check if already paid
        if ($milestone->status === 'paid') {
            return back()->with('error', 'This milestone has already been paid.');
        }

        $agreement = $milestone->agreement;
        $amount = $milestone->payment_amount;

        // Get payment gateway configs
        $settings = Setting::where('group', 'General')->pluck('value', 'key');

        // Check Razorpay config
        $razorpayKeyId = $settings['razorpay_key_id'] ?? '';
        $razorpayKeySecret = $settings['razorpay_key_secret'] ?? '';
        $razorpayEnabled = ($settings['razorpay_enabled'] ?? '0') === '1';

        // Check Cashfree config
        $cashfreeClientId = $settings['cashfree_app_id'] ?? '';
        $cashfreeClientSecret = $settings['cashfree_secret_key'] ?? '';
        $cashfreeEnabled = ($settings['cashfree_enabled'] ?? '0') === '1';
        $cashfreeIsProduction = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';

        // Check PayPal config
        $paypalClientId = $settings['paypal_client_id'] ?? '';
        $paypalClientSecret = $settings['paypal_client_secret'] ?? '';
        $paypalEnabled = ($settings['paypal_enabled'] ?? '0') === '1';
        $paypalIsProduction = ($settings['paypal_mode'] ?? 'sandbox') === 'production';

        // Check PayU config
        $payuKey = $settings['payu_key'] ?? '';
        $payuSalt = $settings['payu_salt'] ?? '';
        $payuEnabled = ($settings['payu_enabled'] ?? '0') === '1';
        $payuIsProduction = ($settings['payu_mode'] ?? 'sandbox') === 'production';

        // Convert amount to INR if needed (assuming USD -> INR conversion rate ~83)
        $currency = $agreement->currency ?? 'USD';
        $amountInINR = $currency === 'USD' ? $amount * 83 : $amount;
        $amountInPaise = $amountInINR * 100;

        $user = Auth::user();

        // Try Razorpay first if available
        if ($razorpayEnabled && !empty($razorpayKeyId) && !empty($razorpayKeySecret)) {
            return $this->createRazorpayPayment($milestone, $agreement, $amountInINR, $amountInPaise, $user, $razorpayKeyId, $razorpayKeySecret);
        }

        // Fallback to Cashfree if Razorpay not available
        if ($cashfreeEnabled && !empty($cashfreeClientId) && !empty($cashfreeClientSecret)) {
            \Illuminate\Support\Facades\Log::info('Creating Cashfree payment for milestone: ' . $milestone->id);
            return $this->createCashfreePayment($milestone, $agreement, $amountInINR, $user, $cashfreeClientId, $cashfreeClientSecret, $cashfreeIsProduction);
        }

        // Fallback to PayPal if Cashfree not available
        if ($paypalEnabled && !empty($paypalClientId) && !empty($paypalClientSecret)) {
            \Illuminate\Support\Facades\Log::info('Creating PayPal payment for milestone: ' . $milestone->id);
            return $this->createPaypalPayment($milestone, $agreement, $amount, $user, $paypalClientId, $paypalClientSecret, $paypalIsProduction, $currency);
        }

        // Fallback to PayU if PayPal not available
        if ($payuEnabled && !empty($payuKey) && !empty($payuSalt)) {
            \Illuminate\Support\Facades\Log::info('Creating PayU payment for milestone: ' . $milestone->id);
            return $this->createPayuPayment($milestone, $agreement, $amountInINR, $user, $payuKey, $payuSalt, $payuIsProduction);
        }

        \Illuminate\Support\Facades\Log::warning('No payment gateway available. Razorpay enabled: ' . ($razorpayEnabled ? 'yes' : 'no') . ', Cashfree enabled: ' . ($cashfreeEnabled ? 'yes' : 'no') . ', PayPal enabled: ' . ($paypalEnabled ? 'yes' : 'no') . ', PayU enabled: ' . ($payuEnabled ? 'yes' : 'no'));

        // No payment gateway available
        return redirect()->route('client.dashboard', ['tab' => 'agreements'])
            ->with('error', 'Online payment is temporarily unavailable. Please use Upload Proof option.');
    }

    private function createRazorpayPayment($milestone, $agreement, $amountInINR, $amountInPaise, $user, $keyId, $keySecret)
    {
        // Create order for the payment
        $order = \App\Models\Order::create([
            'user_id' => Auth::id(),
            'service_id' => null,
            'service_name' => "Milestone: {$milestone->phase_name}",
            'amount' => $amountInINR,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'razorpay',
            'notes' => "Milestone Payment: {$milestone->phase_name} - {$agreement->project_name}",
            'metadata' => [
                'milestone_id' => $milestone->id,
                'agreement_id' => $agreement->id,
                'type' => 'milestone_payment',
            ],
        ]);

        try {
            $api = new Api($keyId, $keySecret);

            $razorpayOrder = $api->order->create([
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'receipt' => $order->order_number,
                'notes' => [
                    'order_id' => $order->id,
                    'milestone_id' => $milestone->id,
                ],
            ]);

            $order->update(['payment_id' => $razorpayOrder['id']]);

            return view('payment.razorpay-milestone', [
                'key_id' => $keyId,
                'amount' => $amountInPaise,
                'currency' => 'INR',
                'order_id' => $razorpayOrder['id'],
                'description' => "Milestone: {$milestone->phase_name}",
                'prefill_name' => $user->name,
                'prefill_email' => $user->email,
                'prefill_contact' => $user->phone ?? '',
                'callback_url' => route('milestone.razorpay.callback'),
                'internal_order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return back()->with('error', 'Failed to create Razorpay payment. Please try again.');
        }
    }

    /**
     * Make Cashfree API request via system curl (PHP OpenSSL is too old for Cashfree CDN).
     */
    private function makeCashfreeCurlRequest(string $method, string $url, string $clientId, string $clientSecret, ?array $payload = null): array
    {
        $tmpFile = sys_get_temp_dir() . '/cf_' . uniqid() . '.json';
        $headers = [
            'Content-Type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . $clientId,
            'x-client-secret: ' . $clientSecret,
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

    private function createCashfreePayment($milestone, $agreement, $amountInINR, $user, $clientId, $clientSecret, $isProduction)
    {
        \Illuminate\Support\Facades\Log::info('Starting Cashfree payment creation. Amount: ' . $amountInINR . ', Production: ' . ($isProduction ? 'yes' : 'no'));

        // Check Cashfree max limit (₹50,000)
        if ($amountInINR > 50000) {
            \Illuminate\Support\Facades\Log::warning('Cashfree amount exceeds limit: ' . $amountInINR);
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Order amount exceeds Cashfree limit of ₹50,000. Please contact Believoo support team at help@believoo.com for assistance with large payments.');
        }

        // Create order for the payment
        $order = \App\Models\Order::create([
            'user_id' => Auth::id(),
            'service_id' => null,
            'service_name' => "Milestone: {$milestone->phase_name}",
            'amount' => $amountInINR,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'cashfree',
            'notes' => "Milestone Payment: {$milestone->phase_name} - {$agreement->project_name}",
            'metadata' => [
                'milestone_id' => $milestone->id,
                'agreement_id' => $agreement->id,
                'type' => 'milestone_payment',
            ],
        ]);

        try {
            $baseUrl = $isProduction
                ? 'https://api.cashfree.com/pg/orders'
                : 'https://sandbox.cashfree.com/pg/orders';

            $payload = [
                'order_amount' => $amountInINR,
                'order_currency' => 'INR',
                'order_id' => (string) $order->order_number,
                'customer_details' => [
                    'customer_id' => 'CUST_' . $user->id,
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->phone ?? '9999999999',
                ],
                'order_meta' => [
                    'return_url' => route('milestone.cashfree.callback') . '?order_id={order_id}&cf_order_id={cf_order_id}',
                    'notify_url' => route('payment.cashfree.webhook'),
                ],
                'order_note' => "Milestone: {$milestone->phase_name}",
            ];

            \Illuminate\Support\Facades\Log::info('Calling Cashfree API: ' . $baseUrl);

            $response = $this->makeCashfreeCurlRequest('POST', $baseUrl, $clientId, $clientSecret, $payload);
            $statusCode = $response['status'];
            $responseBody = $response['body'];

            \Illuminate\Support\Facades\Log::info('Cashfree API response status: ' . $statusCode);

            if ($statusCode < 200 || $statusCode >= 300) {
                \Illuminate\Support\Facades\Log::error('Cashfree API error: ' . $responseBody);
                throw new \Exception('Cashfree API error: ' . $responseBody);
            }

            $cfOrder = json_decode($responseBody, true);

            \Illuminate\Support\Facades\Log::info('Cashfree order created: ' . json_encode($cfOrder));

            if (empty($cfOrder['payment_session_id'])) {
                throw new \Exception('No payment_session_id in response');
            }

            $order->update(['payment_id' => $cfOrder['cf_order_id'] ?? $cfOrder['order_id']]);

            \Illuminate\Support\Facades\Log::info('Returning cashfree-milestone view with session_id: ' . $cfOrder['payment_session_id']);

            return view('payment.cashfree-milestone', [
                'payment_session_id' => $cfOrder['payment_session_id'],
                'order_id' => $order->id,
                'cf_order_id' => $cfOrder['cf_order_id'] ?? $cfOrder['order_id'],
                'environment' => $isProduction ? 'production' : 'sandbox',
            ]);

        } catch (\Exception $e) {
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            \Illuminate\Support\Facades\Log::error('Cashfree payment creation failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Failed to create Cashfree payment: ' . $e->getMessage());
        }
    }

    private function createPaypalPayment($milestone, $agreement, $amount, $user, $clientId, $clientSecret, $isProduction, $currency)
    {
        \Illuminate\Support\Facades\Log::info('Starting PayPal payment creation for milestone: ' . $milestone->id);

        // Create order for the payment
        $order = \App\Models\Order::create([
            'user_id' => Auth::id(),
            'service_id' => null,
            'service_name' => "Milestone: {$milestone->phase_name}",
            'amount' => $amount,
            'currency' => $currency ?? 'USD',
            'status' => 'pending',
            'payment_gateway' => 'paypal',
            'notes' => "Milestone Payment: {$milestone->phase_name} - {$agreement->project_name}",
            'metadata' => [
                'milestone_id' => $milestone->id,
                'agreement_id' => $agreement->id,
                'type' => 'milestone_payment',
            ],
        ]);

        try {
            $baseUrl = $isProduction
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // Get PayPal access token
            $tokenResponse = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Create PayPal order
            $paypalOrder = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post($baseUrl . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => (string) $order->order_number,
                            'description' => "Milestone: {$milestone->phase_name}",
                            'amount' => [
                                'currency_code' => $currency ?? 'USD',
                                'value' => number_format($amount, 2, '.', ''),
                            ],
                        ]
                    ],
                    'application_context' => [
                        'return_url' => route('milestone.paypal.callback') . '?order_id=' . $order->id,
                        'cancel_url' => route('client.dashboard', ['tab' => 'agreements']),
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

            // Redirect to PayPal
            return redirect()->away($approvalUrl);

        } catch (\Exception $e) {
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            \Illuminate\Support\Facades\Log::error('PayPal payment creation failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Failed to create PayPal payment: ' . $e->getMessage());
        }
    }

    private function createPayuPayment($milestone, $agreement, $amountInINR, $user, $key, $salt, $isProduction)
    {
        \Illuminate\Support\Facades\Log::info('Starting PayU payment creation for milestone: ' . $milestone->id);

        // Create order for the payment
        $order = \App\Models\Order::create([
            'user_id' => Auth::id(),
            'service_id' => null,
            'service_name' => "Milestone: {$milestone->phase_name}",
            'amount' => $amountInINR,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'payu',
            'notes' => "Milestone Payment: {$milestone->phase_name} - {$agreement->project_name}",
            'metadata' => [
                'milestone_id' => $milestone->id,
                'agreement_id' => $agreement->id,
                'type' => 'milestone_payment',
            ],
        ]);

        try {
            $baseUrl = $isProduction ? 'https://secure.payu.in' : 'https://test.payu.in';
            $txnid = (string) $order->order_number;
            $productinfo = "Milestone: {$milestone->phase_name}";
            $firstname = $user->name;
            $email = $user->email;
            $phone = $user->phone ?? '9999999999';
            $amount = number_format($amountInINR, 2, '.', '');

            // Generate hash
            $hashString = $key . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|||||||||||' . $salt;
            $hash = strtolower(hash('sha512', $hashString));

            return view('payment.payu-milestone', [
                'base_url' => $baseUrl,
                'key' => $key,
                'txnid' => $txnid,
                'amount' => $amount,
                'productinfo' => $productinfo,
                'firstname' => $firstname,
                'email' => $email,
                'phone' => $phone,
                'surl' => route('milestone.payu.callback'),
                'furl' => route('milestone.payu.callback'),
                'hash' => $hash,
                'order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            \Illuminate\Support\Facades\Log::error('PayU payment creation failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Failed to create PayU payment: ' . $e->getMessage());
        }
    }

    public function razorpayCallback(Request $request)
    {
        $settings = Setting::where('group', 'General')->pluck('value', 'key');
        $razorpayKeyId = $settings['razorpay_key_id'] ?? '';
        $razorpayKeySecret = $settings['razorpay_key_secret'] ?? '';

        try {
            $api = new Api($razorpayKeyId, $razorpayKeySecret);

            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $order = \App\Models\Order::findOrFail($request->internal_order_id);

            if (($order->metadata['type'] ?? null) !== 'milestone_payment') {
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Invalid payment order.');
            }

            // Mark order as paid
            $order->markAsPaid(
                'razorpay',
                $request->razorpay_payment_id,
                $request->razorpay_order_id,
                $request->all()
            );

            $milestoneId = $order->metadata['milestone_id'] ?? null;
            $milestone = AgreementMilestone::find($milestoneId);

            if ($milestone) {
                // Update milestone status
                $milestone->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);

                // Add to history
                AgreementHistory::create([
                    'agreement_id' => $milestone->agreement_id,
                    'user_id' => Auth::id(),
                    'action' => 'payment',
                    'description' => "Payment received for milestone: {$milestone->phase_name} - INR {$order->amount}",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Create invoice automatically
                $invoice = \App\Models\AgreementInvoice::create([
                    'agreement_id' => $milestone->agreement_id,
                    'milestone_id' => $milestone->id,
                    'client_id' => $milestone->agreement->client_id,
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(7),
                    'subtotal' => $order->amount,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'discount_amount' => 0,
                    'total_amount' => $order->amount,
                    'amount_paid' => $order->amount,
                    'balance_due' => 0,
                    'status' => 'paid',
                    'payment_method' => 'razorpay',
                    'paid_at' => now(),
                    'notes' => "Auto-generated invoice for milestone payment: {$milestone->phase_name}",
                ]);

                // Notify client with invoice (silently fail if email fails)
                try {
                    $milestone->agreement->client->notify(new \App\Notifications\InvoicePaidNotification($invoice));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send invoice notification: ' . $e->getMessage());
                }

                // Notify admin (silently fail if email fails)
                try {
                    $admin = \App\Models\User::where('is_admin', true)->first();
                    if ($admin) {
                        $admin->notify(new \App\Notifications\MilestonePaidNotification($milestone, $order));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send admin notification: ' . $e->getMessage());
                }

                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('success', 'Payment successful! Invoice generated.');
            }

            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('success', 'Payment successful!');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Milestone Razorpay verification failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }

    // For local payments (EasyPaisa/JazzCash/Bank Transfer)
    public function submitPaymentProof(Request $request, AgreementMilestone $milestone)
    {
        $request->validate([
            'payment_method' => 'required|in:easypaisa,jazzcash,bank_transfer',
            'transaction_id' => 'required|string|max:255',
            'screenshot' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        // Ensure user has access
        if ($milestone->agreement->client_id !== Auth::id()) {
            abort(403);
        }

        $screenshotPath = null;
        if ($request->hasFile('screenshot')) {
            $screenshotPath = $request->file('screenshot')->store('payment-proofs', 'public');
        }

        // Create payment proof record
        $paymentProof = \App\Models\PaymentProof::create([
            'milestone_id' => $milestone->id,
            'agreement_id' => $milestone->agreement_id,
            'user_id' => Auth::id(),
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'screenshot_path' => $screenshotPath,
            'notes' => $request->notes,
            'amount' => $milestone->payment_amount,
            'status' => 'pending_verification',
        ]);

        // Notify admin (silently fail if email fails)
        try {
            $admin = \App\Models\User::where('is_admin', true)->first();
            if ($admin) {
                $admin->notify(new \App\Notifications\PaymentProofSubmittedNotification($paymentProof));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send payment proof notification: ' . $e->getMessage());
        }

        return back()->with('success', 'Payment proof submitted successfully! We will verify and update your milestone status within 24 hours.');
    }

    public function cashfreeCallback(Request $request)
    {
        $settings = Setting::where('group', 'General')->pluck('value', 'key');
        $clientId = $settings['cashfree_app_id'] ?? '';
        $clientSecret = $settings['cashfree_secret_key'] ?? '';
        $isProduction = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';

        $orderId = $request->get('order_id');
        $cfOrderId = $request->get('cf_order_id');

        // Validate cf_order_id is present
        if (empty($cfOrderId)) {
            \Illuminate\Support\Facades\Log::error('Cashfree milestone callback: cf_order_id is missing for order: ' . $orderId);
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Payment verification failed: Missing order reference. Please contact support.');
        }

        try {
            $baseUrl = $isProduction
                ? 'https://api.cashfree.com/pg/orders/' . $cfOrderId
                : 'https://sandbox.cashfree.com/pg/orders/' . $cfOrderId;

            $client = new \GuzzleHttp\Client([
                'verify' => true,
                'timeout' => 60,
                'connect_timeout' => 30,
                'curl' => [
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-version' => '2023-08-01',
                    'x-client-id' => $clientId,
                    'x-client-secret' => $clientSecret,
                ],
            ]);

            $response = $client->get($baseUrl);
            $response = new \Illuminate\Http\Client\Response($response);

            if (!$response->successful()) {
                \Illuminate\Support\Facades\Log::error('Cashfree milestone callback API error: HTTP ' . $response->status() . ' for order: ' . $orderId);
                throw new \Exception('Failed to verify order: HTTP ' . $response->status());
            }

            $cfOrder = $response->json();

            $order = \App\Models\Order::where('order_number', $orderId)->firstOrFail();

            if (($order->metadata['type'] ?? null) !== 'milestone_payment') {
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Invalid payment order.');
            }

            if ($cfOrder['order_status'] === 'PAID') {
                // Mark order as paid
                $order->markAsPaid(
                    'cashfree',
                    $cfOrder['cf_order_id'] ?? $cfOrderId,
                    $cfOrder['order_id'] ?? $orderId,
                    $cfOrder
                );

                $milestoneId = $order->metadata['milestone_id'] ?? null;
                $milestone = AgreementMilestone::find($milestoneId);

                if ($milestone) {
                    // Update milestone status
                    $milestone->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // Add to history
                    AgreementHistory::create([
                        'agreement_id' => $milestone->agreement_id,
                        'user_id' => Auth::id(),
                        'action' => 'payment',
                        'description' => "Payment received for milestone: {$milestone->phase_name} - INR {$order->amount}",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Create invoice automatically
                    $invoice = \App\Models\AgreementInvoice::create([
                        'agreement_id' => $milestone->agreement_id,
                        'milestone_id' => $milestone->id,
                        'client_id' => $milestone->agreement->client_id,
                        'invoice_date' => now(),
                        'due_date' => now()->addDays(7),
                        'subtotal' => $order->amount,
                        'tax_amount' => 0,
                        'tax_rate' => 0,
                        'discount_amount' => 0,
                        'total_amount' => $order->amount,
                        'amount_paid' => $order->amount,
                        'balance_due' => 0,
                        'status' => 'paid',
                        'payment_method' => 'cashfree',
                        'paid_at' => now(),
                        'notes' => "Auto-generated invoice for milestone payment: {$milestone->phase_name}",
                    ]);

                    // Notify client with invoice (silently fail if email fails)
                    try {
                        $milestone->agreement->client->notify(new \App\Notifications\InvoicePaidNotification($invoice));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send invoice notification: ' . $e->getMessage());
                    }

                    // Notify admin (silently fail if email fails)
                    try {
                        $admin = \App\Models\User::where('is_admin', true)->first();
                        if ($admin) {
                            $admin->notify(new \App\Notifications\MilestonePaidNotification($milestone, $order));
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send admin notification: ' . $e->getMessage());
                    }

                    return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                        ->with('success', 'Payment successful! Invoice generated.');
                }

                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('success', 'Payment successful!');
            } else {
                $order->update(['status' => 'failed']);
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Payment failed. Status: ' . ($cfOrder['order_status'] ?? 'unknown'));
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Milestone Cashfree verification failed for order ' . $orderId . ': ' . $e->getMessage(), [
                'order_id' => $orderId,
                'cf_order_id' => $cfOrderId,
                'exception' => $e,
            ]);

            // Check if it's a 404 - order not found error
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, '404') !== false || strpos($errorMessage, 'order_not_found') !== false) {
                // Order not found on Cashfree - may have expired or been cleared
                $order->update(['status' => 'failed', 'notes' => 'Payment order expired or not found on payment gateway']);
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Payment session expired. Please try placing the order again.');
            }

            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Payment verification failed. Please contact support if amount was deducted.');
        }
    }

    public function paypalCallback(Request $request)
    {
        $settings = Setting::where('group', 'General')->pluck('value', 'key');
        $clientId = $settings['paypal_client_id'] ?? '';
        $clientSecret = $settings['paypal_client_secret'] ?? '';
        $isProduction = ($settings['paypal_mode'] ?? 'sandbox') === 'production';

        $orderId = $request->get('order_id');
        $token = $request->get('token'); // PayPal order ID

        $order = \App\Models\Order::findOrFail($orderId);

        try {
            $baseUrl = $isProduction
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // Get PayPal access token
            $tokenResponse = \Illuminate\Support\Facades\Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($baseUrl . '/v1/oauth2/token', [
                    'grant_type' => 'client_credentials'
                ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            // Capture the payment
            $captureResponse = \Illuminate\Support\Facades\Http::withToken($accessToken)
                ->post($baseUrl . '/v2/checkout/orders/' . $token . '/capture');

            if (!$captureResponse->successful()) {
                throw new \Exception('Failed to capture PayPal payment');
            }

            $captureData = $captureResponse->json();

            if ($captureData['status'] === 'COMPLETED') {
                if (($order->metadata['type'] ?? null) !== 'milestone_payment') {
                    return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                        ->with('error', 'Invalid payment order.');
                }

                $order->markAsPaid(
                    'paypal',
                    $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? $token,
                    $token,
                    $captureData
                );

                $milestoneId = $order->metadata['milestone_id'] ?? null;
                $milestone = AgreementMilestone::find($milestoneId);

                if ($milestone) {
                    // Update milestone status
                    $milestone->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // Add to history
                    AgreementHistory::create([
                        'agreement_id' => $milestone->agreement_id,
                        'user_id' => Auth::id(),
                        'action' => 'payment',
                        'description' => "Payment received for milestone: {$milestone->phase_name} - {$order->amount} {$order->currency}",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Create invoice automatically
                    $invoice = \App\Models\AgreementInvoice::create([
                        'agreement_id' => $milestone->agreement_id,
                        'milestone_id' => $milestone->id,
                        'client_id' => $milestone->agreement->client_id,
                        'invoice_date' => now(),
                        'due_date' => now()->addDays(7),
                        'subtotal' => $order->amount,
                        'tax_amount' => 0,
                        'tax_rate' => 0,
                        'discount_amount' => 0,
                        'total_amount' => $order->amount,
                        'amount_paid' => $order->amount,
                        'balance_due' => 0,
                        'status' => 'paid',
                        'payment_method' => 'paypal',
                        'paid_at' => now(),
                        'notes' => "Auto-generated invoice for milestone payment: {$milestone->phase_name}",
                    ]);

                    // Notify client with invoice (silently fail if email fails)
                    try {
                        $milestone->agreement->client->notify(new \App\Notifications\InvoicePaidNotification($invoice));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send invoice notification: ' . $e->getMessage());
                    }

                    // Notify admin (silently fail if email fails)
                    try {
                        $admin = \App\Models\User::where('is_admin', true)->first();
                        if ($admin) {
                            $admin->notify(new \App\Notifications\MilestonePaidNotification($milestone, $order));
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send admin notification: ' . $e->getMessage());
                    }

                    return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                        ->with('success', 'Payment successful! Invoice generated.');
                }

                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('success', 'Payment successful!');
            } else {
                $order->update(['status' => 'failed']);
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Payment failed. Status: ' . $captureData['status']);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Milestone PayPal verification failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }

    public function payuCallback(Request $request)
    {
        $settings = Setting::where('group', 'General')->pluck('value', 'key');
        $key = $settings['payu_key'] ?? '';
        $salt = $settings['payu_salt'] ?? '';

        $status = $request->get('status');
        $txnid = $request->get('txnid');
        $payuMoneyId = $request->get('payuMoneyId');
        $hash = $request->get('hash');

        try {
            // Verify hash
            $reverseHashString = $salt . '|' . $status . '|||||||||||' . $request->get('email') . '|' . $request->get('firstname') . '|' . $request->get('productinfo') . '|' . $request->get('amount') . '|' . $txnid . '|' . $key;
            $reverseHash = strtolower(hash('sha512', $reverseHashString));

            if ($hash !== $reverseHash) {
                throw new \Exception('PayU hash verification failed');
            }

            $order = \App\Models\Order::where('order_number', $txnid)->firstOrFail();

            if (($order->metadata['type'] ?? null) !== 'milestone_payment') {
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Invalid payment order.');
            }

            if ($status === 'success') {
                $order->markAsPaid(
                    'payu',
                    $payuMoneyId,
                    $txnid,
                    $request->all()
                );

                $milestoneId = $order->metadata['milestone_id'] ?? null;
                $milestone = AgreementMilestone::find($milestoneId);

                if ($milestone) {
                    // Update milestone status
                    $milestone->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // Add to history
                    AgreementHistory::create([
                        'agreement_id' => $milestone->agreement_id,
                        'user_id' => Auth::id(),
                        'action' => 'payment',
                        'description' => "Payment received for milestone: {$milestone->phase_name} - INR {$order->amount}",
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    // Create invoice automatically
                    $invoice = \App\Models\AgreementInvoice::create([
                        'agreement_id' => $milestone->agreement_id,
                        'milestone_id' => $milestone->id,
                        'client_id' => $milestone->agreement->client_id,
                        'invoice_date' => now(),
                        'due_date' => now()->addDays(7),
                        'subtotal' => $order->amount,
                        'tax_amount' => 0,
                        'tax_rate' => 0,
                        'discount_amount' => 0,
                        'total_amount' => $order->amount,
                        'amount_paid' => $order->amount,
                        'balance_due' => 0,
                        'status' => 'paid',
                        'payment_method' => 'payu',
                        'paid_at' => now(),
                        'notes' => "Auto-generated invoice for milestone payment: {$milestone->phase_name}",
                    ]);

                    // Notify client with invoice (silently fail if email fails)
                    try {
                        $milestone->agreement->client->notify(new \App\Notifications\InvoicePaidNotification($invoice));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send invoice notification: ' . $e->getMessage());
                    }

                    // Notify admin (silently fail if email fails)
                    try {
                        $admin = \App\Models\User::where('is_admin', true)->first();
                        if ($admin) {
                            $admin->notify(new \App\Notifications\MilestonePaidNotification($milestone, $order));
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send admin notification: ' . $e->getMessage());
                    }

                    return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                        ->with('success', 'Payment successful! Invoice generated.');
                }

                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('success', 'Payment successful!');
            } else {
                $order->update(['status' => 'failed']);
                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('error', 'Payment failed. Status: ' . $status);
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Milestone PayU verification failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }
}
