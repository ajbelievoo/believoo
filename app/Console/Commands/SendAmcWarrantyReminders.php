<?php

namespace App\Console\Commands;

use App\Models\Agreement;
use App\Models\AmcSubscription;
use App\Notifications\AmcWarrantyExpiringNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAmcWarrantyReminders extends Command
{
    protected $signature = 'amc:send-warranty-reminders';

    protected $description = 'Send AMC warranty expiration reminders to clients';

    public function handle(): int
    {
        $this->info('Sending AMC warranty reminders...');

        // Get agreements that are signed and have support_terms ending soon
        // Assuming 90-day warranty from signing date
        $reminderDays = [7, 3, 1]; // Send reminders at 7, 3, and 1 days before expiry

        foreach ($reminderDays as $daysBefore) {
            $targetDate = Carbon::now()->addDays($daysBefore)->startOfDay();
            
            // Find agreements where warranty expires in X days
            // Assuming warranty end date is stored or calculated from client_signed_at + 90 days
            $agreements = Agreement::where('status', 'signed')
                ->whereNotNull('client_signed_at')
                ->whereDate('client_signed_at', '=', $targetDate->subDays(83)) // 90 - 7 = 83 days after signing
                ->whereDoesntHave('amcSubscriptions', function ($query) {
                    $query->where('status', 'active')
                        ->where('end_date', '>', now());
                })
                ->get();

            foreach ($agreements as $agreement) {
                // Check if reminder already sent recently
                $recentReminder = AmcSubscription::where('agreement_id', $agreement->id)
                    ->where('last_reminder_sent', '>=', now()->subDays(1))
                    ->exists();

                if (!$recentReminder) {
                    try {
                        // Send notification
                        $agreement->client->notify(new AmcWarrantyExpiringNotification($agreement, $daysBefore));

                        // Create a pending AMC subscription record for tracking
                        AmcSubscription::create([
                            'agreement_id' => $agreement->id,
                            'client_id' => $agreement->client_id,
                            'plan_type' => 'standard',
                            'monthly_amount' => 200.00,
                            'start_date' => now(),
                            'end_date' => now()->addYear(),
                            'status' => 'pending',
                            'last_reminder_sent' => now(),
                            'reminder_count' => 1,
                        ]);

                        $this->info("Sent {$daysBefore}-day reminder to {$agreement->client->email} for project {$agreement->project_name}");
                    } catch (\Exception $e) {
                        Log::error('Failed to send AMC reminder', [
                            'agreement_id' => $agreement->id,
                            'error' => $e->getMessage(),
                        ]);
                        $this->error("Failed to send reminder to {$agreement->client->email}: {$e->getMessage()}");
                    }
                }
            }
        }

        // Also check existing AMC subscriptions expiring soon
        $expiringSubscriptions = AmcSubscription::where('status', 'active')
            ->where('end_date', '<=', now()->addDays(7))
            ->where('end_date', '>', now())
            ->where(function ($query) {
                $query->whereNull('last_reminder_sent')
                    ->orWhere('last_reminder_sent', '<', now()->subDays(3));
            })
            ->get();

        foreach ($expiringSubscriptions as $subscription) {
            $daysLeft = $subscription->daysUntilExpiry();
            
            try {
                $subscription->agreement->client->notify(new AmcWarrantyExpiringNotification(
                    $subscription->agreement,
                    $daysLeft,
                    $subscription->monthly_amount
                ));

                $subscription->update([
                    'last_reminder_sent' => now(),
                    'reminder_count' => $subscription->reminder_count + 1,
                ]);

                $this->info("Sent AMC renewal reminder to {$subscription->client->email}");
            } catch (\Exception $e) {
                Log::error('Failed to send AMC renewal reminder', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('AMC warranty reminders completed!');
        return self::SUCCESS;
    }
}
