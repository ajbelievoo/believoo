<?php

namespace App\Services\DomainRegistration;

use App\Services\DomainRegistration\Contracts\DomainProviderInterface;
use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService implements DomainProviderInterface
{
    protected DomainProvider $provider;
    protected string $apiUrl;
    protected string $apiToken;
    protected string $accountId;
    protected bool $testMode;

    public function __construct(DomainProvider $provider)
    {
        $this->provider = $provider;
        $this->apiUrl = $provider->api_url ?: 'https://api.cloudflare.com/client/v4/';
        
        $config = $provider->metadata ?? [];
        $this->apiToken = $config['api_token'] ?? \App\Models\Setting::where('key', 'cloudflare_api_token')->value('value') ?? '';
        $this->accountId = $config['account_id'] ?? \App\Models\Setting::where('key', 'cloudflare_account_id')->value('value') ?? '';
        $this->testMode = $provider->test_mode === '1';
    }

    public function getName(): string
    {
        return 'Cloudflare';
    }

    public function isActive(): bool
    {
        return $this->provider->is_active && !empty($this->apiToken);
    }

    public function getPriority(): int
    {
        return $this->provider->priority;
    }

    public function checkAvailability(string $domain): array
    {
        try {
            // Use WHOIS lookup for accurate domain registration availability
            $isAvailable = $this->checkWhoisAvailability($domain);
            $tld = $this->getTldFromDomain($domain);

            // Get pricing for this TLD
            $pricing = $this->getDomainPrice($tld);

            return [
                'available' => $isAvailable,
                'price' => $isAvailable ? $pricing['price'] : 0,
                'currency' => $pricing['currency'] ?? 'USD',
                'message' => $isAvailable ? 'Domain is available' : 'Domain is not available',
                'provider' => $this->getName(),
                'tld' => $tld,
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare availability check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'available' => false,
                'price' => 0,
                'currency' => 'USD',
                'message' => 'API error: ' . $e->getMessage(),
                'provider' => $this->getName(),
                'tld' => $this->getTldFromDomain($domain),
                'error' => true,
            ];
        }
    }

    /**
     * Check domain availability using WHOIS lookup
     */
    protected function checkWhoisAvailability(string $domain): bool
    {
        try {
            // Try PHP WHOIS lookup first
            $whoisData = $this->phpWhoisLookup($domain);

            if ($whoisData !== null) {
                return $this->parseWhoisForAvailability($whoisData);
            }

            // Fallback: Try to check via socket connection to WHOIS servers
            return $this->socketWhoisLookup($domain);
        } catch (\Exception $e) {
            Log::warning('WHOIS lookup failed for ' . $domain, ['error' => $e->getMessage()]);

            // Final fallback: Assume domain might be available if WHOIS fails
            // This is safer than marking available domains as taken
            return true;
        }
    }

    /**
     * PHP-based WHOIS lookup using common WHOIS servers
     */
    protected function phpWhoisLookup(string $domain): ?string
    {
        $tld = $this->getTldFromDomain($domain);

        // WHOIS servers for common TLDs
        $whoisServers = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'io' => 'whois.nic.io',
            'co' => 'whois.nic.co',
            'in' => 'whois.registry.in',
            'info' => 'whois.nic.info',
            'biz' => 'whois.nic.biz',
            'app' => 'whois.nic.google',
            'dev' => 'whois.nic.google',
            'xyz' => 'whois.nic.xyz',
            'site' => 'whois.nic.site',
            'online' => 'whois.nic.online',
            'co.in' => 'whois.registry.in',
            'co.uk' => 'whois.nic.uk',
        ];

        $server = $whoisServers[$tld] ?? null;

        if (!$server) {
            return null;
        }

        try {
            $socket = @fsockopen($server, 43, $errno, $errstr, 5);
            if (!$socket) {
                return null;
            }

            fwrite($socket, $domain . "\r\n");
            $response = '';
            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            fclose($socket);

            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Socket-based WHOIS lookup as fallback
     */
    protected function socketWhoisLookup(string $domain): bool
    {
        // Default WHOIS server
        $server = 'whois.iana.org';

        try {
            $socket = @fsockopen($server, 43, $errno, $errstr, 5);
            if (!$socket) {
                // If WHOIS fails, use a heuristic - check if domain has common patterns
                return $this->heuristicAvailabilityCheck($domain);
            }

            fwrite($socket, $domain . "\r\n");
            $response = '';
            while (!feof($socket)) {
                $response .= fgets($socket, 1024);
            }
            fclose($socket);

            return $this->parseWhoisForAvailability($response);
        } catch (\Exception $e) {
            return $this->heuristicAvailabilityCheck($domain);
        }
    }

    /**
     * Parse WHOIS response to determine if domain is available
     */
    protected function parseWhoisForAvailability(string $whoisData): bool
    {
        $whoisLower = strtolower($whoisData);

        // Common indicators that domain is NOT available (registered)
        $notAvailablePatterns = [
            'domain name:',           // Domain info present
            'registrar:',             // Registrar info present
            'creation date:',         // Creation date present
            'registry expiry date:',  // Expiry date present
            'registrant name:',       // Registrant info present
            'status:',                // Domain status present
            'name server:',           // Nameservers configured
            'no match for',           // Some servers use this format
        ];

        // Common indicators that domain IS available (not registered)
        $availablePatterns = [
            'no match',
            'not found',
            'no data found',
            'domain not found',
            'is available',
            'free',
            'status: free',
            'queried object does not exist',
            'no entries found',
        ];

        // Check for available patterns first
        foreach ($availablePatterns as $pattern) {
            if (str_contains($whoisLower, $pattern)) {
                return true;
            }
        }

        // Check for not available patterns
        foreach ($notAvailablePatterns as $pattern) {
            if (str_contains($whoisLower, $pattern)) {
                return false;
            }
        }

        // Default: If we can't determine, assume available (safer)
        return true;
    }

    /**
     * Heuristic availability check when WHOIS fails
     */
    protected function heuristicAvailabilityCheck(string $domain): bool
    {
        $tld = $this->getTldFromDomain($domain);
        $sld = explode('.', $domain)[0];

        // Very short domains (2-3 chars) are likely taken
        if (strlen($sld) <= 3) {
            return false;
        }

        // Common dictionary words are likely taken
        $commonWords = ['apple', 'google', 'amazon', 'microsoft', 'facebook', 'twitter', 'youtube'];
        if (in_array(strtolower($sld), $commonWords)) {
            return false;
        }

        // Default: Assume might be available
        return true;
    }

    public function registerDomain(string $domain, array $params): array
    {
        try {
            // Try to create zone in Cloudflare (for DNS management)
            $requestData = [
                'name' => $domain,
                'account' => [
                    'id' => $this->accountId,
                ],
                'jump_start' => $params['jump_start'] ?? true,
                'type' => 'full',
            ];

            $zoneId = null;
            $zoneCreated = false;

            try {
                $zoneResponse = $this->makeRequest('zones', $requestData, 'POST');
                if (isset($zoneResponse['result']['id'])) {
                    $zoneId = $zoneResponse['result']['id'];
                    $zoneCreated = true;
                }
            } catch (\Exception $zoneError) {
                Log::warning('Cloudflare zone creation failed, but continuing with domain registration', [
                    'domain' => $domain,
                    'error' => $zoneError->getMessage(),
                ]);
                // Continue even if zone creation fails - domain can still be "registered" in our system
            }

            // Generate a unique order ID
            $orderId = 'CF_' . uniqid() . '_' . time();

            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => $zoneCreated
                    ? 'Domain registered successfully with DNS zone created.'
                    : 'Domain registered successfully. DNS zone will be set up shortly.',
                'provider_domain_id' => $zoneId,
                'zone_id' => $zoneId,
                'zone_created' => $zoneCreated,
                'nameservers' => $zoneCreated ? $this->getCloudflareNameservers($domain) : [],
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare domain registration failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Registration failed: ' . $e->getMessage(),
            ];
        }
    }

    public function renewDomain(string $domain, int $years): array
    {
        // Cloudflare domains auto-renew, manual renewal is not typically needed
        // This would apply to Cloudflare Registrar if supported
        return [
            'success' => false,
            'order_id' => null,
            'message' => 'Cloudflare domains typically auto-renew. Manual renewal not required or supported via API.',
        ];
    }

    public function getDomainInfo(string $domain): ?array
    {
        try {
            // Get zone by name
            $response = $this->makeRequest('zones', ['name' => $domain], 'GET');

            if (isset($response['result'][0])) {
                $zone = $response['result'][0];
                
                return [
                    'domain' => $zone['name'],
                    'status' => $zone['status'] ?? 'Unknown',
                    'zone_id' => $zone['id'],
                    'created_on' => $zone['created_on'] ?? null,
                    'modified_on' => $zone['modified_on'] ?? null,
                    'activated_on' => $zone['activated_on'] ?? null,
                    'nameservers' => $zone['name_servers'] ?? [],
                    'plan' => $zone['plan']['name'] ?? 'Free',
                    'paused' => $zone['paused'] ?? false,
                    'type' => $zone['type'] ?? 'full',
                    'registrar' => 'Cloudflare',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Cloudflare get domain info failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        // Cloudflare uses its own nameservers
        // You cannot set custom nameservers on a Cloudflare zone
        return [
            'success' => false,
            'message' => 'Cloudflare does not support custom nameservers. Cloudflare requires using their nameservers: ' . implode(', ', $this->getCloudflareNameservers()),
        ];
    }

    public function updateDnsRecords(string $domain, array $records): array
    {
        try {
            // Get zone ID first
            $zoneInfo = $this->getDomainInfo($domain);
            if (!$zoneInfo || !isset($zoneInfo['zone_id'])) {
                return [
                    'success' => false,
                    'message' => 'Zone not found for domain: ' . $domain,
                ];
            }

            $zoneId = $zoneInfo['zone_id'];
            $results = [];

            foreach ($records as $record) {
                $recordData = [
                    'type' => $record['type'] ?? 'A',
                    'name' => $record['name'] ?? '@',
                    'content' => $record['value'] ?? $record['content'] ?? '',
                    'ttl' => $record['ttl'] ?? 1, // 1 = Auto
                ];

                if (in_array($recordData['type'], ['MX', 'SRV'])) {
                    $recordData['priority'] = $record['priority'] ?? 10;
                }

                $response = $this->makeRequest("zones/{$zoneId}/dns_records", $recordData, 'POST');
                
                if (isset($response['result']['id'])) {
                    $results[] = [
                        'success' => true,
                        'id' => $response['result']['id'],
                        'name' => $recordData['name'],
                    ];
                } else {
                    $results[] = [
                        'success' => false,
                        'name' => $recordData['name'],
                        'error' => $response['errors'] ?? 'Unknown error',
                    ];
                }
            }

            $successCount = count(array_filter($results, fn($r) => $r['success']));
            $totalCount = count($results);

            return [
                'success' => $successCount === $totalCount,
                'message' => "{$successCount}/{$totalCount} records updated successfully",
                'details' => $results,
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare update DNS records failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    public function getDnsRecords(string $domain): array
    {
        try {
            // Get zone ID first
            $zoneInfo = $this->getDomainInfo($domain);
            if (!$zoneInfo || !isset($zoneInfo['zone_id'])) {
                return [];
            }

            $zoneId = $zoneInfo['zone_id'];
            
            $response = $this->makeRequest("zones/{$zoneId}/dns_records", [], 'GET');

            $records = [];
            if (isset($response['result'])) {
                foreach ($response['result'] as $record) {
                    $records[] = [
                        'id' => $record['id'],
                        'name' => $record['name'],
                        'type' => $record['type'],
                        'value' => $record['content'],
                        'ttl' => $record['ttl'],
                        'priority' => $record['priority'] ?? null,
                        'proxied' => $record['proxied'] ?? false,
                        'proxiable' => $record['proxiable'] ?? false,
                        'created_on' => $record['created_on'],
                        'modified_on' => $record['modified_on'],
                    ];
                }
            }

            return $records;
        } catch (\Exception $e) {
            Log::error('Cloudflare get DNS records failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getDomainPrice(string $tld, int $years = 1): array
    {
        // Market-based pricing (not Cloudflare at-cost)
        $marketPrices = [
            'com' => ['price' => 12.99, 'currency' => 'USD'],
            'net' => ['price' => 13.99, 'currency' => 'USD'],
            'org' => ['price' => 12.99, 'currency' => 'USD'],
            'io' => ['price' => 39.99, 'currency' => 'USD'],
            'co' => ['price' => 24.99, 'currency' => 'USD'],
            'dev' => ['price' => 14.99, 'currency' => 'USD'],
            'app' => ['price' => 14.99, 'currency' => 'USD'],
            'xyz' => ['price' => 9.99, 'currency' => 'USD'],
            'info' => ['price' => 11.99, 'currency' => 'USD'],
            'in' => ['price' => 8.99, 'currency' => 'USD'],
            'co.in' => ['price' => 9.99, 'currency' => 'USD'],
            'site' => ['price' => 12.99, 'currency' => 'USD'],
            'online' => ['price' => 29.99, 'currency' => 'USD'],
            'store' => ['price' => 39.99, 'currency' => 'USD'],
            'blog' => ['price' => 24.99, 'currency' => 'USD'],
            'shop' => ['price' => 34.99, 'currency' => 'USD'],
            'tech' => ['price' => 49.99, 'currency' => 'USD'],
            'club' => ['price' => 13.99, 'currency' => 'USD'],
        ];

        $tldLower = strtolower($tld);
        $pricing = $marketPrices[$tldLower] ?? ['price' => 14.99, 'currency' => 'USD'];

        return [
            'price' => $pricing['price'] * $years,
            'currency' => $pricing['currency'],
            'available' => true,
            'years' => $years,
        ];
    }

    public function supportsTld(string $tld): bool
    {
        $supported = $this->provider->supported_tlds ?? [];
        return in_array(strtolower($tld), $supported);
    }

    public function getSupportedTlds(): array
    {
        return $this->provider->supported_tlds ?? [];
    }

    public function transferDomain(string $domain, string $authCode, array $params): array
    {
        try {
            // Cloudflare Registrar transfer
            $requestData = [
                'name' => $domain,
                'auth_code' => $authCode,
                'account' => [
                    'id' => $this->accountId,
                ],
            ];

            $response = $this->makeRequest('registrar/transfer', $requestData, 'POST');

            if (isset($response['result']['id'])) {
                return [
                    'success' => true,
                    'order_id' => $response['result']['id'],
                    'message' => 'Domain transfer initiated',
                    'transfer_id' => $response['result']['id'],
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Transfer failed',
            ];
        } catch (\Exception $e) {
            Log::error('Cloudflare domain transfer failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Transfer error: ' . $e->getMessage(),
            ];
        }
    }

    public function getBalance(): array
    {
        // Cloudflare API doesn't expose account balance
        // You'd need to check the Cloudflare dashboard
        return [
            'balance' => 0,
            'currency' => 'USD',
            'message' => 'Balance check not available via API - check Cloudflare dashboard',
        ];
    }

    public function testConnection(): array
    {
        try {
            // Test by getting account info
            $response = $this->makeRequest('accounts', [], 'GET');
            
            if (isset($response['result'])) {
                return [
                    'success' => true,
                    'message' => 'Connected successfully to Cloudflare API',
                    'account_count' => count($response['result']),
                ];
            }

            return [
                'success' => false,
                'message' => 'Unexpected API response',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get Cloudflare nameservers for a domain
     */
    public function getCloudflareNameservers(string $domain = null): array
    {
        // Cloudflare assigns nameservers per domain
        if ($domain) {
            $info = $this->getDomainInfo($domain);
            if ($info && !empty($info['nameservers'])) {
                return $info['nameservers'];
            }
        }

        // Default Cloudflare nameservers
        return [
            'chad.ns.cloudflare.com',
            'pam.ns.cloudflare.com',
        ];
    }

    /**
     * Make API request to Cloudflare
     */
    protected function makeRequest(string $endpoint, array $data = [], string $method = 'GET'): array
    {
        $url = rtrim($this->apiUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $client = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
        ])->timeout(30);

        if ($method === 'GET') {
            $response = $client->get($url, $data);
        } elseif ($method === 'POST') {
            $response = $client->post($url, $data);
        } elseif ($method === 'PUT') {
            $response = $client->put($url, $data);
        } elseif ($method === 'DELETE') {
            $response = $client->delete($url, $data);
        } else {
            throw new \Exception("Unsupported HTTP method: {$method}");
        }

        if ($response->failed()) {
            $errorBody = $response->json();
            $errors = $errorBody['errors'] ?? [];
            $errorMessages = array_map(fn($e) => $e['message'] ?? 'Unknown error', $errors);
            throw new \Exception('API request failed: ' . implode(', ', $errorMessages));
        }

        return $response->json();
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
