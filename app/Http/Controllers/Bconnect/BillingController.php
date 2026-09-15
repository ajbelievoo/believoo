<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Mail\BconnectInvoiceMail;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Notification;
use App\Models\Setting;
use App\Services\BconnectInvoicePdfService;
use App\Services\BconnectSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Razorpay\Api\Api;

class BillingController extends Controller
{
    public static $plans;

    public function __construct()
    {
        self::$plans = BconnectSubscriptionService::$plans;
    }

    public function index(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $invoices = Invoice::where('company_id', $company->id)->latest()->paginate(20);
        $status = BconnectSubscriptionService::status($company);
        $days = BconnectSubscriptionService::daysUntilExpiry($company);
        return view('bconnect.billing', compact('company', 'invoices', 'status', 'days'));
    }

    public function upgrade(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        return view('bconnect.upgrade', ['company' => $company, 'plans' => BconnectSubscriptionService::$plans]);
    }

    public function processUpgrade(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $data = $r->validate([
            'plan' => 'required|in:pro,enterprise',
            'billing_cycle' => 'nullable|in:monthly,yearly',
        ]);

        $plan = $data['plan'];
        $cycle = $data['billing_cycle'] ?? 'monthly';

        if (!BconnectSubscriptionService::isPaidPlan($plan)) {
            return back()->with('error', 'Invalid plan selected.');
        }

        $price = BconnectSubscriptionService::planPrice($plan, $cycle);

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $r->input('bconnect_member')->id,
            'invoice_number' => 'BCU-' . strtoupper(uniqid()),
            'amount' => $price,
            'currency' => 'INR',
            'status' => 'pending',
            'due_at' => now()->addDays(7),
            'is_subscription' => true,
            'description' => "B-CONNECT {$cycle} plan upgrade to " . BconnectSubscriptionService::$plans[$plan]['name'],
            'metadata' => [
                'plan_upgrade' => $plan,
                'billing_cycle' => $cycle,
            ],
        ]);

        try {
            Mail::to($r->input('bconnect_member')->user->email)->send(new BconnectInvoiceMail($invoice, 'created'));
        } catch (\Throwable $e) {
            Log::warning('B-Connect invoice creation email failed: ' . $e->getMessage());
        }

        return redirect()->route('bconnect.billing.pay', $invoice->id)->with('info', 'Please complete payment to activate the plan.');
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
        $client = Member::with('user')->findOrFail($data['client_id']);

        $inv = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $data['client_id'],
            'invoice_number' => 'BCI-' . strtoupper(uniqid()),
            'amount' => $data['amount'],
            'currency' => 'INR',
            'status' => 'pending',
            'due_at' => now()->addDays(7),
            'description' => $data['description'],
        ]);

        try {
            if ($client->user?->email) {
                Mail::to($client->user->email)->send(new BconnectInvoiceMail($inv, 'created'));
            }
        } catch (\Throwable $e) {
            Log::warning('B-Connect invoice creation email failed: ' . $e->getMessage());
        }

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
            $inv->update(['metadata' => array_merge($inv->metadata ?? [], ['razorpay_order_id' => $order['id']])]);
            return view('bconnect.payment.razorpay', ['invoice' => $inv, 'order' => $order, 'key' => $settings['razorpay_key_id']]);
        }

        if (($settings['cashfree_enabled'] ?? '0') == '1' && !empty($settings['cashfree_app_id']) && !empty($settings['cashfree_secret_key'])) {
            $orderId = $inv->invoice_number . '-' . time();
            $inv->update(['metadata' => array_merge($inv->metadata ?? [], ['cashfree_order_id' => $orderId])]);

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

    public function downloadInvoice(Request $r, $invoice) {
        $inv = Invoice::where('id', $invoice)->where('company_id', $r->input('bconnect_company_id'))->firstOrFail();
        $pdf = BconnectInvoicePdfService::generate($inv);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-' . $inv->invoice_number . '.pdf"',
        ]);
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

            if ($inv->metadata['plan_upgrade'] ?? false) {
                BconnectSubscriptionService::activatePlan($inv->company, $inv->metadata['plan_upgrade'], $inv->metadata['billing_cycle'] ?? 'monthly', $inv->amount, $inv);
            } elseif ($inv->metadata['plan_renewal'] ?? false) {
                BconnectSubscriptionService::applyRenewalPayment($inv);
            }

            $this->sendPaidEmail($inv);
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

            if ($inv->metadata['plan_upgrade'] ?? false) {
                BconnectSubscriptionService::activatePlan($inv->company, $inv->metadata['plan_upgrade'], $inv->metadata['billing_cycle'] ?? 'monthly', $inv->amount, $inv);
            } elseif ($inv->metadata['plan_renewal'] ?? false) {
                BconnectSubscriptionService::applyRenewalPayment($inv);
            }

            $this->sendPaidEmail($inv);
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

        if ($inv->metadata['plan_upgrade'] ?? false) {
            BconnectSubscriptionService::activatePlan($inv->company, $inv->metadata['plan_upgrade'], $inv->metadata['billing_cycle'] ?? 'monthly', $inv->amount, $inv);
        } elseif ($inv->metadata['plan_renewal'] ?? false) {
            BconnectSubscriptionService::applyRenewalPayment($inv);
        }

        $this->sendPaidEmail($inv);
        $this->notify($inv, 'Invoice marked as paid manually');

        return redirect()->route('bconnect.billing')->with('success', 'Invoice marked as paid');
    }

    public function cancelSubscription(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $cancelled = BconnectSubscriptionService::cancel($company);
        return $cancelled
            ? back()->with('success', 'Subscription cancelled. Active until ' . ($company->plan_expires_at?->format('M d, Y') ?? 'expiry') . '.')
            : back()->with('error', 'No active paid subscription to cancel.');
    }

    public function renewSubscription(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $cycle = $r->input('billing_cycle', 'monthly');
        $invoice = BconnectSubscriptionService::createRenewalInvoice($company, $cycle);

        if (!$invoice) {
            return back()->with('error', 'Cannot renew this plan.');
        }

        return redirect()->route('bconnect.billing.pay', $invoice->id)->with('info', 'Renewal invoice created. Please complete payment.');
    }

    protected function sendPaidEmail(Invoice $inv): void
    {
        try {
            $emails = [];
            if ($inv->client && $inv->client->user && $inv->client->user->email) {
                $emails[] = $inv->client->user->email;
            }

            $admins = $inv->company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->with('user')->get();
            foreach ($admins as $admin) {
                if ($admin->user && $admin->user->email) {
                    $emails[] = $admin->user->email;
                }
            }

            foreach (array_unique($emails) as $email) {
                Mail::to($email)->send(new BconnectInvoiceMail($inv, 'paid'));
            }
        } catch (\Throwable $e) {
            Log::warning('B-Connect paid email failed: ' . $e->getMessage());
        }
    }

    protected function notify($invoice, $message) {
        $memberId = $invoice->client_id;
        if (!$memberId) {
            $admin = $invoice->company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->first();
            $memberId = $admin?->id ?? 0;
        }

        // Ensure member_id is valid to satisfy foreign key constraints.
        if ($memberId <= 0) {
            return;
        }

        Notification::create([
            'company_id' => $invoice->company_id,
            'member_id' => $memberId,
            'type' => 'billing',
            'title' => $message,
            'message' => 'Invoice ' . $invoice->invoice_number . ' paid ₹' . $invoice->amount,
            'url' => route('bconnect.billing'),
        ]);
    }
}
