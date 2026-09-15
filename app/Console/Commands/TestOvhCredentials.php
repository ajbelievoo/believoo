<?php

namespace App\Console\Commands;

use App\Services\OvhApiService;
use Illuminate\Console\Command;

class TestOvhCredentials extends Command
{
    protected $signature = 'ovh:test-credentials';

    protected $description = 'Test cloud provider API credentials and show account balance';

    public function handle(): int
    {
        $service = new OvhApiService();

        if (!$service->isEnabled()) {
            $this->error('Cloud API is not configured.');
            $this->info('Go to Admin > Settings > Cloud Reseller and add your credentials.');
            return self::FAILURE;
        }

        try {
            $this->info('Testing cloud API connection...');
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
                $this->warn('No payment means found. Add a payment method or prepaid balance in the cloud provider manager.');
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cloud API connection failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
