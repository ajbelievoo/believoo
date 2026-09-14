<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Notification;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Razorpay\Api\Api;

class BillingController extends Controller {
    public static $plans = [
        'free' => ['name' => 'Free', 'price' => 0, 'members' => 2, 'calls' => '1-on-1', 'remote' => false, 'ai' => false],
        'pro' => ['name' => 'Pro / Developer', 'price' => 1999, 'members' => 10, 'calls' => 'Unlimited', 'remote' => true, 'ai' => false],
        'enterprise' => ['name' => 'Enterprise / Business', 'price' => 9999, 'members' => null, 'calls' => 'Unlimited', 'remote' => true, 'ai' => true],
    ];

    public function index(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $invoices = Invoice::where('company_id', $company->id)->latest()->paginate(20);
        return view('bconnect.billing', compact('company', 'invoices'));
    }

    public function upgrade(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        return view('bconnect.upgrade', ['company' => $company, 'plans' => self::$plans]);
    }

    public function processUpgrade(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $data = $r->validate(['plan' => 'required|in:pro,enterprise', 'billing_cycle' => 'nullable|in:monthly,yearly']);
        $plan = $data['plan'];
        $cycle = $data['billing_cycle'] ?? 'monthly';

        if (!isset(self::$plans[$plan])) {
            return back()->with('error', 'Invalid plan selected.');
        }

        $basePrice = self::$plans[$plan]['price'];
        $multiplier = $cycle === 'yearly' ? 10 : 1;
        $amount = $basePrice * $multiplier;

        $inv = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $r->input('bconnect_member')->id,
            'invoice_number' => 'BCU-' . strtoupper(uniqid()),
            'amount' => $amount,
            'currency' => 'INR',
            'status' => 'pending',
            'description' => "B-CONNECT {$cycle} plan upgrade to " . self::$plans[$plan]['name'],
            'metadata' => [
                'plan_upgrade' => $plan,
                'billing_cycle' => $cycle,
            ],
        ]);

        return redirect()->route('bconnect.billing.pay', $inv->id)->with('info', 'Please complete payment to activate the plan.');
    }

    protected function applyPlanUpgrade(Invoice $invoice) {
        $plan = $invoice->metadata['plan_upgrade'] ?? null;
        if (!$plan || !isset(self::$plans[$plan])) {
            return;
        }

        $company = Company::find($invoice->company_id);
        if (!$company) {
            return;
        }

        $cycle = $invoice->metadata['billing_cycle'] ?? 'monthly';
        $months = $cycle === 'yearly' ? 12 : 1;

        $company->update([
            'plan' => $plan,
            'plan_expires_at' => now()->addMonths($months)->endOfDay(),
        ]);

        Notification::create([
            'company_id' => $company->id,
            'member_id' => $invoice->client_id ?: 0,
            'type' => 'billing',
            'title' => 'Plan upgraded',
            'message' => 'Your workspace has been upgraded to ' . self::$plans[$plan]['name'] . ' for ' . $months . ' month(s).',
            'url' => route('bconnect.billing'),
        ]);
    }

    public function storeInvoice(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $data = $r->validate([
            'client_id' => [
                'required',
                Rule::exists('bconnect_members', 'id')->where('company_id', $companyId),
            ],
            'amount' => 'required|numeric|min:0',
            'description' => 'required',
        ]);
        $company = Company::findOrFail($companyId);
        $inv = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $data['client_id'],
            'invoice_number' => 'BCI-' . strtoupper(uniqid()),
            'amount' => $data['amount'],
            'currency' => 'INR',
            'status' => 'pending',
            'description' => $data['description'],
        ]);
        return redirect()->route('bconnect.billing')->with('success', 'Invoice #' . $inv->invoice_number . ' created');
    }

    public function payInvoice(Request $r, $invoice) {
        $inv = Invoice::where('id', $invoice)->where('company_id', $r->input('bconnect_company_id'))->firstOrFail();
        $settings = Setting::pluck('value', 'key')->toArray();

        if (($settings['razorpay_enabled'] ?? '0') == '1' && !empty($settings['razorpay_key_id']) && !empty($settings['razorpay_key_secret'])) {
            $api = new Api($settings['razorpay_key_id'], $settings['razorpay_key_secret']);
            $order = $api->order->create([
                'receipt' => $inv->invoice_number,
                'amount' => (int) ($inv->amount * 100),
                'currency' => strtoupper($inv->currency ?? 'INR'),
                'payment_capture' => 1,
            ]);
            $inv->update(['metadata' => ['razorpay_order_id' => $order['id']]]);
            return view('bconnect.payment.razorpay', ['invoice' => $inv, 'order' => $order, 'key' => $settings['razorpay_key_id']]);
        }

        if (($settings['cashfree_enabled'] ?? '0') == '1' && !empty($settings['cashfree_app_id']) && !empty($settings['cashfree_secret_key'])) {
            $orderId = $inv->invoice_number . '-' . time();
            $inv->update(['metadata' => ['cashfree_order_id' => $orderId]]);

            $payload = [
                'appId' => $settings['cashfree_app_id'],
                'orderId' => $orderId,
                'orderAmount' => $inv->amount,
                'orderCurrency' => strtoupper($inv->currency ?? 'INR'),
                'orderNote' => $inv->description ?: 'B-CONNECT invoice payment',
                'customerName' => Auth::user()?->name ?? 'Customer',
                'customerEmail' => Auth::user()?->email ?? 'customer@believoo.com',
                'customerPhone' => '9999999999',
                'returnUrl' => url('/billing/callback/cashfree'),
                'notifyUrl' => url('/billing/callback/cashfree'),
            ];

            ksort($payload);
            $signatureData = '';
            foreach ($payload as $key => $value) {
                $signatureData .= $key . $value;
            }
            $signature = base64_encode(hash_hmac('sha256', $signatureData, $settings['cashfree_secret_key'], true));
            $payload['signature'] = $signature;

            $isProduction = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';
            $action = $isProduction
                ? 'https://www.cashfree.com/checkout/post/submit'
                : 'https://test.cashfree.com/billpay/checkout/post/submit';

            return view('bconnect.payment.cashfree', [
                'invoice' => $inv,
                'payload' => $payload,
                'action' => $action,
            ]);
        }

        return back()->with('error', 'No payment gateway configured. Go to Admin → Settings → Payment.');
    }

    public function razorpayCallback(Request $request) {
        $settings = Setting::pluck('value', 'key')->toArray();

        if (empty($settings['razorpay_key_id']) || empty($settings['razorpay_key_secret'])) {
            return redirect()->route('bconnect.billing')->with('error', 'Razorpay credentials not configured.');
        }

        $api = new Api($settings['razorpay_key_id'], $settings['razorpay_key_secret']);
        try {
            $api->utility->verifyPaymentSignature($request->all());
            $inv = Invoice::where('metadata->razorpay_order_id', $request->razorpay_order_id)->firstOrFail();
            $inv->update(['status' => 'paid', 'paid_at' => now()]);
            $this->applyPlanUpgrade($inv);
            $this->notify($inv, 'Invoice paid via Razorpay');
            return redirect()->route('bconnect.billing')->with('success', 'Payment successful');
        } catch (\Exception $e) {
            Log::error('Razorpay callback verification failed: ' . $e->getMessage());
            return redirect()->route('bconnect.billing')->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    public function cashfreeCallback(Request $request) {
        $settings = Setting::pluck('value', 'key')->toArray();

        $inv = Invoice::where('metadata->cashfree_order_id', $request->orderId)->first();
        if (!$inv) {
            return redirect()->route('bconnect.billing')->with('error', 'Invoice not found.');
        }

        // Verify Cashfree legacy response signature when available.
        $signature = $request->signature;
        if ($signature && !empty($settings['cashfree_secret_key'])) {
            $data = ($request->orderId ?? '') . ($request->orderAmount ?? '') . ($request->referenceId ?? '') . ($request->txStatus ?? '') . ($request->paymentMode ?? '') . ($request->txMsg ?? '') . ($request->txTime ?? '');
            $expected = base64_encode(hash_hmac('sha256', $data, $settings['cashfree_secret_key'], true));
            if (!hash_equals($expected, $signature)) {
                Log::warning('Cashfree signature mismatch for order ' . $request->orderId);
                return redirect()->route('bconnect.billing')->with('error', 'Payment verification failed.');
            }
        }

        if ($request->txStatus === 'SUCCESS') {
            $inv->update(['status' => 'paid', 'paid_at' => now()]);
            $this->applyPlanUpgrade($inv);
            $this->notify($inv, 'Invoice paid via Cashfree');
            return redirect()->route('bconnect.billing')->with('success', 'Payment successful');
        }

        return redirect()->route('bconnect.billing')->with('error', 'Payment failed: ' . ($request->txMsg ?? 'Unknown'));
    }

    public function markPaid(Request $r, $invoice) {
        $inv = Invoice::where('id', $invoice)->where('company_id', $r->input('bconnect_company_id'))->firstOrFail();
        if ($inv->status === 'paid') {
            return back()->with('info', 'Invoice already paid.');
        }
        $inv->update(['status' => 'paid', 'paid_at' => now(), 'metadata' => array_merge($inv->metadata ?? [], ['manual' => true])]);
        $this->applyPlanUpgrade($inv);
        $this->notify($inv, 'Invoice marked as paid manually');
        return redirect()->route('bconnect.billing')->with('success', 'Invoice marked as paid');
    }

    protected function notify($invoice, $message) {
        Notification::create([
            'company_id' => $invoice->company_id,
            'member_id' => $invoice->client_id ?: 0,
            'type' => 'billing',
            'title' => $message,
            'message' => 'Invoice ' . $invoice->invoice_number . ' paid ₹' . $invoice->amount,
            'url' => route('bconnect.billing'),
        ]);
    }
}
