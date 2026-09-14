<?php

namespace App\Console\Commands;

use App\Mail\ServiceSuspendedAlert;
use App\Models\User;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AutoSuspendOverdueUsers extends Command
{
    protected $signature = 'billing:auto-suspend';
    protected $description = 'Auto-suspend VPS for users with overdue payments (Hostinger style)';

    public function handle(BillingService $billingService): int
    {
        $this->info('Starting auto-suspension check...');
        Log::info('Billing: Auto-suspension cron job started');

        $gracePeriodDays = 2;
        $suspensionThreshold = Carbon::now()->subDays($gracePeriodDays);

        $overdueUsers = User::where('billing_status', 'active')
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<', $suspensionThreshold->toDateString())
            ->whereNotNull('vps_ids')
            ->where('vps_ids', '!=', '')
            ->where('vps_ids', '!=', '[]')
            ->where('vps_ids', '!=', 'null')
            ->get()
            ->filter(fn ($user) => !empty($user->getVpsIdsArray()));

        $this->info("Found {$overdueUsers->count()} users to suspend");
        Log::info("Billing: Found {$overdueUsers->count()} overdue users");

        $suspendedCount = 0;

        foreach ($overdueUsers as $user) {
            try {
                $this->info("Processing user: {$user->email}");
                Log::info("Billing: Suspending user {$user->id} - {$user->email}");

                $result = $billingService->suspendUserServers($user);

                if ($result['success']) {
                    $suspendedCount++;
                    $this->info("✓ Suspended servers for {$user->email}");
                    Log::info("Billing: Successfully suspended servers for user {$user->id}");

                    try {
                        Mail::to($user->email)->send(new ServiceSuspendedAlert($user));
                    } catch (\Exception $e) {
                        Log::error("Billing: Failed to send suspension alert to {$user->email}: " . $e->getMessage());
                    }
                } else {
                    $this->error("✗ Failed to suspend {$user->email}: {$result['message']}");
                    Log::error("Billing: Failed to suspend user {$user->id}: {$result['message']}");
                }
            } catch (\Exception $e) {
                $this->error("✗ Exception for {$user->email}: {$e->getMessage()}");
                Log::error("Billing: Exception during suspension", [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("\n========================================");
        $this->info("Auto-suspension completed!");
        $this->info("Total suspended: {$suspendedCount}/{$overdueUsers->count()}");
        $this->info("========================================");

        Log::info("Billing: Auto-suspension completed. {$suspendedCount}/{$overdueUsers->count()} users suspended");

        return self::SUCCESS;
    }
}
