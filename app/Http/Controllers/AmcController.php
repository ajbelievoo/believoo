<?php

namespace App\Http\Controllers;

use App\Models\Agreement;
use App\Models\AmcSubscription;
use App\Models\AgreementHistory;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

class AmcController extends Controller
{
    public function showSubscribeForm(Agreement $agreement)
    {
        // Ensure user has access
        if ($agreement->client_id !== Auth::id()) {
            abort(403);
        }

        // Check if already has active AMC
        $existingSubscription = AmcSubscription::where('agreement_id', $agreement->id)
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->first();

        if ($existingSubscription) {
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('info', 'You already have an active AMC subscription for this project.');
        }

        return view('amc.subscribe', compact('agreement'));
    }

    public function subscribe(Request $request, Agreement $agreement)
    {
        // Ensure user has access
        if ($agreement->client_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'plan_type' => 'required|in:basic,standard,premium',
            'payment_method' => 'required|in:razorpay,easypaisa,jazzcash,bank_transfer',
        ]);

        $monthlyAmount = match ($request->plan_type) {
            'basic' => 150.00,
            'standard' => 200.00,
            'premium' => 350.00,
            default => 200.00,
        };

        // Create AMC subscription
        $subscription = AmcSubscription::create([
            'agreement_id' => $agreement->id,
            'client_id' => Auth::id(),
            'plan_type' => $request->plan_type,
            'monthly_amount' => $monthlyAmount,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'pending',
        ]);

        // If Razorpay selected, create order and show checkout
        if ($request->payment_method === 'razorpay') {
            $settings = Setting::where('group', 'General')->pluck('value', 'key');
            $razorpayKeyId = $settings['razorpay_key_id'] ?? '';
            $razorpayKeySecret = $settings['razorpay_key_secret'] ?? '';
            $razorpayEnabled = ($settings['razorpay_enabled'] ?? '0') === '1';

            if (!$razorpayEnabled || empty($razorpayKeyId) || empty($razorpayKeySecret)) {
                return back()->with('error', 'Razorpay payment is not configured.');
            }

            $amountInINR = $monthlyAmount * 83;
            $amountInPaise = $amountInINR * 100;

            $order = \App\Models\Order::create([
                'user_id' => Auth::id(),
                'service_id' => null,
                'service_name' => "AMC Subscription - {$subscription->plan_label}",
                'amount' => $amountInINR,
                'currency' => 'INR',
                'status' => 'pending',
                'payment_gateway' => 'razorpay',
                'notes' => "AMC Subscription - {$subscription->plan_label} for {$agreement->project_name}",
                'metadata' => [
                    'amc_subscription_id' => $subscription->id,
                    'type' => 'amc_subscription',
                    'first_month' => true,
                ],
            ]);

            try {
                $api = new Api($razorpayKeyId, $razorpayKeySecret);

                $razorpayOrder = $api->order->create([
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'receipt' => $order->order_number,
                    'notes' => [
                        'order_id' => $order->id,
                        'subscription_id' => $subscription->id,
                    ],
                ]);

                $order->update(['payment_id' => $razorpayOrder['id']]);

                $user = Auth::user();

                return view('payment.razorpay-amc', [
                    'key_id' => $razorpayKeyId,
                    'amount' => $amountInPaise,
                    'currency' => 'INR',
                    'order_id' => $razorpayOrder['id'],
                    'description' => "AMC Subscription - {$subscription->plan_label}",
                    'prefill_name' => $user->name,
                    'prefill_email' => $user->email,
                    'prefill_contact' => $user->phone ?? '',
                    'callback_url' => route('amc.razorpay.callback'),
                    'internal_order_id' => $order->id,
                    'agreement_id' => $agreement->id,
                ]);

            } catch (\Exception $e) {
                $order->update(['status' => 'failed', 'notes' => $e->getMessage()]);
                return back()->with('error', 'Failed to create payment. Please try again.');
            }
        }

        // For other payment methods, show upload proof form
        return view('amc.payment-proof', compact('subscription', 'agreement'));
    }

    public function submitPaymentProof(Request $request, AmcSubscription $subscription)
    {
        // Ensure user has access
        if ($subscription->client_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'payment_method' => 'required|in:easypaisa,jazzcash,bank_transfer',
            'transaction_id' => 'required|string|max:255',
            'screenshot' => 'required|image|max:5120',
            'notes' => 'nullable|string|max:500',
        ]);

        $screenshotPath = $request->file('screenshot')->store('amc-payment-proofs', 'public');

        // Update subscription with payment proof
        $subscription->update([
            'notes' => "Payment Method: {$request->payment_method}\nTransaction ID: {$request->transaction_id}\nNotes: {$request->notes}",
        ]);

        // Create payment proof record
        \App\Models\PaymentProof::create([
            'milestone_id' => null,
            'agreement_id' => $subscription->agreement_id,
            'user_id' => Auth::id(),
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'screenshot_path' => $screenshotPath,
            'notes' => "AMC Subscription Payment - {$subscription->subscription_number}\n{$request->notes}",
            'amount' => $subscription->monthly_amount,
            'status' => 'pending_verification',
        ]);

        // Notify admin (silently fail if email fails)
        try {
            $admin = \App\Models\User::where('is_admin', true)->first();
            if ($admin) {
                $admin->notify(new \App\Notifications\AmcPaymentProofSubmittedNotification($subscription));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send AMC payment proof notification: ' . $e->getMessage());
        }

        return redirect()->route('client.dashboard', ['tab' => 'agreements'])
            ->with('success', 'AMC subscription payment proof submitted! We will activate your subscription within 24 hours.');
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

            if (($order->metadata['type'] ?? null) !== 'amc_subscription') {
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

            $subscriptionId = $order->metadata['amc_subscription_id'] ?? null;
            $subscription = AmcSubscription::find($subscriptionId);

            if ($subscription) {
                // Activate subscription
                $subscription->update([
                    'status' => 'active',
                ]);

                // Add to history
                AgreementHistory::create([
                    'agreement_id' => $subscription->agreement_id,
                    'user_id' => Auth::id(),
                    'action' => 'amc_subscribed',
                    'description' => "AMC subscription activated: {$subscription->plan_label} - \${$subscription->monthly_amount}/month",
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                // Notify client (silently fail if email fails)
                try {
                    $subscription->client->notify(new \App\Notifications\AmcSubscribedNotification($subscription));
                } catch (\Exception $e) {
                    Log::error('Failed to send AMC client notification: ' . $e->getMessage());
                }

                // Notify admin (silently fail if email fails)
                try {
                    $admin = \App\Models\User::where('is_admin', true)->first();
                    if ($admin) {
                        $admin->notify(new \App\Notifications\AmcActivatedNotification($subscription));
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send AMC admin notification: ' . $e->getMessage());
                }

                return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                    ->with('success', 'AMC subscription activated successfully! Welcome to priority support.');
            }

            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('success', 'Payment successful!');

        } catch (\Exception $e) {
            Log::error('AMC Razorpay verification failed: ' . $e->getMessage());
            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }

    public function handlePaymentSuccess(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = \App\Models\Order::find($orderId);

        if (!$order || !isset($order->metadata['type']) || $order->metadata['type'] !== 'amc_subscription') {
            return redirect()->route('client.dashboard', ['tab' => 'agreements']);
        }

        $subscriptionId = $order->metadata['amc_subscription_id'] ?? null;
        $subscription = AmcSubscription::find($subscriptionId);

        if ($subscription && $order->status === 'completed') {
            // Activate subscription
            $subscription->update([
                'status' => 'active',
            ]);

            // Add to history
            AgreementHistory::create([
                'agreement_id' => $subscription->agreement_id,
                'user_id' => Auth::id(),
                'action' => 'amc_subscribed',
                'description' => "AMC subscription activated: {$subscription->plan_label} - \${$subscription->monthly_amount}/month",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Notify client
            $subscription->client->notify(new \App\Notifications\AmcSubscribedNotification($subscription));

            // Notify admin
            $admin = \App\Models\User::where('is_admin', true)->first();
            if ($admin) {
                $admin->notify(new \App\Notifications\AmcActivatedNotification($subscription));
            }

            return redirect()->route('client.dashboard', ['tab' => 'agreements'])
                ->with('success', 'AMC subscription activated successfully! Welcome to priority support.');
        }

        return redirect()->route('client.dashboard', ['tab' => 'agreements'])
            ->with('error', 'Payment verification failed. Please contact support.');
    }
}
