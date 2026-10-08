<?php

namespace App\Console\Commands;

use App\Models\UserHosting;
use App\Notifications\HostingRenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendHostingRenewalReminders extends Command
{
    protected $signature = 'hosting:renewal-reminders
                            {--days=7 : Days before expiry to send reminder}';

    protected $description = 'Send hosting/service renewal reminders (once per threshold day)';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $this->info("Checking hostings expiring in {$days} days...");

        // Narrow window [days-1, days) so each threshold sends exactly once
        $from = now()->addDays($days - 1)->endOfDay();
        $to = now()->addDays($days)->endOfDay();

        $hostings = UserHosting::where('status', 'active')
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '>', $from)
            ->where('expiry_date', '<=', $to)
            ->with('user')
            ->get();

        $this->info("Found {$hostings->count()} hostings");

        foreach ($hostings as $hosting) {
            if (!$hosting->user) {
                continue;
            }

            try {
                $hosting->user->notify(new HostingRenewalReminder($hosting, $days));
                Log::info('Hosting renewal reminder sent', [
                    'hosting_id' => $hosting->id,
                    'user_id'    => $hosting->user_id,
                    'days'       => $days,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Hosting renewal reminder failed', [
                    'hosting_id' => $hosting->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        $this->info('Hosting renewal reminders completed!');

        return self::SUCCESS;
    }
}
