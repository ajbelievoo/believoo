<?php

namespace App\Http\Controllers;

use App\Mail\WalletTopupSuccess;
use App\Models\Order;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WalletTopup;
use App\Services\WalletService;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Api;

class WalletController extends Controller
{
    public function topup()
    {
        $user = Auth::user();
        $transactions = $user->walletTransactions()->latest()->limit(20)->get();
        $topups = WalletTopup::where('user_id', $user->id)->latest()->limit(10)->get();
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');

        return view('wallet.topup', compact('user', 'transactions', 'topups', 'settings'));
    }

    public function createTopup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10|max:500000',
        ]);

        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        $enabled = ($settings['razorpay_enabled'] ?? '0') === '1';
        $keyId = $settings['razorpay_key_id'] ?? '';
        $keySecret = $settings['razorpay_key_secret'] ?? '';

        if (!$enabled || empty($keyId) || empty($keySecret)) {
            return response()->json(['error' => 'Razorpay is not configured for wallet top-up.'], 400);
        }

        $user = Auth::user();
        $amount = round((float) $request->amount, 2);

        $topup = WalletTopup::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'razorpay',
        ]);

        try {
            $api = new Api($keyId, $keySecret);
            $razorpayOrder = $api->order->create([
                'amount' => (int) round($amount * 100),
                'currency' => 'INR',
                'receipt' => $topup->topup_number,
                'notes' => [
                    'wallet_topup_id' => $topup->id,
                    'user_id' => $user->id,
                ],
            ]);

            $topup->update(['gateway_order_id' => $razorpayOrder['id']]);

            return response()->json([
                'key_id' => $keyId,
                'amount' => (int) round($amount * 100),
                'currency' => 'INR',
                'order_id' => $razorpayOrder['id'],
                'name' => config('app.name'),
                'description' => 'Wallet Top-up ' . $topup->topup_number,
                'prefill' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'contact' => $user->phone ?? '',
                ],
                'notes' => [
                    'wallet_topup_id' => $topup->id,
                ],
            ]);
        } catch (\Exception $e) {
            $topup->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create wallet top-up order.'], 500);
        }
    }

    public function topupCallback(Request $request, WalletService $walletService)
    {
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        $keyId = $settings['razorpay_key_id'] ?? '';
        $keySecret = $settings['razorpay_key_secret'] ?? '';

        try {
            $api = new Api($keyId, $keySecret);
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];

            $api->utility->verifyPaymentSignature($attributes);

            $topup = WalletTopup::where('gateway_order_id', $request->razorpay_order_id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            if (!$topup->isPaid()) {
                $topup->update([
                    'status' => 'paid',
                    'gateway_payment_id' => $request->razorpay_payment_id,
                    'gateway_signature' => $request->razorpay_signature,
                    'payment_response' => $request->all(),
                    'paid_at' => now(),
                ]);

                $walletService->credit(
                    $topup->user,
                    (float) $topup->amount,
                    'gateway_topup',
                    'Wallet top-up via Razorpay',
                    ['reference' => $topup->topup_number, 'gateway_payment_id' => $request->razorpay_payment_id]
                );

                try {
                    Mail::to($topup->user->email)->send(new WalletTopupSuccess($topup->user, $topup));
                } catch (\Exception $e) {
                    Log::error("Wallet: Failed to send top-up success email to {$topup->user->email}: " . $e->getMessage());
                }
            }

            return redirect()->route('wallet.topup')->with('success', 'Wallet topped up successfully.');
        } catch (\Exception $e) {
            return redirect()->route('wallet.topup')->with('error', 'Wallet top-up verification failed.');
        }
    }

    public function pay(Request $request, WalletService $walletService)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'tier_name' => 'nullable|string',
            'amount' => 'required|numeric|min:1',
            'billing_months' => 'required|integer|min:1',
            'quantity' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();
        $service = Service::findOrFail($request->service_id);
        $totalAmountInINR = round((float) $request->amount, 2);
        $gstRate = 0.18;
        $subtotalInINR = round($totalAmountInINR / (1 + $gstRate), 2);
        $gstAmountInINR = round($totalAmountInINR - $subtotalInINR, 2);

        if (!$walletService->canDebit($user, $totalAmountInINR)) {
            return response()->json([
                'error' => 'Insufficient wallet balance. Please add funds and try again.',
                'wallet_balance' => (float) ($user->wallet_balance ?? 0),
            ], 422);
        }

        $searchName = trim($request->tier_name ?? '');

        // If no tier_name, try service title as fallback
        if (empty($searchName)) {
            $searchName = trim($service->title ?? '');
        }

        $metadata = [
            'quantity' => max(1, (int) ($request->quantity ?? 1)),
            'tier_name' => $request->tier_name,
            'billing_months' => (int) $request->billing_months,
        ];

        if (!empty($searchName)) {
            $plan = \App\Models\VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$searchName])
                ->orWhereRaw('LOWER(slug) = LOWER(?)', [$searchName])
                ->first();

            if ($plan) {
                $metadata['vps_plan_id'] = $plan->id;
                $metadata['cpu_cores'] = $plan->cpu_cores;
                $metadata['memory_gb'] = $plan->memory_gb;
                $metadata['disk_gb'] = $plan->disk_gb;
                $metadata['disk_type'] = $plan->disk_type;
                $metadata['os'] = 'Ubuntu 22.04';
                $metadata['iso'] = 'ubuntu-22.04-live-server-amd64.iso';
            }
        }

        if (session()->has('streaming_addon_context')) {
            $metadata['streaming_addon_context'] = session('streaming_addon_context');
        }

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
            'payment_gateway' => 'wallet',
            'metadata' => $metadata,
        ]);

        $walletService->debit(
            $user,
            $totalAmountInINR,
            'service_payment',
            'Payment for ' . $service->title,
            ['reference' => $order->order_number],
            null,
            $order
        );

        $order->markAsPaid('wallet', 'WALLET-' . $order->order_number, $order->order_number, [
            'wallet_balance_after' => Auth::user()->fresh()->wallet_balance,
        ]);

        return response()->json([
            'success' => true,
            'redirect_url' => route('client.orders'),
        ]);
    }

    /**
     * Make Cashfree API request via system curl (PHP OpenSSL is too old for Cashfree CDN).
     */
    private function makeCashfreeCurlRequest(string $method, string $url, string $appId, string $secret, ?array $payload = null): array
    {
        $tmpFile = sys_get_temp_dir() . '/cf_' . uniqid() . '.json';
        $headers = [
            'Content-Type: application/json',
            'x-api-version: 2023-08-01',
            'x-client-id: ' . $appId,
            'x-client-secret: ' . $secret,
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

    /**
     * Create Cashfree wallet top-up order
     */
    public function createCashfreeTopup(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10|max:500000']);

        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        $enabled = ($settings['cashfree_enabled'] ?? '0') === '1';
        $appId = $settings['cashfree_app_id'] ?? '';
        $secret = $settings['cashfree_secret_key'] ?? '';

        if (!$enabled || empty($appId) || empty($secret)) {
            return response()->json(['error' => 'Cashfree is not configured for wallet top-up.'], 400);
        }

        if (($settings['cashfree_mode'] ?? 'sandbox') !== 'production' && !Auth::user()->isAdmin()) {
            return response()->json(['error' => 'Cashfree is in test mode and cannot accept real payments right now.'], 400);
        }

        $user = Auth::user();
        $amount = round((float) $request->amount, 2);

        $topup = WalletTopup::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'pending',
            'payment_gateway' => 'cashfree',
        ]);

        try {
            $isProduction = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';
            $baseUrl = $isProduction
                ? 'https://api.cashfree.com/pg/orders'
                : 'https://sandbox.cashfree.com/pg/orders';

            $payload = [
                'order_amount' => $amount,
                'order_currency' => 'INR',
                'order_id' => (string) $topup->topup_number,
                'customer_details' => [
                    'customer_id' => 'CUST_' . $user->id,
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                    'customer_phone' => $user->phone ?? '9999999999',
                ],
                'order_meta' => [
                    'return_url' => route('wallet.topup.cashfree.callback') . '?topup_id=' . $topup->id . '&order_id={order_id}&cf_order_id={cf_order_id}',
                ],
                'order_note' => 'Wallet Top-up ' . $topup->topup_number,
            ];

            $response = $this->makeCashfreeCurlRequest('POST', $baseUrl, $appId, $secret, $payload);
            $statusCode = $response['status'];
            $responseBody = $response['body'];
            $cfOrder = json_decode($responseBody, true);

            if ($statusCode < 200 || $statusCode >= 300 || empty($cfOrder['payment_session_id'])) {
                $topup->update(['status' => 'failed', 'notes' => 'Cashfree API error: ' . $responseBody]);
                return response()->json(['error' => 'Cashfree API error. Please try again.'], 400);
            }

            $topup->update(['gateway_order_id' => $cfOrder['cf_order_id'] ?? $cfOrder['order_id']]);

            return response()->json([
                'payment_session_id' => $cfOrder['payment_session_id'],
                'topup_id' => $topup->id,
                'cf_order_id' => $cfOrder['cf_order_id'] ?? $cfOrder['order_id'],
                'environment' => $isProduction ? 'production' : 'sandbox',
            ]);
        } catch (\Exception $e) {
            Log::error('Cashfree wallet top-up failed: ' . $e->getMessage());
            $topup->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create Cashfree top-up: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cashfree wallet top-up callback
     */
    public function cashfreeTopupCallback(Request $request, WalletService $walletService)
    {
        $topup = WalletTopup::findOrFail($request->topup_id);
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        $appId = $settings['cashfree_app_id'] ?? '';
        $secret = $settings['cashfree_secret_key'] ?? '';

        try {
            $isProduction = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';
            $baseUrl = $isProduction
                ? 'https://api.cashfree.com/pg/orders/' . $topup->gateway_order_id . '/payments'
                : 'https://sandbox.cashfree.com/pg/orders/' . $topup->gateway_order_id . '/payments';

            $client = new Client([
                'verify' => false,
                'timeout' => 60,
                'version' => 1.1,
                'curl' => [
                    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_SSL_CIPHER_LIST => 'DEFAULT:@SECLEVEL=1',
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'x-api-version' => '2023-08-01',
                    'x-client-id' => $appId,
                    'x-client-secret' => $secret,
                ],
            ]);

            $response = $client->get($baseUrl);
            $data = json_decode((string) $response->getBody(), true);
            $payments = $data['data'] ?? [];
            $successfulPayment = null;

            foreach ($payments as $payment) {
                if (($payment['payment_status'] ?? '') === 'SUCCESS') {
                    $successfulPayment = $payment;
                    break;
                }
            }

            if ($successfulPayment) {
                if (!$topup->isPaid()) {
                    $topup->update([
                        'status' => 'paid',
                        'gateway_payment_id' => $successfulPayment['cf_payment_id'] ?? null,
                        'payment_response' => $successfulPayment,
                        'paid_at' => now(),
                    ]);

                    $walletService->credit(
                        $topup->user,
                        (float) $topup->amount,
                        'gateway_topup',
                        'Wallet top-up via Cashfree',
                        ['reference' => $topup->topup_number, 'gateway_payment_id' => $successfulPayment['cf_payment_id'] ?? null]
                    );

                    try {
                        Mail::to($topup->user->email)->send(new WalletTopupSuccess($topup->user, $topup));
                    } catch (\Exception $e) {
                        Log::error("Wallet: Failed to send top-up success email: " . $e->getMessage());
                    }
                }

                return redirect()->route('client.dashboard')->with('success', 'Wallet topped up successfully via Cashfree.');
            } else {
                $topup->update(['status' => 'failed']);
                return redirect()->route('client.dashboard')->with('error', 'Cashfree payment failed or was cancelled.');
            }
        } catch (\Exception $e) {
            Log::error('Cashfree wallet callback error: ' . $e->getMessage());
            return redirect()->route('client.dashboard')->with('error', 'Payment verification failed.');
        }
    }

    /**
     * Create PayPal wallet top-up order
     */
    public function createPaypalTopup(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10|max:500000']);

        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        $enabled = ($settings['paypal_enabled'] ?? '0') === '1';
        $clientId = $settings['paypal_client_id'] ?? '';
        $clientSecret = $settings['paypal_client_secret'] ?? '';

        if (!$enabled || empty($clientId) || empty($clientSecret)) {
            return response()->json(['error' => 'PayPal is not configured for wallet top-up.'], 400);
        }

        $user = Auth::user();
        $amount = round((float) $request->amount, 2);

        $topup = WalletTopup::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_gateway' => 'paypal',
        ]);

        try {
            $isProduction = ($settings['paypal_mode'] ?? 'sandbox') === 'production';
            $baseUrl = $isProduction ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            $tokenResponse = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            $paypalOrder = Http::withToken($accessToken)
                ->post($baseUrl . '/v2/checkout/orders', [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => (string) $topup->topup_number,
                            'description' => 'Wallet Top-up ' . $topup->topup_number,
                            'amount' => [
                                'currency_code' => 'USD',
                                'value' => number_format($amount, 2, '.', ''),
                            ],
                        ]
                    ],
                    'application_context' => [
                        'return_url' => route('wallet.topup.paypal.callback') . '?topup_id=' . $topup->id,
                        'cancel_url' => route('client.dashboard'),
                        'brand_name' => config('app.name'),
                        'landing_page' => 'BILLING',
                        'user_action' => 'PAY_NOW',
                    ]
                ]);

            if (!$paypalOrder->successful()) {
                throw new \Exception('Failed to create PayPal order: ' . $paypalOrder->body());
            }

            $paypalData = $paypalOrder->json();
            $topup->update(['gateway_order_id' => $paypalData['id']]);

            $approvalUrl = null;
            foreach ($paypalData['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalUrl = $link['href'];
                    break;
                }
            }

            return response()->json([
                'topup_id' => $topup->id,
                'paypal_order_id' => $paypalData['id'],
                'approval_url' => $approvalUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal wallet top-up failed: ' . $e->getMessage());
            $topup->update(['status' => 'failed', 'notes' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create PayPal top-up: ' . $e->getMessage()], 500);
        }
    }

    /**
     * PayPal wallet top-up callback
     */
    public function paypalTopupCallback(Request $request, WalletService $walletService)
    {
        $topup = WalletTopup::findOrFail($request->topup_id);
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        $clientId = $settings['paypal_client_id'] ?? '';
        $clientSecret = $settings['paypal_client_secret'] ?? '';

        try {
            $isProduction = ($settings['paypal_mode'] ?? 'sandbox') === 'production';
            $baseUrl = $isProduction ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            $tokenResponse = Http::withBasicAuth($clientId, $clientSecret)
                ->asForm()
                ->post($baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get PayPal access token');
            }

            $accessToken = $tokenResponse->json()['access_token'];

            $captureResponse = Http::withToken($accessToken)
                ->post($baseUrl . '/v2/checkout/orders/' . $topup->gateway_order_id . '/capture');

            if (!$captureResponse->successful()) {
                throw new \Exception('Failed to capture PayPal payment');
            }

            $captureData = $captureResponse->json();

            if ($captureData['status'] === 'COMPLETED') {
                if (!$topup->isPaid()) {
                    $topup->update([
                        'status' => 'paid',
                        'gateway_payment_id' => $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? $topup->gateway_order_id,
                        'payment_response' => $captureData,
                        'paid_at' => now(),
                    ]);

                    $walletService->credit(
                        $topup->user,
                        (float) $topup->amount,
                        'gateway_topup',
                        'Wallet top-up via PayPal',
                        ['reference' => $topup->topup_number, 'gateway_payment_id' => $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null]
                    );

                    try {
                        Mail::to($topup->user->email)->send(new WalletTopupSuccess($topup->user, $topup));
                    } catch (\Exception $e) {
                        Log::error("Wallet: Failed to send top-up success email: " . $e->getMessage());
                    }
                }

                return redirect()->route('client.dashboard')->with('success', 'Wallet topped up successfully via PayPal.');
            } else {
                $topup->update(['status' => 'failed']);
                return redirect()->route('client.dashboard')->with('error', 'PayPal payment failed. Status: ' . $captureData['status']);
            }
        } catch (\Exception $e) {
            Log::error('PayPal wallet callback error: ' . $e->getMessage());
            return redirect()->route('client.dashboard')->with('error', 'Payment verification failed.');
        }
    }
}
