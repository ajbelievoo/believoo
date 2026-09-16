<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Models\OvhProduct;
use App\Models\Service;
use App\Models\VpsPlan;
use App\Services\OvhApiService;
use App\Services\OvhPricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOvhProducts extends Command
{
    protected $signature = 'ovh:sync-products
                            {--type=all : Product type to sync (all|vps|dedicated|hosting|domain|public_cloud|private_cloud|license)}
                            {--dry-run : Show what would change without saving}';

    protected $description = 'Sync OVHcloud product catalog into local pricing tables';

    protected const CATEGORIES = [
        'dedicated'     => 'DEDICATED',
        'hosting'       => 'WEB_HOSTING',
        'domain'        => 'DOMAINS',
        'public_cloud'  => 'PUBLIC_CLOUD',
        'private_cloud' => 'PRIVATE_CLOUD',
    ];

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
            if ($type === 'all' || $type === 'vps') {
                $this->syncVps($service, $dryRun);
            }

            if ($type === 'all') {
                foreach (self::CATEGORIES as $slug => $category) {
                    $this->syncOvhProductCategory($service, $category, $slug, $dryRun);
                }
            } elseif (isset(self::CATEGORIES[$type])) {
                $this->syncOvhProductCategory($service, self::CATEGORIES[$type], $type, $dryRun);
            } elseif ($type === 'license') {
                $this->warn('OVH license products are not exposed through the public catalog API for this account.');
            } elseif ($type !== 'all' && $type !== 'vps') {
                throw new \InvalidArgumentException("Unknown type: {$type}");
            }

            if ($type === 'all') {
                $this->info('Full OVH catalog sync complete.');
            }
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

        $pricing = new OvhPricingService();

        foreach ($plans as $plan) {
            $ovhMonthly = $plan['price_monthly'];
            $ovhCurrency = $plan['currency_code'] ?? 'EUR';

            if ($ovhMonthly === null) {
                $this->warn("Skipping {$plan['plan_code']}: no monthly price found.");
                continue;
            }

            $costInInr = $this->convertToInr($ovhMonthly, $ovhCurrency);
            $priced = $pricing->calculate($costInInr, 'vps', $plan['plan_code'], $commission);
            $saleInInr = $priced['sale_price'];

            $ovhConfig = [
                'plan_code'     => $plan['plan_code'],
                'duration'      => $plan['duration'],
                'pricing_mode'  => $plan['pricing_mode'],
                'ovh_currency'  => $ovhCurrency,
                'ovh_price'     => $ovhMonthly,
                'commission'    => $priced['margin_percent'],
                'pricing_rule_id' => $priced['rule_id'],
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
            } else {
                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    $payload['sort_order'] = VpsPlan::max('sort_order') + 1;
                    VpsPlan::create($payload);
                    $created++;
                }

                // Also keep a unified OvhProduct record so VPS shows up in the catalog checkout flow.
                $product = OvhProduct::updateOrCreate(
                    ['category' => 'VPS', 'plan_code' => $plan['plan_code']],
                    [
                        'family'         => 'vps',
                        'invoice_name'   => $plan['display_name'] ?? $plan['plan_code'],
                        'description'    => $plan['display_name'] ?? 'GHC Cloud VPS plan',
                        'cpu_cores'      => $plan['cpu_cores'] ?? 1,
                        'ram_gb'         => $plan['memory_gb'] ?? 1,
                        'disk_gb'        => $plan['disk_gb'] ?? 20,
                        'disk_type'      => $plan['disk_type'] ?? 'SSD',
                        'currency'       => 'INR',
                        'price_monthly'  => $saleInInr,
                        'cost_price'     => $priced['cost_price'],
                        'sale_price'     => $saleInInr,
                        'commission_percent' => $priced['margin_percent'],
                        'durations'      => $plan['raw']['durations'] ?? [['duration' => 'P1M', 'price' => $ovhMonthly]],
                        'ovh_config'     => $ovhConfig,
                        'is_active'      => true,
                    ]
                );

                $this->syncServiceForProduct($product);
            }

            $synced++;
        }

        $this->info("VPS sync complete: {$synced} plans processed ({$created} created, {$updated} updated).");
        Log::info('OVH VPS sync complete', ['synced' => $synced, 'created' => $created, 'updated' => $updated]);
    }

    protected function syncOvhProductCategory(OvhApiService $service, string $category, string $slug, bool $dryRun): void
    {
        $this->info("Fetching OVH {$category} catalog...");

        try {
            $plans = $service->getCatalogPlans($category);
        } catch (\Exception $e) {
            $this->warn("{$category} catalog fetch failed: " . $e->getMessage());
            Log::warning('OVH catalog fetch failed', ['category' => $category, 'error' => $e->getMessage()]);
            return;
        }

        if (empty($plans)) {
            $this->warn("No {$category} plans returned from OVH.");
            return;
        }

        $commission = (float) config('ovh.commission_percent', 25.0);
        $synced = 0;
        $created = 0;
        $updated = 0;

        $pricing = new OvhPricingService();

        foreach ($plans as $plan) {
            $planCode = $plan['plan_code'] ?? null;
            if (!$planCode) {
                continue;
            }

            $monthly = $this->extractMonthlyDurationPrice($plan['durations'] ?? []);

            if ($monthly['price'] === null) {
                $this->warn("Skipping {$planCode}: no monthly price found.");
                continue;
            }

            $costInInr = $this->convertToInr($monthly['price'], $monthly['currency'] ?? ($plan['currency'] ?? 'EUR'));
            $priced = $pricing->calculate($costInInr, $slug, $planCode, $commission);
            $saleInInr = $priced['sale_price'];

            $payload = [
                'category'          => $category,
                'family'            => $plan['family'] ?? $slug,
                'plan_code'         => $planCode,
                'invoice_name'      => $plan['invoice_name'] ?? $planCode,
                'description'       => $plan['description'] ?? null,
                'cpu_cores'         => $plan['cpu_cores'] ?? null,
                'ram_gb'            => $plan['ram_gb'] ?? null,
                'disk_gb'           => $plan['disk_gb'] ?? null,
                'disk_type'         => $plan['disk_type'] ?? null,
                'bandwidth_mbps'    => $plan['bandwidth_mbps'] ?? null,
                'currency'          => 'INR',
                'price_monthly'     => $saleInInr,
                'cost_price'        => $priced['cost_price'],
                'sale_price'        => $saleInInr,
                'commission_percent'=> $priced['margin_percent'],
                'durations'         => $plan['durations'] ?? [],
                'ovh_config'        => [
                    'plan_code'    => $planCode,
                    'ovh_currency' => $monthly['currency'] ?? ($plan['currency'] ?? 'EUR'),
                    'ovh_price'    => $monthly['price'],
                    'commission'   => $commission,
                    'raw'          => $plan,
                ],
                'is_active'         => $category !== 'PRIVATE_CLOUD' && ($category !== 'PUBLIC_CLOUD' || $saleInInr > 0),
                'sort_order'        => 0,
            ];

            $existing = OvhProduct::where('plan_code', $planCode)->first();

            if ($dryRun) {
                $this->info('[DRY-RUN] ' . ($existing ? 'Would update' : 'Would create') . ' [' . $category . '] ' . $planCode . ' @ ₹' . number_format($saleInInr, 2));
            } else {
                if ($existing) {
                    $existing->update($payload);
                    $product = $existing->refresh();
                    $updated++;
                } else {
                    $product = OvhProduct::create($payload);
                    $created++;
                }

                // Some catalog plan codes (e.g. legacy web-hosting-*-ovh or dedicated
                // SKUs that are out of stock in every region) are not accepted by the
                // OVH cart endpoint. Skip those from the storefront.
                if (in_array(strtoupper($category), ['WEB_HOSTING', 'DEDICATED']) && !$this->isPlanOrderable($service, $category, $planCode)) {
                    $this->warn("{$planCode} is not orderable; deactivating.");
                    $product->update(['is_active' => false]);
                    if ($product->service_id) {
                        Service::where('id', $product->service_id)->delete();
                        $product->update(['service_id' => null]);
                    }
                    $synced--;
                    continue;
                }

                $this->syncServiceForProduct($product);
            }

            $synced++;
        }

        $this->info("{$category} sync complete: {$synced} plans processed ({$created} created, {$updated} updated).");
        Log::info('OVH catalog sync complete', ['category' => $category, 'synced' => $synced, 'created' => $created, 'updated' => $updated]);
    }

    /**
     * Pick the monthly (1 month) raw price from a list of GHC plan durations.
     */
    protected function extractMonthlyDurationPrice(array $durations): array
    {
        foreach ($durations as $duration) {
            if (($duration['interval'] ?? 0) == 1 && ($duration['interval_unit'] ?? '') === 'month') {
                return [
                    'price'    => (float) ($duration['raw_price'] ?? 0),
                    'currency' => $duration['currency'] ?? 'EUR',
                ];
            }
        }

        if (!empty($durations[0])) {
            return [
                'price'    => (float) ($durations[0]['raw_price'] ?? 0),
                'currency' => $durations[0]['currency'] ?? 'EUR',
            ];
        }

        return ['price' => null, 'currency' => 'EUR'];
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

        $direct = ExchangeRate::getRate($currency, 'INR');
        if ($direct) {
            return $amount * $direct;
        }

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

    /**
     * Mirror an OvhProduct as a customer-facing Service so the existing
     * site checkout flow can sell it.
     */
    protected function syncServiceForProduct(OvhProduct $product): void
    {
        // Skip categories that are not directly orderable through the generic cart flow.
        // Domain registration is handled on the GHC portal (ghc.believoo.com/domain).
        if (in_array(strtoupper($product->category), ['PRIVATE_CLOUD', 'PUBLIC_CLOUD', 'DOMAINS', 'DOMAIN', 'LICENSE', 'IP_ADDON', 'CDN'])) {
            return;
        }

        $features = [];
        if ($product->cpu_cores) {
            $features[] = $product->cpu_cores . ' vCores';
        }
        if ($product->ram_gb) {
            $features[] = $product->ram_gb . ' GB RAM';
        }
        if ($product->disk_gb) {
            $features[] = $product->disk_gb . ' GB ' . ($product->disk_type ?? 'SSD');
        }
        if ($product->bandwidth_mbps) {
            $features[] = $product->bandwidth_mbps . ' Mbps';
        }

        $priceUsd = $this->convertToUsd((float) $product->price_monthly, 'INR');

        $serviceData = [
            'title'         => $product->display_name,
            'slug'          => $this->slugify($product->plan_code),
            'category'      => 'ovh_' . strtolower($product->category),
            'description'   => $product->description ?: 'OVH ' . $product->category_label . ' plan',
            'price'         => $priceUsd,
            'price_label'   => 'per month',
            'pricing_tiers' => [
                ['name' => 'default', 'price' => $priceUsd],
            ],
            'billing_cycles' => Service::getDefaultBillingCycles(),
            'features'      => $features,
            'is_active'     => $product->is_active,
        ];

        $service = Service::updateOrCreate(
            ['slug' => $serviceData['slug']],
            $serviceData
        );

        $product->update(['service_id' => $service->id]);
    }

    /**
     * Probe whether a plan code is accepted by the OVH cart endpoint.
     */
    protected function isPlanOrderable(OvhApiService $ovh, string $category, string $planCode): bool
    {
        return $ovh->isOrderable($category, $planCode, 'P1M', 1, null);
    }
}
