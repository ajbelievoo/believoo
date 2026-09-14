<?php

namespace App\Services\DomainRegistration;

use App\Services\DomainRegistration\Contracts\DomainProviderInterface;
use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResellerClubService implements DomainProviderInterface
{
    protected DomainProvider $provider;
    protected string $apiUrl;
    protected string $authUserId;
    protected string $apiKey;
    protected bool $testMode;

    public function __construct(DomainProvider $provider)
    {
        $this->provider = $provider;
        $this->apiUrl = $provider->api_url ?: 'https://httpapi.com/api/';
        
        // Decrypt credentials
        $config = $provider->metadata ?? [];
        $this->authUserId = $config['auth_userid'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->testMode = $provider->test_mode === '1';
    }

    public function getName(): string
    {
        return 'ResellerClub';
    }

    public function isActive(): bool
    {
        return $this->provider->is_active && !empty($this->authUserId) && !empty($this->apiKey);
    }

    public function getPriority(): int
    {
        return $this->provider->priority;
    }

    public function checkAvailability(string $domain): array
    {
        try {
            $response = $this->makeRequest('domains/available.json', [
                'domain-name' => explode('.', $domain)[0] ?? $domain,
                'tlds' => $this->getTldFromDomain($domain),
            ]);

            if (isset($response[$domain])) {
                $result = $response[$domain];
                
                return [
                    'available' => $result['status'] === 'available',
                    'price' => $result['pricing']['addnewdomain'] ?? 0,
                    'currency' => 'USD',
                    'message' => $result['status'] === 'available' ? 'Domain is available' : 'Domain is not available',
                    'provider' => $this->getName(),
                    'tld' => $this->getTldFromDomain($domain),
                ];
            }

            return [
                'available' => false,
                'price' => 0,
                'currency' => 'USD',
                'message' => 'Unable to check availability',
                'provider' => $this->getName(),
                'tld' => $this->getTldFromDomain($domain),
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub availability check failed', [
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

    public function registerDomain(string $domain, array $params): array
    {
        try {
            $requestParams = [
                'domain-name' => $domain,
                'years' => $params['years'] ?? 1,
                'ns' => $params['nameservers'] ?? $this->provider->default_nameservers ?? ['ns1.believoo.com', 'ns2.believoo.com'],
                'customer-id' => $params['customer_id'] ?? null,
                'reg-contact-id' => $params['registrant_contact_id'] ?? null,
                'admin-contact-id' => $params['admin_contact_id'] ?? null,
                'tech-contact-id' => $params['technical_contact_id'] ?? null,
                'billing-contact-id' => $params['billing_contact_id'] ?? null,
                'invoice-option' => $params['invoice_option'] ?? 'PayInvoice',
                'protect-privacy' => $params['whois_privacy'] ?? false,
            ];

            $response = $this->makeRequest('domains/register.json', $requestParams);

            if (isset($response['entityid'])) {
                return [
                    'success' => true,
                    'order_id' => (string) $response['entityid'],
                    'message' => 'Domain registered successfully',
                    'provider_domain_id' => $response['entityid'],
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => $response['message'] ?? 'Registration failed',
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub domain registration failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Registration error: ' . $e->getMessage(),
            ];
        }
    }

    public function renewDomain(string $domain, int $years): array
    {
        try {
            $response = $this->makeRequest('domains/renew.json', [
                'domain-name' => $domain,
                'years' => $years,
                'exp-date' => $this->getDomainExpiryDate($domain),
                'invoice-option' => 'PayInvoice',
            ]);

            if (isset($response['entityid'])) {
                return [
                    'success' => true,
                    'order_id' => (string) $response['entityid'],
                    'message' => 'Domain renewed successfully',
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => $response['message'] ?? 'Renewal failed',
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub domain renewal failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Renewal error: ' . $e->getMessage(),
            ];
        }
    }

    public function getDomainInfo(string $domain): ?array
    {
        try {
            $response = $this->makeRequest('domains/details.json', [
                'domain-name' => $domain,
                'options' => 'All',
            ]);

            if (isset($response['domainname'])) {
                return [
                    'domain' => $response['domainname'],
                    'status' => $response['currentstatus'] ?? 'Unknown',
                    'expiry_date' => $response['endtime'] ?? null,
                    'registration_date' => $response['creationtime'] ?? null,
                    'nameservers' => $response['ns'] ?? [],
                    'contacts' => [
                        'registrant' => $response['contacts']['registrant'] ?? null,
                        'admin' => $response['contacts']['admin'] ?? null,
                        'technical' => $response['contacts']['tech'] ?? null,
                        'billing' => $response['contacts']['billing'] ?? null,
                    ],
                    'privacy_enabled' => $response['isprivacyprotected'] ?? false,
                    'auth_code' => $response['domsecret'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('ResellerClub get domain info failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        try {
            $response = $this->makeRequest('domains/modify-ns.json', [
                'domain-name' => $domain,
                'ns' => $nameservers,
            ]);

            return [
                'success' => $response['status'] === 'Success',
                'message' => $response['message'] ?? 'Nameservers updated',
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub update nameservers failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ];
        }
    }

    public function updateDnsRecords(string $domain, array $records): array
    {
        // ResellerClub uses a separate DNS service
        // This would require DNS service API integration
        return [
            'success' => false,
            'message' => 'DNS record management not implemented for ResellerClub. Use nameserver change instead.',
        ];
    }

    public function getDnsRecords(string $domain): array
    {
        return [];
    }

    public function getDomainPrice(string $tld, int $years = 1): array
    {
        try {
            // Fetch pricing from ResellerClub
            $response = $this->makeRequest('products/pricing.json', [
                'tld' => $tld,
            ]);

            if (isset($response[$tld])) {
                $pricing = $response[$tld];
                return [
                    'price' => $pricing['addnewdomain'] ?? 0,
                    'currency' => 'USD',
                    'available' => true,
                    'years' => $years,
                ];
            }

            return [
                'price' => 0,
                'currency' => 'USD',
                'available' => false,
                'years' => $years,
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub get price failed', [
                'tld' => $tld,
                'error' => $e->getMessage(),
            ]);

            return [
                'price' => 0,
                'currency' => 'USD',
                'available' => false,
                'years' => $years,
            ];
        }
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
            $requestParams = [
                'domain-name' => $domain,
                'auth-code' => $authCode,
                'customer-id' => $params['customer_id'] ?? null,
                'reg-contact-id' => $params['registrant_contact_id'] ?? null,
                'admin-contact-id' => $params['admin_contact_id'] ?? null,
                'tech-contact-id' => $params['technical_contact_id'] ?? null,
                'billing-contact-id' => $params['billing_contact_id'] ?? null,
                'invoice-option' => $params['invoice_option'] ?? 'PayInvoice',
            ];

            $response = $this->makeRequest('domains/transfer.json', $requestParams);

            if (isset($response['entityid'])) {
                return [
                    'success' => true,
                    'order_id' => (string) $response['entityid'],
                    'message' => 'Domain transfer initiated',
                    'provider_domain_id' => $response['entityid'],
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => $response['message'] ?? 'Transfer failed',
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub domain transfer failed', [
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
        try {
            $response = $this->makeRequest('billing/customer-balance.json', [
                'customer-id' => $this->authUserId,
            ]);

            return [
                'balance' => $response['sellingcurrencysymbol'] . $response['sellingcurrencybalance'] ?? 0,
                'currency' => $response['sellingcurrencysymbol'] ?? 'USD',
            ];
        } catch (\Exception $e) {
            Log::error('ResellerClub get balance failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'balance' => 0,
                'currency' => 'USD',
            ];
        }
    }

    public function testConnection(): array
    {
        try {
            $response = $this->makeRequest('billing/customer-balance.json', [
                'customer-id' => $this->authUserId,
            ]);

            return [
                'success' => true,
                'message' => 'Connected successfully to ResellerClub API',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request to ResellerClub
     */
    protected function makeRequest(string $endpoint, array $params = []): array
    {
        $url = rtrim($this->apiUrl, '/') . '/' . $endpoint;
        
        $queryParams = array_merge([
            'auth-userid' => $this->authUserId,
            'api-key' => $this->apiKey,
        ], $params);

        $response = Http::timeout(30)
            ->get($url, $queryParams);

        if ($response->failed()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['status']) && $data['status'] === 'ERROR') {
            throw new \Exception($data['message'] ?? 'API error');
        }

        return $data;
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

    /**
     * Get domain expiry date (helper for renewals)
     */
    protected function getDomainExpiryDate(string $domain): ?string
    {
        $info = $this->getDomainInfo($domain);
        return $info['expiry_date'] ?? null;
    }
}
