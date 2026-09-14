<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Notification;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Razorpay\Api\Api;

class BillingController extends Controller {
    protected $plans = [
        'free' => ['name' => 'Free', 'price' => 0, 'members' => 2, 'calls' => '1-on-1', 'remote' => false, 'ai' => false],
        'pro' => ['name' => 'Pro / Developer', 'price' => 1999, 'members' => 10, 'calls' => 'Unlimited', 'remote' => true, 'ai' => false],
        'enterprise' => ['name' => 'Enterprise / Business', 'price' => 9999, 'members' => null, 'calls' => 'Unlimited', 'remote' => true, 'ai' => true],
    ];

    public function index(Request $r) {
        $company = Company::findOrFail($r->input('bconnect_company_id'));
        $invoices = Invoice::where('company_id', $company->id)->latest()->paginate(20);
        return view('bconnect.billing', compact('company', 'invoices'));
    }

    public function storeInvoice(Request $r) {
        $data = $r->validate(['client_id' => 'required|exists:bconnect_members,id', 'amount' => 'required|numeric', 'description' => 'required']);
        $company = Company::findOrFail($r->input('bconnect_company_id'));
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

        if (($settings['razorpay_enabled'] ?? '0') == '1' && !empty($settings['razorpay_key_id'])) {
            $api = new Api($settings['razorpay_key_id'], $settings['razorpay_key_secret'] ?? '');
            $order = $api->order->create([
                'receipt' => $inv->invoice_number,
                'amount' => (int) ($inv->amount * 100),
                'currency' => strtoupper($inv->currency ?? 'INR'),
                'payment_capture' => 1,
            ]);
            $inv->update(['metadata' => ['razorpay_order_id' => $order['id']]]);
            return view('bconnect.payment.razorpay', ['invoice' => $inv, 'order' => $order, 'key' => $settings['razorpay_key_id']]);
        }

        if (($settings['cashfree_enabled'] ?? '0') == '1' && !empty($settings['cashfree_app_id'])) {
            $orderId = $inv->invoice_number . '-' . time();
            $inv->update(['metadata' => ['cashfree_order_id' => $orderId]]);
            $payload = [
                'orderId' => $orderId,
                'orderAmount' => $inv->amount,
                'orderCurrency' => strtoupper($inv->currency ?? 'INR'),
                'customerName' => Auth::user()?->name ?? 'Customer',
                'customerEmail' => Auth::user()?->email ?? 'customer@believoo.com',
                'customerPhone' => '9999999999',
                'notifyUrl' => url('/billing/callback/cashfree'),
                'returnUrl' => url('/billing/callback/cashfree'),
            ];
            return view('bconnect.payment.cashfree', ['invoice' => $inv, 'payload' => $payload, 'app_id' => $settings['cashfree_app_id']]);
        }

        return back()->with('error', 'No payment gateway configured. Go to Admin → Settings → Payment.');
    }

    public function razorpayCallback(Request $request) {
        $settings = Setting::pluck('value', 'key')->toArray();
        $api = new Api($settings['razorpay_key_id'], $settings['razorpay_key_secret'] ?? '');
        try {
            $api->utility->verifyPaymentSignature($request->all());
            $inv = Invoice::where('metadata->razorpay_order_id', $request->razorpay_order_id)->firstOrFail();
            $inv->update(['status' => 'paid', 'paid_at' => now()]);
            $this->notify($inv, 'Invoice paid via Razorpay');
            return redirect()->route('bconnect.billing')->with('success', 'Payment successful');
        } catch (\Exception $e) {
            return redirect()->route('bconnect.billing')->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    public function cashfreeCallback(Request $request) {
        $inv = Invoice::where('metadata->cashfree_order_id', $request->orderId)->first();
        if ($inv && $request->txStatus === 'SUCCESS') {
            $inv->update(['status' => 'paid', 'paid_at' => now()]);
            $this->notify($inv, 'Invoice paid via Cashfree');
            return redirect()->route('bconnect.billing')->with('success', 'Payment successful');
        }
        return redirect()->route('bconnect.billing')->with('error', 'Payment failed');
    }

    public function markPaid(Request $r, $invoice) {
        $inv = Invoice::where('id', $invoice)->where('company_id', $r->input('bconnect_company_id'))->firstOrFail();
        $role = session('bconnect_role');
        if ($role !== 'company_admin' && $role !== 'admin') {
            return back()->with('error', 'Only company admin can mark invoice as paid.');
        }
        if ($inv->status === 'paid') {
            return back()->with('info', 'Invoice already paid.');
        }
        $inv->update(['status' => 'paid', 'paid_at' => now(), 'metadata' => array_merge($inv->metadata ?? [], ['manual' => true])]);
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
