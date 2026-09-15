<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Models\VpsPlan;
use App\Services\OvhApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOvhProducts extends Command
{
    protected $signature = 'ovh:sync-products
                            {--type=vps : Product type to sync (vps|hosting|domain|license)}
                            {--dry-run : Show what would change without saving}';

    protected $description = 'Sync OVHcloud product catalog into local pricing tables';

    public function handle(): int
    {
        $service = new OvhApiService();

        if (!$service->isEnabled()) {
            $this->error('OVH API is not configured. Add credentials in Admin > Site Settings > OVH Reseller.');
            return self::FAILURE;
        }

        $type = $this->option('type');
        $dryRun = $this->option('dry-run');

        try {
            match ($type) {
                'vps'     => $this->syncVps($service, $dryRun),
                'hosting' => $this->warn('OVH hosting sync not implemented yet.'),
                'domain'  => $this->warn('OVH domain sync not implemented yet.'),
                'license' => $this->warn('OVH license sync not implemented yet.'),
                default   => throw new \InvalidArgumentException("Unknown type: {$type}"),
            };
        } catch (\Exception $e) {
            Log::error('OVH sync failed', ['type' => $type, 'error' => $e->getMessage()]);
            $this->error('Sync failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function syncVps(OvhApiService $service, bool $dryRun): void
    {
        $this->info('Fetching OVH VPS catalog...');
        $plans = $service->getVpsPlans();

        if (empty($plans)) {
            $this->warn('No VPS plans returned from OVH.');
            return;
        }

        $commission = (float) config('ovh.commission_percent', 25.0);
        $synced = 0;
        $created = 0;
        $updated = 0;

        foreach ($plans as $plan) {
            $ovhMonthly = $plan['price_monthly'];
            $ovhCurrency = $plan['currency_code'] ?? 'EUR';

            if ($ovhMonthly === null) {
                $this->warn("Skipping {$plan['plan_code']}: no monthly price found.");
                continue;
            }

            // Convert OVH cost to INR.
            $costInInr = $this->convertToInr($ovhMonthly, $ovhCurrency);

            // Apply commission markup.
            $saleInInr = $costInInr * (1 + $commission / 100);

            $ovhConfig = [
                'plan_code'     => $plan['plan_code'],
                'duration'      => $plan['duration'],
                'pricing_mode'  => $plan['pricing_mode'],
                'ovh_currency'  => $ovhCurrency,
                'ovh_price'     => $ovhMonthly,
                'commission'    => $commission,
                'raw_offer'     => $plan['raw'] ?? [],
            ];

            $existing = VpsPlan::where('ovh_plan_code', $plan['plan_code'])->first();

            $payload = [
                'name'          => $plan['plan_code'],
                'slug'          => $this->slugify($plan['plan_code']),
                'category'      => 'vps_2026',
                'display_name'  => $plan['display_name'],
                'description'   => 'GHC Cloud VPS plan synced via API',
                'cpu_cores'     => $plan['cpu_cores'] ?? 1,
                'memory_gb'     => $plan['memory_gb'] ?? 1,
                'disk_gb'       => $plan['disk_gb'] ?? 20,
                'disk_type'     => $plan['disk_type'] ?? 'SSD',
                'bandwidth'     => $plan['bandwidth'] ?? 'Unlimited',
                'unlimited_traffic' => true,
                'daily_backup'  => true,
                'installation_free' => true,
                'price_monthly' => round($saleInInr, 2),
                'cost_price'    => round($costInInr, 2),
                'base_price_usd'=> round($this->convertToUsd($costInInr, 'INR'), 2),
                'ovh_plan_code' => $plan['plan_code'],
                'ovh_config'    => $ovhConfig,
                'is_active'     => true,
                'is_sold_out'   => false,
                'features'      => [
                    ($plan['cpu_cores'] ?? 1) . ' vCores',
                    ($plan['memory_gb'] ?? 1) . ' GB RAM',
                    ($plan['disk_gb'] ?? 20) . ' GB ' . ($plan['disk_type'] ?? 'SSD'),
                    'Daily backup',
                    'Unlimited traffic',
                    $plan['bandwidth'] ?? 'Unlimited',
                ],
            ];

            if ($dryRun) {
                $this->info('[DRY-RUN] ' . ($existing ? 'Would update' : 'Would create') . ' ' . $plan['plan_code'] . ' @ ₹' . number_format($saleInInr, 2));
                continue;
            }

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                $payload['sort_order'] = VpsPlan::max('sort_order') + 1;
                VpsPlan::create($payload);
                $created++;
            }

            $synced++;
        }

        $this->info("VPS sync complete: {$synced} plans processed ({$created} created, {$updated} updated).");
        Log::info('OVH VPS sync complete', ['synced' => $synced, 'created' => $created, 'updated' => $updated]);
    }

    /**
     * Convert an OVH price into INR.
     */
    protected function convertToInr(float $amount, string $currency): float
    {
        $currency = strtoupper($currency);

        if ($currency === 'INR') {
            return $amount;
        }

        // Try direct rate first.
        $direct = ExchangeRate::getRate($currency, 'INR');
        if ($direct) {
            return $amount * $direct;
        }

        // Fallback: convert via USD.
        $toUsd = ExchangeRate::getRate($currency, 'USD') ?? $this->fallbackCrossRate($currency, 'USD');
        $usdToInr = ExchangeRate::getUsdToInrRate();

        return $amount * $toUsd * $usdToInr;
    }

    /**
     * Convert INR amount to USD.
     */
    protected function convertToUsd(float $amountInr, string $from): float
    {
        return round($amountInr / ExchangeRate::getUsdToInrRate(), 2);
    }

    /**
     * Rough fallback cross rates when no DB rate exists.
     */
    protected function fallbackCrossRate(string $from, string $to): float
    {
        $rates = [
            'EUR' => ['USD' => 1.08],
            'USD' => ['EUR' => 0.93],
            'GBP' => ['USD' => 1.27],
            'CAD' => ['USD' => 0.73],
        ];

        return $rates[$from][$to] ?? 1.0;
    }

    /**
     * Turn a plan code into a URL-safe slug.
     */
    protected function slugify(string $planCode): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $planCode));
    }
}
