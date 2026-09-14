<?php

namespace App\Console\Commands;

use App\Models\VpsLicense;
use App\Services\AlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLicenseExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'licenses:reminders
                            {--days=7 : Days before expiry to send reminder}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send license expiry reminders';

    /**
     * Execute the console command.
     */
    public function handle(AlertService $alertService): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Checking for licenses expiring in {$days} days...");
        
        // Find licenses expiring soon
        $expiringLicenses = VpsLicense::where('status', 'active')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>=', now())
            ->with(['user', 'proxmoxVm'])
            ->get();
        
        $count = $expiringLicenses->count();
        $this->info("Found {$count} licenses expiring soon");
        
        foreach ($expiringLicenses as $license) {
            // Notify user
            $license->user->notify(new \App\Notifications\LicenseExpiringNotification($license));
            
            // Notify admins
            $alertService->sendCriticalAlert(
                'license_expiring',
                "License #{$license->id} expires in {$days} days",
                [
                    'License Type' => $license->type,
                    'Customer' => $license->user->name,
                    'Expires At' => $license->expires_at->format('Y-m-d'),
                ]
            );
            
            Log::info('License expiry reminder sent', [
                'license_id' => $license->id,
                'user_id' => $license->user_id,
                'expires_at' => $license->expires_at,
            ]);
        }
        
        $this->info('License reminders completed!');
        
        return self::SUCCESS;
    }
}
