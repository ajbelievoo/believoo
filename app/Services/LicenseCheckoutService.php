<?php

namespace App\Services;

use App\Models\LicenseOrder;
use App\Models\ProxmoxVm;
use App\Models\User;
use App\Models\VpsLicense;
use App\Notifications\LicenseActivatedNotification;
use App\Notifications\LicensePaymentReceivedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LicenseCheckoutService
{
    /**
     * License type configurations
     */
    const LICENSE_TYPES = [
        'aapanel' => [
            'name' => 'aaPanel Professional',
            'description' => 'Lightweight control panel with PHP, MySQL, Nginx',
            'icon' => '🎛️',
            'price_monthly' => 199,
            'price_yearly' => 1990,
            'features' => ['Unlimited Sites', 'Free SSL', 'One-click Deploy', 'Resource Monitor'],
            'activation_url' => 'https://www.aapanel.com/api/activate',
        ],
        'cpanel' => [
            'name' => 'cPanel Solo',
            'description' => 'Industry standard for single account',
            'icon' => '🌐',
            'price_monthly' => 1499,
            'price_yearly' => 14990,
            'features' => ['1 Account', 'Softaculous', 'Email Hosting', 'DNS Management'],
            'activation_url' => 'https://store.cpanel.net/api/activate',
        ],
        'cpanel_plus' => [
            'name' => 'cPanel Admin',
            'description' => 'For small businesses (up to 5 accounts)',
            'icon' => '🌐',
            'price_monthly' => 2499,
            'price_yearly' => 24990,
            'features' => ['5 Accounts', 'Softaculous', 'Email Hosting', 'WHM Access'],
            'activation_url' => 'https://store.cpanel.net/api/activate',
        ],
        'cpanel_pro' => [
            'name' => 'cPanel Pro',
            'description' => 'For growing businesses (up to 30 accounts)',
            'icon' => '🌐',
            'price_monthly' => 3499,
            'price_yearly' => 34990,
            'features' => ['30 Accounts', 'Softaculous', 'Priority Support', 'WHM Access'],
            'activation_url' => 'https://store.cpanel.net/api/activate',
        ],
        'plesk' => [
            'name' => 'Plesk Web Admin',
            'description' => 'Complete web hosting platform',
            'icon' => '🔧',
            'price_monthly' => 1299,
            'price_yearly' => 12990,
            'features' => ['10 Domains', 'WordPress Toolkit', 'Email Security', 'Git Integration'],
            'activation_url' => 'https://store.plesk.com/api/activate',
        ],
        'imunify' => [
            'name' => 'Imunify360',
            'description' => 'Advanced security suite',
            'icon' => '🛡️',
            'price_monthly' => 599,
            'price_yearly' => 5990,
            'features' => ['Malware Scan', 'WAF', 'IDS/IPS', 'Auto-Cleanup'],
            'activation_url' => 'https://www.imunify360.com/api/activate',
        ],
        'kernelcare' => [
            'name' => 'KernelCare',
            'description' => 'Live kernel patching',
            'icon' => '⚡',
            'price_monthly' => 299,
            'price_yearly' => 2990,
            'features' => ['No Reboot', 'Auto-Patch', 'Security Fixes', 'Uptime Protection'],
            'activation_url' => 'https://kernelcare.com/api/activate',
        ],
    ];

    /**
     * Create a license order (before payment)
     */
    public function createOrder(User $user, ProxmoxVm $vm, string $licenseType, string $billingCycle): LicenseOrder
    {
        $licenseConfig = self::LICENSE_TYPES[$licenseType] ?? null;
        if (!$licenseConfig) {
            throw new \InvalidArgumentException("Invalid license type: {$licenseType}");
        }

        $price = $billingCycle === 'yearly' 
            ? $licenseConfig['price_yearly'] 
            : $licenseConfig['price_monthly'];

        $order = LicenseOrder::create([
            'user_id' => $user->id,
            'proxmox_vm_id' => $vm->id,
            'license_type' => $licenseType,
            'amount' => $price,
            'currency' => 'INR',
            'billing_cycle' => $billingCycle,
            'payment_status' => 'pending',
            'activation_status' => 'pending_payment',
            'server_ip' => $vm->ip_address,
            'server_hostname' => $vm->hostname,
        ]);

        Log::info('License order created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $user->id,
            'license_type' => $licenseType,
        ]);

        return $order;
    }

    /**
     * Process successful payment
     */
    public function processPayment(LicenseOrder $order, string $paymentMethod, string $paymentId, array $paymentResponse): void
    {
        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $paymentMethod,
            'payment_id' => $paymentId,
            'payment_response' => $paymentResponse,
            'paid_at' => now(),
            'activation_status' => 'ready_to_activate',
        ]);

        // Notify user
        $order->user->notify(new LicensePaymentReceivedNotification($order));

        // Auto-activate if configured
        $this->autoActivate($order);

        // Notify admins
        $this->notifyAdmins('payment', $order);

        Log::info('License payment processed', [
            'order_id' => $order->id,
            'payment_id' => $paymentId,
            'method' => $paymentMethod,
        ]);
    }

    /**
     * Activate license after payment
     */
    public function activateLicense(LicenseOrder $order): VpsLicense
    {
        if (!$order->isPaid()) {
            throw new \RuntimeException('Cannot activate unpaid license order');
        }

        $licenseConfig = self::LICENSE_TYPES[$order->license_type];
        
        // Generate license key
        $licenseKey = $this->generateLicenseKey($order->license_type);
        
        // Create VPS License record
        $license = VpsLicense::create([
            'user_id' => $order->user_id,
            'proxmox_vm_id' => $order->proxmox_vm_id,
            'type' => $order->license_type,
            'license_key' => encrypt($licenseKey),
            'activation_code' => $this->generateActivationCode(),
            'selling_price' => $order->amount,
            'billing_cycle' => $order->billing_cycle,
            'status' => 'active',
            'activated_at' => now(),
            'expires_at' => $order->billing_cycle === 'yearly' 
                ? now()->addYear() 
                : now()->addMonth(),
        ]);

        // Update order
        $order->update([
            'activation_status' => 'active',
            'activated_at' => now(),
            'vps_license_id' => $license->id,
        ]);

        // Install on VPS (async job)
        \App\Jobs\InstallLicenseJob::dispatch($license);

        // Notify user
        $order->user->notify(new LicenseActivatedNotification($license));

        Log::info('License activated', [
            'license_id' => $license->id,
            'order_id' => $order->id,
            'type' => $order->license_type,
        ]);

        return $license;
    }

    /**
     * Get all available license types
     */
    public function getAvailableLicenses(): array
    {
        return self::LICENSE_TYPES;
    }

    /**
     * Get license config by type
     */
    public function getLicenseConfig(string $type): ?array
    {
        return self::LICENSE_TYPES[$type] ?? null;
    }

    /**
     * Calculate prorated price for license upgrades
     */
    public function calculateUpgradePrice(LicenseOrder $currentOrder, string $newLicenseType): float
    {
        $currentConfig = self::LICENSE_TYPES[$currentOrder->license_type];
        $newConfig = self::LICENSE_TYPES[$newLicenseType];
        
        $currentPrice = $currentOrder->billing_cycle === 'yearly' 
            ? $currentConfig['price_yearly'] 
            : $currentConfig['price_monthly'];
        $newPrice = $currentOrder->billing_cycle === 'yearly' 
            ? $newConfig['price_yearly'] 
            : $newConfig['price_monthly'];

        // Calculate remaining days
        $daysRemaining = $currentOrder->expires_at->diffInDays(now());
        $totalDays = $currentOrder->billing_cycle === 'yearly' ? 365 : 30;
        
        $remainingValue = ($currentPrice / $totalDays) * $daysRemaining;
        
        return max(0, $newPrice - $remainingValue);
    }

    /**
     * Auto-activate if enabled
     */
    private function autoActivate(LicenseOrder $order): void
    {
        if (config('licenses.auto_activate', true)) {
            try {
                $this->activateLicense($order);
            } catch (\Exception $e) {
                Log::error('Auto-activation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                
                $order->update(['activation_status' => 'failed']);
            }
        }
    }

    /**
     * Notify admins of license events
     */
    private function notifyAdmins(string $event, LicenseOrder $order): void
    {
        $admins = \App\Models\User::where('is_admin', true)->get();
        
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\AdminLicenseNotification($event, $order));
        }
    }

    /**
     * Generate unique license key
     */
    private function generateLicenseKey(string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid()), 0, 8));
        
        return "BEL-{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Generate activation code
     */
    private function generateActivationCode(): string
    {
        return strtoupper(Str::random(16));
    }
}
