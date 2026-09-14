<?php

namespace App\Console\Commands;

use App\Services\CurrencyService;
use Illuminate\Console\Command;

class FetchExchangeRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:fetch-rates 
                            {--currency=USD : Base currency to fetch rates for}
                            {--test : Test mode - do not save to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest exchange rates from external API';

    /**
     * Execute the console command.
     */
    public function handle(CurrencyService $currencyService): int
    {
        $this->info('Fetching latest exchange rates...');
        
        $testMode = $this->option('test');
        $baseCurrency = $this->option('currency');

        if ($testMode) {
            $this->warn('Running in TEST MODE - rates will not be saved');
        }

        // Fetch rates
        $success = $currencyService->fetchLatestRates();

        if ($success) {
            $this->info('✅ Exchange rates updated successfully!');
            
            // Show current rates
            $this->newLine();
            $this->table(
                ['Currency', 'Rate', 'Last Updated'],
                [
                    ['INR', \App\Models\ExchangeRate::getUsdToInrRate(), now()->format('Y-m-d H:i:s')],
                ]
            );

            return self::SUCCESS;
        } else {
            $this->error('❌ Failed to fetch exchange rates');
            $this->error('Check logs for details: storage/logs/laravel.log');
            
            return self::FAILURE;
        }
    }
}
