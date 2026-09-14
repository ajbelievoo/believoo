<?php

namespace App\Services\DomainRegistration;

use App\Models\DomainProvider;
use App\Models\DomainPricing;
use App\Models\UserDomain;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DomainAggregatorService
{
    protected array $providers = [];
    protected float $profitMargin = 10.00; // Default profit margin in currency units

    public function __construct()
    {
        $this->loadProviders();
    }

    /**
     * Load all active providers
     */
    protected function loadProviders(): void
    {
        try {
            $providerModels = DomainProvider::where('is_active', true)
                ->orderBy('priority')
                ->get();

            foreach ($providerModels as $providerModel) {
                $className = $providerModel->class;
                
                if (class_exists($className)) {
                    $this->providers[$providerModel->code] = new $className($providerModel);
                    Log::info("Domain provider loaded: {$providerModel->name}");
                } else {
                    Log::error("Domain provider class not found: {$className}");
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to load domain providers', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Smart Search: Query all providers simultaneously
     */
    public function searchDomain(string $domain): array
    {
        $domain = strtolower(trim($domain));
        $tld = $this->getTldFromDomain($domain);
        
        $results = [];
        $promises = [];

        // Check each active provider
        foreach ($this->providers as $code => $provider) {
            if (!$provider->isActive()) {
                continue;
            }

            // Check if provider supports this TLD
            if (!$provider->supportsTld($tld)) {
                continue;
            }

            try {
                $availability = $provider->checkAvailability($domain);
                $sellingPrice = $this->calculateSellingPrice($availability['price'], $tld, $provider->getName());
                
                $results[] = [
                    'provider' => $provider->getName(),
                    'provider_code' => $code,
                    'available' => $availability['available'],
                    'provider_price' => $availability['price'],
                    'selling_price' => $sellingPrice,
                    'currency' => $availability['currency'],
                    'message' => $availability['message'],
                    'error' => $availability['error'] ?? false,
                    'tld' => $tld,
                ];
            } catch (\Exception $e) {
                Log::error("Provider {$provider->getName()} search failed", [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
                
                // Add error result for failover tracking
                $results[] = [
                    'provider' => $provider->getName(),
                    'provider_code' => $code,
                    'available' => false,
                    'provider_price' => 0,
                    'selling_price' => 0,
                    'currency' => 'USD',
                    'message' => 'API error: ' . $e->getMessage(),
                    'error' => true,
                    'tld' => $tld,
                ];
            }
        }

        // Sort by selling price (best price first)
        usort($results, fn($a, $b) => $a['selling_price'] <=> $b['selling_price']);

        // Determine best option
        $bestOption = null;
        $availableOptions = array_filter($results, fn($r) => $r['available'] && !($r['error'] ?? false));
        
        if (!empty($availableOptions)) {
            $bestOption = $availableOptions[0]; // First one is cheapest after sorting
        }

        return [
            'domain' => $domain,
            'tld' => $tld,
            'results' => $results,
            'best_option' => $bestOption,
            'is_available' => !empty($availableOptions),
            'total_providers_checked' => count($results),
            'providers_with_errors' => count(array_filter($results, fn($r) => $r['error'] ?? false)),
        ];
    }

    /**
     * Multi-domain search
     */
    public function searchDomains(array $domains): array
    {
        $results = [];
        
        foreach ($domains as $domain) {
            $results[$domain] = $this->searchDomain($domain);
        }
        
        return $results;
    }

    /**
     * Smart TLD Routing with Provider Selection
     * .in domains -> ResellerClub (best for Indian domains)
     * .com domains -> Cloudflare (best pricing & features)
     * Others -> Auto-select by price
     */
    public function selectBestProvider(string $domain, string $preferredProvider = null): ?array
    {
        $domain = strtolower(trim($domain));
        $tld = $this->getTldFromDomain($domain);
        
        $searchResults = $this->searchDomain($domain);
        
        if (!$searchResults['is_available']) {
            return null;
        }

        // === SMART TLD ROUTING ===
        // Route specific TLDs to preferred providers
        $tldRouting = [
            'in' => 'resellerclub',      // Indian domains -> ResellerClub
            'co.in' => 'resellerclub',
            'net.in' => 'resellerclub',
            'org.in' => 'resellerclub',
            'gen.in' => 'resellerclub',
            'firm.in' => 'resellerclub',
            'ind.in' => 'resellerclub',
            'com' => 'cloudflare',        // .com -> Cloudflare
            'net' => 'cloudflare',
            'org' => 'cloudflare',
            'io' => 'cloudflare',
            'dev' => 'cloudflare',
            'app' => 'cloudflare',
        ];

        // Determine preferred provider based on TLD routing
        if (!$preferredProvider && isset($tldRouting[$tld])) {
            $preferredProvider = $tldRouting[$tld];
            Log::info("Smart TLD routing applied", [
                'domain' => $domain,
                'tld' => $tld,
                'routed_to' => $preferredProvider,
            ]);
        }

        // If preferred provider is specified and available, use it
        if ($preferredProvider && isset($this->providers[$preferredProvider])) {
            $preferredResult = array_filter(
                $searchResults['results'], 
                fn($r) => $r['provider_code'] === $preferredProvider && $r['available']
            );
            
            if (!empty($preferredResult)) {
                return array_values($preferredResult)[0];
            }
            
            // Fallback if preferred provider doesn't have the domain
            Log::warning("Preferred provider unavailable, falling back to best option", [
                'domain' => $domain,
                'preferred' => $preferredProvider,
            ]);
        }

        // Return best option (already sorted by price)
        return $searchResults['best_option'];
    }

    /**
     * Register domain with automatic provider selection
     */
    public function registerDomain(string $domain, array $params, ?string $preferredProvider = null): array
    {
        $domain = strtolower(trim($domain));
        
        // Select best provider
        $providerInfo = $this->selectBestProvider($domain, $preferredProvider);
        
        if (!$providerInfo) {
            return [
                'success' => false,
                'message' => 'Domain not available or no provider supports this TLD',
                'domain' => $domain,
            ];
        }

        $providerCode = $providerInfo['provider_code'];
        $provider = $this->providers[$providerCode] ?? null;

        if (!$provider) {
            return [
                'success' => false,
                'message' => 'Selected provider not found',
                'domain' => $domain,
            ];
        }

        // === AUTO-SET DEFAULT NAMESERVERS ===
        // Set BelieVoo nameservers if not provided
        if (empty($params['nameservers'])) {
            $params['nameservers'] = [
                'ns1.believoo.com',
                'ns2.believoo.com',
            ];
            Log::info("Auto-set default BelieVoo nameservers", [
                'domain' => $domain,
                'nameservers' => $params['nameservers'],
            ]);
        }

        // Attempt registration
        $result = $provider->registerDomain($domain, $params);

        if ($result['success']) {
            // Save to database
            try {
                $tld = $this->getTldFromDomain($domain);
                $sld = explode('.', $domain)[0];
                
                UserDomain::create([
                    'user_id' => $params['user_id'] ?? auth()->id(),
                    'domain_provider_id' => DomainProvider::where('code', $providerCode)->first()?->id,
                    'domain_name' => $domain,
                    'tld' => $tld,
                    'sld' => $sld,
                    'registration_date' => now(),
                    'expiry_date' => now()->addYears($params['years'] ?? 1),
                    'registration_period' => $params['years'] ?? 1,
                    'provider_order_id' => $result['order_id'],
                    'status' => 'active',
                    'auto_renew' => $params['auto_renew'] ?? true,
                    'nameservers' => $params['nameservers'],
                    'use_believoo_dns' => true, // Mark as using BelieVoo DNS
                    'registrant_contact' => $params['registrant'] ?? null,
                    'admin_contact' => $params['admin'] ?? null,
                    'technical_contact' => $params['technical'] ?? null,
                    'billing_contact' => $params['billing'] ?? null,
                    'whois_privacy' => $params['whois_privacy'] ?? false,
                    'purchase_price' => $providerInfo['provider_price'],
                    'selling_price' => $providerInfo['selling_price'],
                    'profit_margin' => $providerInfo['selling_price'] - $providerInfo['provider_price'],
                    'currency' => $providerInfo['currency'],
                    'metadata' => [
                        'provider_response' => $result,
                        'search_info' => $providerInfo,
                        'auto_nameservers' => true,
                    ],
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to save domain to database', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the registration, but log the error
            }
        }

        return array_merge($result, [
            'domain' => $domain,
            'provider' => $provider->getName(),
            'provider_code' => $providerCode,
            'price_paid' => $providerInfo['selling_price'],
        ]);
    }

    /**
     * Register with failover (try next provider if first fails)
     */
    public function registerWithFailover(string $domain, array $params): array
    {
        $searchResults = $this->searchDomain($domain);
        
        if (!$searchResults['is_available']) {
            return [
                'success' => false,
                'message' => 'Domain not available',
                'domain' => $domain,
            ];
        }

        // Get available providers sorted by price
        $availableProviders = array_filter(
            $searchResults['results'],
            fn($r) => $r['available'] && !($r['error'] ?? false)
        );

        $lastError = null;

        foreach ($availableProviders as $providerInfo) {
            $providerCode = $providerInfo['provider_code'];
            $provider = $this->providers[$providerCode] ?? null;

            if (!$provider || !$provider->isActive()) {
                continue;
            }

            try {
                $result = $provider->registerDomain($domain, $params);

                if ($result['success']) {
                    // Save to database
                    $this->saveDomainToDatabase($domain, $providerCode, $providerInfo, $params, $result);

                    return array_merge($result, [
                        'domain' => $domain,
                        'provider' => $provider->getName(),
                        'provider_code' => $providerCode,
                        'price_paid' => $providerInfo['selling_price'],
                        'failover_used' => $providerInfo !== $availableProviders[0], // True if not first choice
                    ]);
                }

                $lastError = $result['message'];
                Log::warning("Provider {$provider->getName()} registration failed, trying next...", [
                    'domain' => $domain,
                    'error' => $result['message'],
                ]);

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::error("Provider {$provider->getName()} registration exception, trying next...", [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'success' => false,
            'message' => 'All providers failed. Last error: ' . ($lastError ?? 'Unknown error'),
            'domain' => $domain,
            'providers_tried' => count($availableProviders),
        ];
    }

    /**
     * Renew domain
     */
    public function renewDomain(string $domain, int $years = 1, int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        // Find domain in database
        $userDomain = UserDomain::where('domain_name', $domain)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->first();

        if (!$userDomain) {
            return [
                'success' => false,
                'message' => 'Domain not found or not active',
            ];
        }

        $providerCode = $userDomain->domainProvider?->code;
        $provider = $this->providers[$providerCode] ?? null;

        if (!$provider || !$provider->isActive()) {
            return [
                'success' => false,
                'message' => 'Provider not available',
            ];
        }

        $result = $provider->renewDomain($domain, $years);

        if ($result['success']) {
            // Update expiry date
            $userDomain->update([
                'expiry_date' => $userDomain->expiry_date->addYears($years),
                'registration_period' => $userDomain->registration_period + $years,
            ]);
        }

        return $result;
    }

    /**
     * Get user's domains (Unified Dashboard)
     */
    public function getUserDomains(int $userId): Collection
    {
        return UserDomain::where('user_id', $userId)
            ->with('domainProvider')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update domain DNS (routes to correct provider)
     */
    public function updateDomainDns(string $domain, array $records, int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        $userDomain = UserDomain::where('domain_name', $domain)
            ->where('user_id', $userId)
            ->first();

        if (!$userDomain) {
            return [
                'success' => false,
                'message' => 'Domain not found',
            ];
        }

        $providerCode = $userDomain->domainProvider?->code;
        $provider = $this->providers[$providerCode] ?? null;

        if (!$provider || !$provider->isActive()) {
            return [
                'success' => false,
                'message' => 'Provider not available',
            ];
        }

        return $provider->updateDnsRecords($domain, $records);
    }

    /**
     * Get domain DNS records
     */
    public function getDomainDns(string $domain, int $userId = null): array
    {
        $userId = $userId ?? auth()->id();
        
        $userDomain = UserDomain::where('domain_name', $domain)
            ->where('user_id', $userId)
            ->first();

        if (!$userDomain) {
            return [];
        }

        $providerCode = $userDomain->domainProvider?->code;
        $provider = $this->providers[$providerCode] ?? null;

        if (!$provider || !$provider->isActive()) {
            return [];
        }

        return $provider->getDnsRecords($domain);
    }

    /**
     * Get all active providers info
     */
    public function getProviders(): array
    {
        $result = [];
        
        foreach ($this->providers as $code => $provider) {
            $result[] = [
                'code' => $code,
                'name' => $provider->getName(),
                'active' => $provider->isActive(),
                'priority' => $provider->getPriority(),
                'supported_tlds' => $provider->getSupportedTlds(),
            ];
        }

        return $result;
    }

    /**
     * Test all provider connections
     */
    public function testAllConnections(): array
    {
        $results = [];

        foreach ($this->providers as $code => $provider) {
            try {
                $test = $provider->testConnection();
                $results[$code] = [
                    'name' => $provider->getName(),
                    'success' => $test['success'],
                    'message' => $test['message'],
                ];
            } catch (\Exception $e) {
                $results[$code] = [
                    'name' => $provider->getName(),
                    'success' => false,
                    'message' => 'Test failed: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Calculate selling price with profit margin
     */
    protected function calculateSellingPrice(float $providerPrice, string $tld, string $providerName): float
    {
        // Check for custom pricing in database
        $customPricing = DomainPricing::whereHas('domainProvider', function ($q) use ($providerName) {
                $q->where('name', $providerName);
            })
            ->where('tld', $tld)
            ->where('is_active', true)
            ->first();

        if ($customPricing) {
            return $customPricing->selling_register_price;
        }

        // Apply default profit margin
        return $providerPrice + $this->profitMargin;
    }

    /**
     * Save domain to database after registration
     */
    protected function saveDomainToDatabase(string $domain, string $providerCode, array $providerInfo, array $params, array $result): void
    {
        try {
            $tld = $this->getTldFromDomain($domain);
            $sld = explode('.', $domain)[0];
            
            UserDomain::create([
                'user_id' => $params['user_id'] ?? auth()->id(),
                'domain_provider_id' => DomainProvider::where('code', $providerCode)->first()?->id,
                'domain_name' => $domain,
                'tld' => $tld,
                'sld' => $sld,
                'registration_date' => now(),
                'expiry_date' => now()->addYears($params['years'] ?? 1),
                'registration_period' => $params['years'] ?? 1,
                'provider_order_id' => $result['order_id'],
                'status' => 'active',
                'auto_renew' => $params['auto_renew'] ?? true,
                'nameservers' => $params['nameservers'] ?? [],
                'registrant_contact' => $params['registrant'] ?? null,
                'admin_contact' => $params['admin'] ?? null,
                'technical_contact' => $params['technical'] ?? null,
                'billing_contact' => $params['billing'] ?? null,
                'whois_privacy' => $params['whois_privacy'] ?? false,
                'purchase_price' => $providerInfo['provider_price'],
                'selling_price' => $providerInfo['selling_price'],
                'profit_margin' => $providerInfo['selling_price'] - $providerInfo['provider_price'],
                'currency' => $providerInfo['currency'],
                'metadata' => [
                    'provider_response' => $result,
                    'search_info' => $providerInfo,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save domain to database', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get TLD from domain name
     */
    protected function getTldFromDomain(string $domain): string
    {
        $parts = explode('.', $domain);
        array_shift($parts); // Remove SLD
        return strtolower(implode('.', $parts));
    }
}
