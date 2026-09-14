<?php

namespace App\Services\DomainRegistration;

use App\Services\DomainRegistration\Contracts\DomainProviderInterface;
use App\Models\DomainProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class NamecheapService implements DomainProviderInterface
{
    protected DomainProvider $provider;
    protected string $apiUrl;
    protected string $apiKey;
    protected string $username;
    protected string $clientIp;
    protected bool $testMode;

    public function __construct(DomainProvider $provider)
    {
        $this->provider = $provider;
        $this->apiUrl = $provider->api_url ?: 'https://api.namecheap.com/xml.response';
        
        $config = $provider->metadata ?? [];
        $this->apiKey = $config['api_key'] ?? '';
        $this->username = $config['username'] ?? '';
        $this->clientIp = $config['client_ip'] ?? request()->ip();
        $this->testMode = $provider->test_mode === '1';
    }

    public function getName(): string
    {
        return 'Namecheap';
    }

    public function isActive(): bool
    {
        return $this->provider->is_active && !empty($this->apiKey) && !empty($this->username);
    }

    public function getPriority(): int
    {
        return $this->provider->priority;
    }

    public function checkAvailability(string $domain): array
    {
        try {
            $response = $this->makeRequest('namecheap.domains.check', [
                'DomainList' => $domain,
            ]);

            $domainCheckResult = $response->CommandResponse->DomainCheckResult ?? null;

            if ($domainCheckResult) {
                $available = (string) $domainCheckResult->Available === 'true';
                $price = 0;
                
                // Get price if available
                if ($available && isset($domainCheckResult->PremiumPricing)) {
                    $price = (float) $domainCheckResult->PremiumRegistrationPrice;
                }

                return [
                    'available' => $available,
                    'price' => $price,
                    'currency' => 'USD',
                    'message' => $available ? 'Domain is available' : 'Domain is not available',
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
            Log::error('Namecheap availability check failed', [
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
            $nameservers = $params['nameservers'] ?? $this->provider->default_nameservers ?? ['ns1.believoo.com', 'ns2.believoo.com'];
            
            $requestParams = [
                'DomainName' => $domain,
                'Years' => $params['years'] ?? 1,
                'RegistrantFirstName' => $params['registrant']['first_name'] ?? 'Admin',
                'RegistrantLastName' => $params['registrant']['last_name'] ?? 'User',
                'RegistrantAddress1' => $params['registrant']['address1'] ?? '123 Main St',
                'RegistrantCity' => $params['registrant']['city'] ?? 'City',
                'RegistrantStateProvince' => $params['registrant']['state'] ?? 'State',
                'RegistrantPostalCode' => $params['registrant']['zip'] ?? '12345',
                'RegistrantCountry' => $params['registrant']['country'] ?? 'US',
                'RegistrantPhone' => $params['registrant']['phone'] ?? '+1.1234567890',
                'RegistrantEmailAddress' => $params['registrant']['email'] ?? 'admin@example.com',
            ];

            // Add nameservers
            for ($i = 0; $i < count($nameservers) && $i < 5; $i++) {
                $requestParams['Nameservers'] = ($requestParams['Nameservers'] ?? '') . ($i > 0 ? ',' : '') . $nameservers[$i];
            }

            $response = $this->makeRequest('namecheap.domains.create', $requestParams);

            $domainCreateResult = $response->CommandResponse->DomainCreateResult ?? null;

            if ($domainCreateResult && (string) $domainCreateResult->Registered === 'true') {
                return [
                    'success' => true,
                    'order_id' => (string) $domainCreateResult->OrderID,
                    'message' => 'Domain registered successfully',
                    'provider_domain_id' => (string) $domainCreateResult->DomainID,
                    'charged_amount' => (float) $domainCreateResult->ChargedAmount,
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Registration failed',
            ];
        } catch (\Exception $e) {
            Log::error('Namecheap domain registration failed', [
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
            $response = $this->makeRequest('namecheap.domains.renew', [
                'DomainName' => $domain,
                'Years' => $years,
            ]);

            $domainRenewResult = $response->CommandResponse->DomainRenewResult ?? null;

            if ($domainRenewResult && (string) $domainRenewResult->Renew === 'true') {
                return [
                    'success' => true,
                    'order_id' => (string) $domainRenewResult->OrderID,
                    'message' => 'Domain renewed successfully',
                    'charged_amount' => (float) $domainRenewResult->ChargedAmount,
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Renewal failed',
            ];
        } catch (\Exception $e) {
            Log::error('Namecheap domain renewal failed', [
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
            $response = $this->makeRequest('namecheap.domains.getInfo', [
                'DomainName' => $domain,
            ]);

            $domainInfo = $response->CommandResponse->DomainGetInfoResult ?? null;

            if ($domainInfo) {
                $statuses = $domainInfo->StatusList->Status ?? [];
                $statusList = [];
                foreach ($statuses as $status) {
                    $statusList[] = (string) $status;
                }

                return [
                    'domain' => $domain,
                    'status' => implode(', ', $statusList),
                    'expiry_date' => (string) $domainInfo->DomainDetails->ExpiredDate ?? null,
                    'registration_date' => (string) $domainInfo->DomainDetails->CreatedDate ?? null,
                    'nameservers' => $this->extractNameservers($domainInfo->DnsDetails),
                    'whois_guard' => (string) $domainInfo->Whoisguard->Enabled === 'True',
                    'is_expired' => (string) $domainInfo->IsExpired === 'true',
                    'is_locked' => (string) $domainInfo->IsLocked === 'true',
                    'is_premium' => (string) $domainInfo->IsPremium === 'true',
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Namecheap get domain info failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function updateNameservers(string $domain, array $nameservers): array
    {
        try {
            $params = [
                'DomainName' => $domain,
            ];

            // Add nameservers
            for ($i = 0; $i < count($nameservers) && $i < 5; $i++) {
                $params['Nameservers'] = ($params['Nameservers'] ?? '') . ($i > 0 ? ',' : '') . $nameservers[$i];
            }

            $response = $this->makeRequest('namecheap.domains.dns.setCustom', $params);

            $result = $response->CommandResponse->DomainDNSSetCustomResult ?? null;

            if ($result && (string) $result->Updated === 'true') {
                return [
                    'success' => true,
                    'message' => 'Nameservers updated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Update failed',
            ];
        } catch (\Exception $e) {
            Log::error('Namecheap update nameservers failed', [
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
        try {
            // Namecheap uses separate hosts for each record
            $hosts = [];
            foreach ($records as $record) {
                $hosts[] = [
                    'HostName' => $record['name'] ?? '@',
                    'RecordType' => $record['type'] ?? 'A',
                    'Address' => $record['value'] ?? '',
                    'MXPref' => $record['priority'] ?? '10',
                    'TTL' => $record['ttl'] ?? '1800',
                ];
            }

            // Build host params
            $params = [
                'SLD' => explode('.', $domain)[0],
                'TLD' => $this->getTldFromDomain($domain),
            ];

            foreach ($hosts as $i => $host) {
                $params["HostName{$i}"] = $host['HostName'];
                $params["RecordType{$i}"] = $host['RecordType'];
                $params["Address{$i}"] = $host['Address'];
                $params["MXPref{$i}"] = $host['MXPref'];
                $params["TTL{$i}"] = $host['TTL'];
            }

            $response = $this->makeRequest('namecheap.domains.dns.setHosts', $params);

            $result = $response->CommandResponse->DomainDNSSetHostsResult ?? null;

            if ($result && (string) $result->IsSuccess === 'true') {
                return [
                    'success' => true,
                    'message' => 'DNS records updated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => 'Update failed',
            ];
        } catch (\Exception $e) {
            Log::error('Namecheap update DNS records failed', [
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
            $response = $this->makeRequest('namecheap.domains.dns.getHosts', [
                'SLD' => explode('.', $domain)[0],
                'TLD' => $this->getTldFromDomain($domain),
            ]);

            $result = $response->CommandResponse->DomainDNSGetHostsResult ?? null;
            $records = [];

            if ($result && isset($result->host)) {
                foreach ($result->host as $host) {
                    $records[] = [
                        'name' => (string) $host->Name,
                        'type' => (string) $host->Type,
                        'value' => (string) $host->Address,
                        'ttl' => (int) $host->TTL,
                        'priority' => (int) ($host->MXPref ?? 0),
                    ];
                }
            }

            return $records;
        } catch (\Exception $e) {
            Log::error('Namecheap get DNS records failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getDomainPrice(string $tld, int $years = 1): array
    {
        try {
            $response = $this->makeRequest('namecheap.users.getPricing', [
                'ProductType' => 'DOMAIN',
                'ProductCategory' => 'REGISTER',
                'ActionName' => 'REGISTER',
                'ProductName' => $tld,
            ]);

            $product = $response->CommandResponse->UserGetPricingResult->Product ?? null;

            if ($product) {
                $price = (float) ($product->Price->YourPrice ?? 0);
                return [
                    'price' => $price * $years,
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
            Log::error('Namecheap get price failed', [
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
                'DomainName' => $domain,
                'AuthCode' => $authCode,
            ];

            // Add contacts if provided
            if (isset($params['registrant'])) {
                $requestParams['RegistrantFirstName'] = $params['registrant']['first_name'] ?? '';
                $requestParams['RegistrantLastName'] = $params['registrant']['last_name'] ?? '';
                $requestParams['RegistrantEmailAddress'] = $params['registrant']['email'] ?? '';
            }

            $response = $this->makeRequest('namecheap.domains.transfer.create', $requestParams);

            $result = $response->CommandResponse->DomainTransferCreateResult ?? null;

            if ($result && (string) $result->TransferRequestSuccess === 'true') {
                return [
                    'success' => true,
                    'order_id' => (string) $result->OrderID,
                    'message' => 'Domain transfer initiated',
                    'transaction_id' => (string) $result->TransactionID,
                ];
            }

            return [
                'success' => false,
                'order_id' => null,
                'message' => 'Transfer failed',
            ];
        } catch (\Exception $e) {
            Log::error('Namecheap domain transfer failed', [
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
            $response = $this->makeRequest('namecheap.users.getBalances');
            
            $balances = $response->CommandResponse->UserGetBalancesResult ?? null;
            
            return [
                'balance' => (float) ($balances->AvailableBalance ?? 0),
                'currency' => 'USD',
                'account_balance' => (float) ($balances->AccountBalance ?? 0),
                'earnings' => (float) ($balances->Earnings ?? 0),
                'funds_requested' => (float) ($balances->FundsRequested ?? 0),
                'hold' => (float) ($balances->Hold ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Namecheap get balance failed', [
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
            // Try to get account balance as a simple test
            $this->getBalance();
            
            return [
                'success' => true,
                'message' => 'Connected successfully to Namecheap API',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request to Namecheap
     */
    protected function makeRequest(string $command, array $params = []): SimpleXMLElement
    {
        $url = $this->apiUrl;
        
        $queryParams = array_merge([
            'ApiUser' => $this->username,
            'ApiKey' => $this->apiKey,
            'UserName' => $this->username,
            'ClientIp' => $this->clientIp,
            'Command' => $command,
        ], $params);

        if ($this->testMode) {
            $queryParams['TestMode'] = 'true';
        }

        $response = Http::timeout(30)
            ->get($url, $queryParams);

        if ($response->failed()) {
            throw new \Exception('API request failed: ' . $response->body());
        }

        $xml = new SimpleXMLElement($response->body());

        if ((string) $xml->Status !== 'OK') {
            $errors = [];
            if (isset($xml->Errors->Error)) {
                foreach ($xml->Errors->Error as $error) {
                    $errors[] = (string) $error;
                }
            }
            throw new \Exception(implode(', ', $errors) ?: 'API error');
        }

        return $xml;
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
     * Extract nameservers from DNS details
     */
    protected function extractNameservers($dnsDetails): array
    {
        $nameservers = [];
        if (isset($dnsDetails->Nameserver)) {
            foreach ($dnsDetails->Nameserver as $ns) {
                $nameservers[] = (string) $ns;
            }
        }
        return $nameservers;
    }
}
