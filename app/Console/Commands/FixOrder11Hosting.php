<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\UserHosting;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FixOrder11Hosting extends Command
{
    protected $signature = 'fix:order11';
    protected $description = 'Manually create hosting for order #11';

    public function handle()
    {
        $order = Order::with('user')->find(11);

        if (!$order) {
            $this->error('Order #11 not found');
            return 1;
        }

        $this->info('Order #11 found:');
        $this->info('  Status: ' . $order->status);
        $this->info('  User: ' . ($order->user->name ?? 'N/A'));
        $this->info('  Service: ' . ($order->service->title ?? 'N/A'));
        $this->info('  Amount: ₹' . $order->amount);

        // Check if hosting already exists
        $existing = UserHosting::where('order_id', 11)->first();
        if ($existing) {
            $this->warn('Hosting already exists for this order: #' . $existing->id);
            $this->info('  Status: ' . $existing->status);
            return 0;
        }

        // Create hosting manually
        $rootPassword = $this->generatePassword();

        $hosting = UserHosting::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'service_id' => $order->service_id,
            'hosting_type' => 'vps',
            'plan_name' => $order->tier_name ?? 'VPS-3',
            'status' => 'active',
            'price' => $order->amount,
            'billing_cycle' => 'monthly',
            'start_date' => now(),
            'expiry_date' => now()->addMonth(),
            'primary_domain' => null,
            'cpu_cores' => 4,
            'ram_size' => 8,
            'storage_size' => 100,
            'os_name' => 'Ubuntu 22.04',
            'root_password' => $rootPassword,
            'datacenter_location' => 'Singapore',
            'server_ip' => '139.99.XX.XX', // Placeholder - will be updated when VM is created
            'server_hostname' => 'vps-' . $order->user_id . '-11.believoo.com',
            'admin_notes' => 'Manually created for order #11. VM provisioning pending.',
        ]);

        $this->newLine();
        $this->info('✅ Hosting created successfully!');
        $this->info('  Hosting ID: #' . $hosting->id);
        $this->info('  Status: ' . $hosting->status);
        $this->info('  Plan: ' . $hosting->plan_name);
        $this->info('  Root Password: ' . $rootPassword);
        $this->newLine();
        $this->info('Client dashboard mein ab hosting dikhni chahiye!');

        return 0;
    }

    private function generatePassword(): string
    {
        return Str::random(4) . '!' . Str::random(4) . '#' . Str::random(4);
    }
}
