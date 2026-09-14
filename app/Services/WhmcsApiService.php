<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class WhmcsApiService
{
    protected string $baseUrl;
    protected string $apiIdentifier;
    protected string $apiSecret;
    protected int $cacheTtl;

    public function __construct()
    {
        // Read from database settings first, fallback to .env/config
        $this->baseUrl = rtrim(
            Setting::getValue('whmcs_base_url') ?: config('server-management.whmcs.base_url'),
            '/'
        );
        $this->apiIdentifier = Setting::getValue('whmcs_api_identifier') ?: config('server-management.whmcs.api_identifier');
        $this->apiSecret = Setting::getValue('whmcs_api_secret') ?: config('server-management.whmcs.api_secret');
        $this->cacheTtl = (int) (Setting::getValue('whmcs_cache_ttl') ?: config('server-management.whmcs.cache_ttl', 300));
    }

    /**
     * Make a request to the WHMCS API
     */
    protected function request(string $action, array $params = []): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post($this->baseUrl . '/includes/api.php', array_merge([
                    'action' => $action,
                    'identifier' => $this->apiIdentifier,
                    'secret' => $this->apiSecret,
                    'responsetype' => 'json',
                ], $params));

            if (!$response->successful()) {
                Log::error('WHMCS API request failed', [
                    'action' => $action,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['result']) && $data['result'] === 'error') {
                Log::error('WHMCS API returned error', [
                    'action' => $action,
                    'error' => $data['message'] ?? 'Unknown error',
                ]);
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('WHMCS API exception', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get client's products/services with server details
     */
    public function getClientProducts(int $clientId): ?array
    {
        $cacheKey = "whmcs_client_products_{$clientId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($clientId) {
            return $this->request('GetClientsProducts', [
                'clientid' => $clientId,
                'stats' => true,
            ]);
        });
    }

    /**
     * Get product/service details
     */
    public function getService(int $serviceId): ?array
    {
        $cacheKey = "whmcs_service_{$serviceId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($serviceId) {
            return $this->request('GetClientsProducts', [
                'serviceid' => $serviceId,
            ]);
        });
    }

    /**
     * Get bandwidth usage for a specific service
     */
    public function getBandwidthUsage(int $serviceId): ?array
    {
        $data = $this->getService($serviceId);

        if (!$data || !isset($data['products']['product'][0])) {
            return null;
        }

        $product = $data['products']['product'][0];

        return [
            'service_id' => $serviceId,
            'bandwidth_usage' => $product['bandwidthusage'] ?? 0,
            'bandwidth_limit' => $product['bandwidthlimit'] ?? 'Unlimited',
            'disk_usage' => $product['diskusage'] ?? 0,
            'disk_limit' => $product['disklimit'] ?? 'Unlimited',
            'last_update' => $product['lastupdate'] ?? null,
        ];
    }

    /**
     * Get client details by email
     */
    public function getClientByEmail(string $email): ?array
    {
        return $this->request('GetClientsDetails', [
            'email' => $email,
        ]);
    }

    /**
     * Get invoices for a client
     */
    public function getClientInvoices(int $clientId, string $status = ''): ?array
    {
        $params = ['clientid' => $clientId];
        if ($status) {
            $params['status'] = $status;
        }

        return $this->request('GetInvoices', $params);
    }

    /**
     * Get all active services with server details for dashboard display
     */
    public function getDashboardData(int $clientId): array
    {
        $products = $this->getClientProducts($clientId);
        $services = [];

        if (!$products || !isset($products['products']['product'])) {
            return ['services' => [], 'invoices' => []];
        }

        foreach ($products['products']['product'] as $product) {
            if ($product['status'] !== 'Active') {
                continue;
            }

            $services[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'domain' => $product['domain'] ?? null,
                'dedicated_ip' => $product['dedicatedip'] ?? null,
                'status' => $product['status'],
                'next_due_date' => $product['nextduedate'] ?? null,
                'billing_cycle' => $product['billingcycle'] ?? null,
                'server_id' => $product['customfields']['customfield'][0]['value'] ?? null,
                'bandwidth' => [
                    'usage' => $product['bandwidthusage'] ?? 0,
                    'limit' => $product['bandwidthlimit'] ?? 'Unlimited',
                    'unit' => 'GB',
                ],
                'disk' => [
                    'usage' => $product['diskusage'] ?? 0,
                    'limit' => $product['disklimit'] ?? 'Unlimited',
                    'unit' => 'GB',
                ],
            ];
        }

        $invoices = $this->getClientInvoices($clientId, 'Unpaid');

        return [
            'services' => $services,
            'invoices' => $invoices['invoices']['invoice'] ?? [],
        ];
    }

    /**
     * Clear cache for a client
     */
    public function clearCache(int $clientId): void
    {
        Cache::forget("whmcs_client_products_{$clientId}");
    }
}
