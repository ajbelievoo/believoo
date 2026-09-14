<?php

namespace App\Console\Commands;

use App\Mail\LowBalanceWarning;
use App\Mail\WalletDebitReceipt;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BillingService;
use App\Services\WalletService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoRenewWalletSubscriptions extends Command
{
    protected $signature = 'billing:auto-renew';
    protected $description = 'Auto-renew subscriptions via wallet deduction and send email alerts';

    public function handle(WalletService $walletService, BillingService $billingService): int
    {
        $this->info('Starting wallet auto-renewal...');
        Log::info('Billing: Auto-renewal cron started');

        $renewalWindow = Carbon::now()->addDays(3);
        $gracePeriodDays = 2;

        $users = User::where('auto_renew', true)
            ->whereNotNull('next_due_date')
            ->whereNotNull('plan_price')
            ->where('plan_price', '>', 0)
            ->where('next_due_date', '<=', $renewalWindow)
            ->where('billing_status', '!=', 'cancelled')
            ->get();

        $this->info("Found {$users->count()} users in renewal window");
        Log::info("Billing: Found {$users->count()} users in renewal window");

        $renewedCount = 0;
        $warnedCount = 0;
        $failedCount = 0;

        foreach ($users as $user) {
            $daysUntilDue = Carbon::now()->startOfDay()->diffInDays(
                Carbon::parse($user->next_due_date)->startOfDay(), false
            );

            $this->info("User {$user->email} - Due in {$daysUntilDue} days - Balance: {$user->wallet_balance} - Price: {$user->plan_price}");

            // 1. Low balance warning (3 days before due date)
            if ($daysUntilDue > 0 && $daysUntilDue <= 3) {
                if (!$walletService->canDebit($user, (float) $user->plan_price)) {
                    try {
                        Mail::to($user->email)->send(new LowBalanceWarning($user, (float) $user->plan_price));
                        $warnedCount++;
                        $this->warn("Sent low balance warning to {$user->email}");
                        Log::info("Billing: Low balance warning sent to user {$user->id}");
                    } catch (\Exception $e) {
                        Log::error("Billing: Failed to send low balance warning to {$user->email}: " . $e->getMessage());
                    }
                    continue;
                }
            }

            // 2. Attempt auto-renewal (on or past due date)
            if ($daysUntilDue <= 0) {
                try {
                    $walletService->debit(
                        $user,
                        (float) $user->plan_price,
                        'auto_renew',
                        'Auto-renewal for ' . ($user->current_plan_name ?? 'Standard Plan'),
                        ['reference' => 'AUTO-RENEW-' . now()->format('YmdHis')]
                    );

                    // Extend due date by 1 month from current next_due_date
                    $newDueDate = Carbon::parse($user->next_due_date)->addMonth();
                    $wasSuspended = $user->billing_status === 'suspended';

                    $user->update([
                        'next_due_date' => $newDueDate,
                        'last_payment_date' => Carbon::now(),
                        'billing_status' => 'active',
                    ]);

                    // Create paid invoice
                    $invoice = Invoice::create([
                        'user_id' => $user->id,
                        'invoice_number' => 'BEL-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                        'order_id' => 'REN-' . strtoupper(uniqid()),
                        'invoice_type' => 'subscription',
                        'description' => 'Auto-renewal for ' . ($user->current_plan_name ?? 'Standard Plan'),
                        'amount' => $user->plan_price,
                        'total_amount' => $user->plan_price,
                        'currency' => 'INR',
                        'status' => 'paid',
                        'payment_gateway' => 'wallet',
                        'paid_at' => now(),
                        'due_date' => $newDueDate,
                    ]);

                    // Unsuspend if previously suspended
                    if ($wasSuspended) {
                        $unsuspendResult = $billingService->unsuspendUserServers($user);
                        Log::info("Billing: Unsuspend result for user {$user->id}", $unsuspendResult);
                    }

                    // Send receipt email
                    try {
                        Mail::to($user->email)->send(new WalletDebitReceipt($user, $invoice));
                    } catch (\Exception $e) {
                        Log::error("Billing: Failed to send debit receipt to {$user->email}: " . $e->getMessage());
                    }

                    $renewedCount++;
                    $this->info("Renewed {$user->email} - New due date: {$newDueDate->format('Y-m-d')}");
                    Log::info("Billing: Auto-renewal successful for user {$user->id}");
                } catch (\InvalidArgumentException $e) {
                    $this->warn("Insufficient balance for {$user->email}");
                    Log::warning("Billing: Auto-renewal failed for user {$user->id} - Insufficient balance");

                    if ($daysUntilDue < -$gracePeriodDays) {
                        Log::info("Billing: User {$user->id} is past grace period, suspension will be handled by auto-suspend command");
                    }

                    $failedCount++;
                } catch (\Exception $e) {
                    $this->error("Error renewing {$user->email}: " . $e->getMessage());
                    Log::error("Billing: Auto-renewal exception for user {$user->id}: " . $e->getMessage());
                    $failedCount++;
                }
            }
        }

        $this->info("\n========================================");
        $this->info("Auto-renewal completed!");
        $this->info("Renewed: {$renewedCount} | Warned: {$warnedCount} | Failed: {$failedCount}");
        $this->info("========================================");

        Log::info("Billing: Auto-renewal completed. Renewed: {$renewedCount}, Warned: {$warnedCount}, Failed: {$failedCount}");

        return self::SUCCESS;
    }
}
