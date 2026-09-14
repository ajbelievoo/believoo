<?php

namespace App\Console\Commands;

use App\Services\OvhApiService;
use Illuminate\Console\Command;

class TestOvhCredentials extends Command
{
    protected $signature = 'ovh:test-credentials';

    protected $description = 'Test OVHcloud API credentials and show account balance';

    public function handle(): int
    {
        $service = new OvhApiService();

        if (!$service->isEnabled()) {
            $this->error('OVH API is not configured.');
            $this->info('Go to Admin > Site Settings > OVH Reseller and add your credentials.');
            return self::FAILURE;
        }

        try {
            $this->info('Testing OVH API connection...');
            $me = $service->get('/me');

            $this->info('Connected successfully!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['NIC Handle', $me['nichandle'] ?? 'N/A'],
                    ['Name', ($me['firstname'] ?? '') . ' ' . ($me['name'] ?? '')],
                    ['Email', $me['email'] ?? 'N/A'],
                    ['Country', $me['country'] ?? 'N/A'],
                    ['Currency', $me['currency'] ?? 'N/A'],
                ]
            );

            $balance = $service->getAccountBalance();
            $this->info('Account balance: ' . number_format($balance['balance'], 2) . ' ' . ($balance['currency'] ?? 'EUR'));

            $means = $service->getAvailablePaymentMeans();
            if (!empty($means)) {
                $this->info('Available payment means: ' . implode(', ', $means));
            } else {
                $this->warn('No payment means found. Add a payment method or prepaid balance in OVH manager.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('OVH API connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
