<?php

namespace App\Console\Commands;

use App\Mail\BconnectInvoiceMail;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Notification;
use App\Services\BconnectInvoicePdfService;
use App\Services\BconnectSubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BconnectSubscriptionLifecycle extends Command
{
    protected $signature = 'bconnect:subscription-lifecycle';
    protected $description = 'Renewal invoices, reminders, grace period and expiry handling for Bmydesk subscriptions';

    public function handle()
    {
        $now = now();

        // 1. Generate renewal invoices for active paid plans
        $companies = Company::whereNot('plan', 'free')
            ->whereNotNull('plan_expires_at')
            ->whereNotNull('next_invoice_at')
            ->where('next_invoice_at', '<=', $now)
            ->where('plan_expires_at', '>', $now)
            ->whereIn('subscription_status', ['active'])
            ->get();

        foreach ($companies as $company) {
            $existing = Invoice::where('company_id', $company->id)
                ->where('status', 'pending')
                ->whereJsonContains('metadata->plan_renewal', true)
                ->first();

            if ($existing) {
                $this->info("Renewal invoice already pending for company {$company->id}");
                $company->update(['next_invoice_at' => $company->plan_expires_at]);
                continue;
            }

            $lastCycle = 'monthly';
            $lastPaid = Invoice::where('company_id', $company->id)
                ->where('status', 'paid')
                ->whereNotNull('metadata')
                ->where(function ($q) {
                    $q->whereJsonContains('metadata->plan_renewal', true)
                      ->orWhereJsonContains('metadata->plan_upgrade', true);
                })
                ->latest('paid_at')
                ->first();

            if ($lastPaid && ($lastPaid->metadata['billing_cycle'] ?? 'monthly') === 'yearly') {
                $lastCycle = 'yearly';
            }

            $invoice = BconnectSubscriptionService::createRenewalInvoice($company, $lastCycle);

            if ($invoice) {
                // One final reminder at expiry, then let expiry logic handle downgrade.
                $company->update(['next_invoice_at' => $company->plan_expires_at]);

                try {
                    $admin = $company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->with('user')->first();
                    if ($admin && $admin->user?->email) {
                        Mail::to($admin->user->email)->send(new BconnectInvoiceMail($invoice, 'created'));
                    }
                    $invoice->update(['metadata' => array_merge($invoice->metadata ?? [], ['reminder_email_sent' => now()->toDateTimeString()])]);
                } catch (\Throwable $e) {
                    \Log::warning('Bmydesk renewal invoice email failed: ' . $e->getMessage());
                }

                $this->info("Created renewal invoice {$invoice->invoice_number} for company {$company->id}");
            }
        }

        // 2. Send payment reminders for pending invoices older than 24 hours
        $pendingInvoices = Invoice::where('status', 'pending')
            ->where('created_at', '<=', $now->copy()->subDay())
            ->get();

        foreach ($pendingInvoices as $invoice) {
            $meta = $invoice->metadata ?? [];
            $lastReminder = $meta['last_reminder_sent'] ?? null;

            // Remind once per day
            if ($lastReminder && \Carbon\Carbon::parse($lastReminder)->isSameDay($now)) {
                continue;
            }

            $recipient = $invoice->client?->user?->email;
            if (!$recipient) {
                $admin = $invoice->company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->with('user')->first();
                $recipient = $admin?->user?->email;
            }

            if (!$recipient) {
                continue;
            }

            try {
                Mail::to($recipient)->send(new BconnectInvoiceMail($invoice, 'reminder'));
                $invoice->update(['metadata' => array_merge($meta, ['last_reminder_sent' => $now->toDateTimeString()])]);
                $this->info("Reminder sent for invoice {$invoice->invoice_number}");
            } catch (\Throwable $e) {
                \Log::warning('Bmydesk invoice reminder failed: ' . $e->getMessage());
            }
        }

        // 3. Mark invoices overdue after 7 days
        Invoice::where('status', 'pending')
            ->where('created_at', '<=', $now->copy()->subDays(7))
            ->update(['status' => 'overdue']);

        // 4. Put expired paid plans into a 3-day grace period
        $expiringNow = Company::whereNot('plan', 'free')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<=', $now)
            ->whereNull('grace_period_until')
            ->where('subscription_status', '!=', 'free')
            ->get();

        foreach ($expiringNow as $company) {
            $company->update([
                'subscription_status' => 'grace-period',
                'grace_period_until' => $now->copy()->addDays(3),
            ]);

            $admin = $company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->first();
            if ($admin) {
                Notification::create([
                    'company_id' => $company->id,
                    'member_id' => $admin->id,
                    'type' => 'billing',
                    'title' => 'Plan expired — grace period started',
                    'message' => 'Your ' . ucfirst($company->plan) . ' plan expired. You have 3 days to renew before downgrade to Free.',
                    'url' => route('bconnect.billing'),
                ]);
            }

            $this->info("Grace period started for company {$company->id}");
        }

        // 5. Downgrade companies whose grace period is over
        $graceExpired = Company::whereNot('plan', 'free')
            ->whereNotNull('grace_period_until')
            ->where('grace_period_until', '<=', $now)
            ->get();

        foreach ($graceExpired as $company) {
            $oldPlan = $company->plan;

            $company->update([
                'plan' => 'free',
                'subscription_status' => 'free',
                'plan_expires_at' => null,
                'trial_ends_at' => null,
                'grace_period_until' => null,
                'next_invoice_at' => null,
            ]);

            $admin = $company->members()->whereIn('role', ['company_admin', 'super_admin'])->where('is_active', true)->first();
            if ($admin) {
                Notification::create([
                    'company_id' => $company->id,
                    'member_id' => $admin->id,
                    'type' => 'billing',
                    'title' => 'Plan downgraded',
                    'message' => "Your {$oldPlan} plan grace period ended. Workspace downgraded to Free.",
                    'url' => route('bconnect.billing'),
                ]);
            }

            $this->info("Downgraded company {$company->id} from {$oldPlan} to free");
        }

        $this->info('Bmydesk subscription lifecycle run complete.');

        return 0;
    }
}
