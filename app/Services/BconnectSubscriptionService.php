<?php

namespace App\Services;

use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Notification;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BconnectSubscriptionService
{
    public static $plans = [
        'free' => ['name' => 'Free', 'price' => 0, 'members' => 2, 'calls' => '1-on-1', 'remote' => false, 'ai' => false, 'recording' => false, 'branding' => false],
        'pro' => ['name' => 'Pro / Developer', 'price' => 1999, 'members' => 10, 'calls' => 'Unlimited', 'remote' => true, 'ai' => false, 'recording' => false, 'branding' => true],
        'enterprise' => ['name' => 'Enterprise / Business', 'price' => 9999, 'members' => null, 'calls' => 'Unlimited', 'remote' => true, 'ai' => true, 'recording' => true, 'branding' => true],
    ];

    public static function planPrice(string $plan, string $cycle = 'monthly'): float
    {
        $base = self::$plans[$plan]['price'] ?? 0;
        return $cycle === 'yearly' ? $base * 10 : $base;
    }

    public static function planMonths(string $cycle): int
    {
        return $cycle === 'yearly' ? 12 : 1;
    }

    public static function isPaidPlan(string $plan): bool
    {
        return in_array($plan, ['pro', 'enterprise']);
    }

    public static function status(Company $company): string
    {
        if ($company->plan === 'free') {
            return 'free';
        }

        if ($company->subscription_status === 'cancelled') {
            return $company->plan_expires_at && $company->plan_expires_at->isFuture()
                ? 'cancelled-active-until-expiry'
                : 'cancelled-expired';
        }

        if ($company->plan_expires_at && $company->plan_expires_at->isFuture()) {
            return 'active';
        }

        if ($company->grace_period_until && $company->grace_period_until->isFuture()) {
            return 'grace-period';
        }

        return 'expired';
    }

    public static function isActive(Company $company): bool
    {
        $status = self::status($company);
        return in_array($status, ['active', 'cancelled-active-until-expiry', 'grace-period', 'free']);
    }

    public static function daysUntilExpiry(Company $company): ?int
    {
        if (!$company->plan_expires_at) return null;
        return max(0, now()->diffInDays($company->plan_expires_at, false));
    }

    public static function activatePlan(Company $company, string $plan, string $cycle, ?float $amount = null, ?Invoice $invoice = null): Invoice
    {
        $months = self::planMonths($cycle);
        $price = $amount ?? self::planPrice($plan, $cycle);

        $existing = $company->plan_expires_at && $company->plan_expires_at->isFuture()
            ? $company->plan_expires_at->copy()
            : now();

        $newExpiry = $existing->addMonths($months)->endOfDay();
        $nextInvoice = $newExpiry->copy()->subDays(3);

        $company->update([
            'plan' => $plan,
            'subscription_status' => 'active',
            'plan_expires_at' => $newExpiry,
            'next_invoice_at' => $nextInvoice,
            'trial_ends_at' => null,
            'grace_period_until' => null,
        ]);

        if (!$invoice) {
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => null,
                'invoice_number' => 'BCU-' . strtoupper(uniqid()),
                'amount' => $price,
                'currency' => 'INR',
                'status' => 'paid',
                'paid_at' => now(),
                'due_at' => now(),
                'is_subscription' => true,
                'description' => "Bmydesk {$cycle} plan activation to " . self::$plans[$plan]['name'],
                'metadata' => [
                    'plan_upgrade' => $plan,
                    'billing_cycle' => $cycle,
                    'plan_expires_at' => $newExpiry->toDateTimeString(),
                ],
            ]);
        } else {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge($invoice->metadata ?? [], [
                    'plan_expires_at' => $newExpiry->toDateTimeString(),
                ]),
            ]);
            $company->invoices()->save($invoice);
        }

        self::notifyAdmins($company, 'Plan activated', "Your workspace is now on " . self::$plans[$plan]['name'] . " until " . $newExpiry->format('M d, Y') . ".");

        return $invoice;
    }

    public static function cancel(Company $company): bool
    {
        if ($company->plan === 'free' || $company->subscription_status !== 'active') {
            return false;
        }

        $company->update([
            'subscription_status' => 'cancelled',
            'next_invoice_at' => null,
        ]);

        self::notifyAdmins($company, 'Subscription cancelled', 'Your subscription is cancelled. Workspace will remain active until ' . ($company->plan_expires_at?->format('M d, Y') ?? 'expiry') . '.');

        return true;
    }

    public static function renew(Company $company, string $cycle = 'monthly', ?float $amount = null): Invoice
    {
        $plan = $company->plan;
        $price = $amount ?? self::planPrice($plan, $cycle);
        return self::activatePlan($company, $plan, $cycle, $price);
    }

    public static function createRenewalInvoice(Company $company, string $cycle = 'monthly'): ?Invoice
    {
        if ($company->plan === 'free' || !$company->plan_expires_at) {
            return null;
        }

        $plan = $company->plan;
        $price = self::planPrice($plan, $cycle);
        $newExpiry = $company->plan_expires_at->copy()->addMonths(self::planMonths($cycle))->endOfDay();

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $company->members()->whereIn('role', ['company_admin', 'super_admin'])->first()?->id,
            'invoice_number' => 'BCR-' . strtoupper(Str::random(8)),
            'amount' => $price,
            'currency' => 'INR',
            'status' => 'pending',
            'due_at' => now()->addDays(7),
            'is_subscription' => true,
            'description' => "Bmydesk {$cycle} plan renewal to " . self::$plans[$plan]['name'],
            'metadata' => [
                'plan_renewal' => true,
                'plan' => $plan,
                'billing_cycle' => $cycle,
                'plan_expires_at' => $newExpiry->toDateTimeString(),
            ],
        ]);

        $company->update([
            'next_invoice_at' => $newExpiry->copy()->subDays(3),
        ]);

        self::notifyAdmins($company, 'Renewal invoice ready', "Invoice {$invoice->invoice_number} of ₹{$price} is due for renewal.");

        return $invoice;
    }

    public static function applyRenewalPayment(Invoice $invoice): void
    {
        $company = $invoice->company;
        if (!$company || !($invoice->metadata['plan_renewal'] ?? false)) {
            return;
        }

        $plan = $invoice->metadata['plan'] ?? $company->plan;
        $cycle = $invoice->metadata['billing_cycle'] ?? 'monthly';

        $months = self::planMonths($cycle);
        $existing = $company->plan_expires_at && $company->plan_expires_at->isFuture()
            ? $company->plan_expires_at->copy()
            : now();
        $newExpiry = $existing->addMonths($months)->endOfDay();

        $company->update([
            'plan' => $plan,
            'subscription_status' => 'active',
            'plan_expires_at' => $newExpiry,
            'next_invoice_at' => $newExpiry->copy()->subDays(3),
            'grace_period_until' => null,
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'metadata' => array_merge($invoice->metadata ?? [], [
                'renewed_plan_expires_at' => $newExpiry->toDateTimeString(),
            ]),
        ]);

        self::notifyAdmins($company, 'Plan renewed', 'Your subscription has been renewed until ' . $newExpiry->format('M d, Y') . '.');
    }

    public static function notifyAdmins(Company $company, string $title, string $message): void
    {
        $admins = $company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->get();

        foreach ($admins as $admin) {
            Notification::create([
                'company_id' => $company->id,
                'member_id' => $admin->id,
                'type' => 'billing',
                'title' => $title,
                'message' => $message,
                'url' => route('bconnect.billing'),
            ]);
        }
    }
}
