<?php

namespace App\Console\Commands;

use App\Services\OvhApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Models\User;
use App\Notifications\AdminAlertNotification;

class CheckOvhBalance extends Command
{
    protected $signature = 'ovh:check-balance
                            {--threshold=50 : Low-balance warning threshold in account currency}';

    protected $description = 'Check upstream cloud account balance and alert admins if low';

    public function handle(): int
    {
        $service = new OvhApiService();

        if (!$service->isEnabled()) {
            $this->warn('OVH API is not configured. Skipping balance check.');
            return self::SUCCESS;
        }

        try {
            $balance = $service->getAccountBalance();
            $amount = $balance['balance'] ?? 0;
            $currency = $balance['currency'] ?? 'EUR';
            $threshold = (float) $this->option('threshold');

            $this->info("Cloud balance: {$amount} {$currency}");
            Log::info('Cloud balance check', $balance);

            // Persist balance in settings for dashboard display.
            \App\Models\Setting::updateOrCreate(
                ['key' => 'cloud_balance_amount'],
                ['value' => (string) $amount, 'group' => 'Cloud', 'type' => 'string']
            );
            \App\Models\Setting::updateOrCreate(
                ['key' => 'cloud_balance_currency'],
                ['value' => $currency, 'group' => 'Cloud', 'type' => 'string']
            );
            \App\Models\Setting::updateOrCreate(
                ['key' => 'cloud_balance_checked_at'],
                ['value' => now()->toDateTimeString(), 'group' => 'Cloud', 'type' => 'string']
            );

            if ($amount < $threshold) {
                $message = "Your GHC cloud wallet balance is low: {$amount} {$currency} (below {$threshold} {$currency}). Add funds to avoid order failures.";

                Log::warning($message);
                $this->warn($message);

                // Notify admins.
                $admins = User::where('is_admin', true)->get();
                if ($admins->isEmpty()) {
                    $admins = User::take(3)->get();
                }

                Notification::send($admins, new AdminAlertNotification(
                    title: 'Low GHC Cloud Wallet Balance',
                    message: $message,
                    actionUrl: url('/admin/settings'),
                    actionLabel: 'Top Up Cloud Wallet'
                ));
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Cloud balance check failed', ['error' => $e->getMessage()]);
            $this->error('Balance check failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
