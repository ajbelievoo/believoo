<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class ProvisionExistingOrders extends Command
{
    protected $signature = 'orders:provision-existing {--order= : Specific order ID} {--all : All paid orders without hosting}';
    protected $description = 'Provision hosting for existing paid orders that dont have hosting yet';

    public function handle()
    {
        $orderId = $this->option('order');
        $all = $this->option('all');

        if ($orderId) {
            // Provision specific order
            $order = Order::find($orderId);
            if (!$order) {
                $this->error("Order #{$orderId} not found");
                return 1;
            }

            if ($order->status !== 'paid') {
                $this->error("Order #{$orderId} is not paid (status: {$order->status})");
                return 1;
            }

            $this->provisionOrder($order);
            return 0;
        }

        if ($all) {
            // Find all paid orders without hosting
            $orders = Order::where('status', 'paid')
                ->whereDoesntHave('hosting')
                ->get();

            if ($orders->isEmpty()) {
                $this->info("No paid orders without hosting found");
                return 0;
            }

            $this->info("Found {$orders->count()} paid orders without hosting");
            $bar = $this->output->createProgressBar($orders->count());

            foreach ($orders as $order) {
                $this->provisionOrder($order, true);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Done!");
            return 0;
        }

        $this->error("Use --order=ID for specific order or --all for all orders");
        return 1;
    }

    private function provisionOrder($order, bool $silent = false): void
    {
        try {
            $hosting = $order->provisionHosting();

            if ($hosting) {
                if (!$silent) {
                    $this->info("✅ Order #{$order->id}: Hosting #{$hosting->id} created successfully");
                }
            } else {
                if (!$silent) {
                    $this->error("❌ Order #{$order->id}: Failed to create hosting");
                }
            }
        } catch (\Exception $e) {
            if (!$silent) {
                $this->error("❌ Order #{$order->id}: Error - {$e->getMessage()}");
            }
        }
    }
}
