<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ovh\Api;

class OvhApiService
{
    protected ?Api $client = null;

    protected bool $enabled = false;

    protected array $config = [];

    public function __construct()
    {
        $this->config = $this->loadConfig();
        $this->enabled = $this->isConfigured();

        if ($this->enabled) {
            try {
                $endpoint = $this->config['endpoint'] ?? 'https://eu.api.ovh.com/1.0';
                // OVH SDK constructor expects positional arguments; endpoint param is $api_endpoint.
                $this->client = new Api(
                    $this->config['application_key'] ?? '',
                    $this->config['application_secret'] ?? '',
                    $endpoint,
                    $this->config['consumer_key'] ?? '',
                );
            } catch (\Exception $e) {
                Log::error('OVH API client init failed', ['error' => $e->getMessage()]);
                $this->enabled = false;
            }
        }
    }

    /**
     * Load OVH config merged with settings stored in the database.
     */
    protected function loadConfig(): array
    {
        $defaults = config('ovh', []);

        $settings = [
            'application_key'    => Setting::getValue('ovh_application_key', $defaults['application_key'] ?? ''),
            'application_secret' => $this->decryptSetting('ovh_application_secret', $defaults['application_secret'] ?? ''),
            'consumer_key'       => $this->decryptSetting('ovh_consumer_key', $defaults['consumer_key'] ?? ''),
            'endpoint'           => Setting::getValue('ovh_endpoint', $defaults['endpoint'] ?? 'https://eu.api.ovh.com/1.0'),
            'commission_percent' => (float) Setting::getValue('ovh_commission_percent', $defaults['commission_percent'] ?? 25.0),
            'ovh_subsidiary'     => Setting::getValue('ovh_subsidiary', $defaults['ovh_subsidiary'] ?? 'FR'),
            'auto_pay'           => filter_var(Setting::getValue('ovh_auto_pay', $defaults['auto_pay'] ?? true), FILTER_VALIDATE_BOOLEAN),
            'sync'               => $defaults['sync'] ?? [],
        ];

        return array_merge($defaults, $settings);
    }

    /**
     * Decrypt a setting value if it was encrypted.
     */
    protected function decryptSetting(string $key, string $default = ''): string
    {
        $value = Setting::getValue($key, $default);
        if (!is_string($value) || $value === '') {
            return $default;
        }

        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Check if all required OVH credentials are present.
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['application_key'])
            && !empty($this->config['application_secret'])
            && !empty($this->config['consumer_key'])
            && !empty($this->config['endpoint']);
    }

    /**
     * Returns true if the OVH client is ready to make calls.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && $this->client !== null;
    }

    /**
     * Get current OVH API credentials from config/DB (decrypted if needed).
     */
    public static function getCredentials(): array
    {
        return [
            'application_key'    => config('ovh.application_key'),
            'application_secret' => config('ovh.application_secret'),
            'consumer_key'       => config('ovh.consumer_key'),
            'endpoint'           => config('ovh.endpoint'),
        ];
    }

    /**
     * Store OVH credentials in the database settings table.
     */
    public static function setCredentials(array $values): void
    {
        $map = [
            'ovh_application_key'    => 'application_key',
            'ovh_application_secret' => 'application_secret',
            'ovh_consumer_key'       => 'consumer_key',
            'ovh_endpoint'           => 'endpoint',
        ];

        foreach ($map as $settingKey => $configKey) {
            $value = $values[$configKey] ?? null;
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['key' => $settingKey],
                    [
                        'value' => (string) $value,
                        'group' => 'OVH',
                        'type'  => 'string',
                    ]
                );
            }
        }
    }

    /**
     * Make a raw GET call to the OVH API.
     */
    public function get(string $path, array $params = []): mixed
    {
        return $this->call('GET', $path, $params);
    }

    /**
     * Make a raw POST call to the OVH API.
     */
    public function post(string $path, array $params = []): mixed
    {
        return $this->call('POST', $path, $params);
    }

    /**
     * Make a raw DELETE call to the OVH API.
     */
    public function delete(string $path, array $params = []): mixed
    {
        return $this->call('DELETE', $path, $params);
    }

    /**
     * Execute a raw OVH API call with logging and error handling.
     */
    protected function call(string $method, string $path, array $params = []): mixed
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('OVH API is not configured or enabled');
        }

        try {
            Log::info('OVH API call', [
                'method' => $method,
                'path'   => $path,
                'params' => $params,
            ]);

            $response = match (strtoupper($method)) {
                'GET'    => $this->client->get($path, $params),
                'POST'   => $this->client->post($path, $params),
                'PUT'    => $this->client->put($path, $params),
                'DELETE' => $this->client->delete($path, $params),
                default  => throw new \InvalidArgumentException("Unsupported method: {$method}"),
            };

            Log::info('OVH API response', [
                'method' => $method,
                'path'   => $path,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('OVH API call failed', [
                'method' => $method,
                'path'   => $path,
                'params' => $params,
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // ------------------------------------------------------------------------
    // Account / Wallet
    // ------------------------------------------------------------------------

    /**
     * Get cloud account balance (prepaid wallet).
     */
    public function getAccountBalance(): array
    {
        // Try the GHC Python backend first — it uses a newer cURL/OpenSSL stack.
        $pythonBalance = $this->getAccountBalanceFromPython();
        if ($pythonBalance !== null) {
            return $pythonBalance;
        }

        $balance = 0.0;
        $currency = 'EUR';
        $subsidiary = $this->config['ovh_subsidiary'] ?? 'FR';

        try {
            $me = $this->get('/me');
            $currency = $me['currency'] ?? 'EUR';
        } catch (\Exception $e) {
            Log::warning('Cloud /me call failed', ['error' => $e->getMessage()]);
        }

        // Cloud prepaid account balance (e.g. /me/ovhAccount/FR)
        try {
            $ovhAccount = $this->get('/me/ovhAccount/' . $subsidiary);
            if (is_array($ovhAccount)) {
                $balance = (float) ($ovhAccount['balance'] ?? 0);
                $currency = $ovhAccount['currency'] ?? $currency;
            }
        } catch (\Exception $e) {
            Log::warning('Cloud /me/ovhAccount call failed', ['error' => $e->getMessage(), 'subsidiary' => $subsidiary]);
        }

        // Fallback: fidelity / loyalty account.
        if ($balance <= 0) {
            try {
                $fidelity = $this->get('/me/fidelityAccount');
                if (is_array($fidelity)) {
                    $balance = (float) ($fidelity['balance'] ?? 0) * 0.01; // balance is usually in cents
                    $currency = $fidelity['currency'] ?? $currency;
                }
            } catch (\Exception $e) {
                Log::warning('Cloud /me/fidelityAccount call failed', ['error' => $e->getMessage()]);
            }
        }

        return [
            'balance'  => $balance,
            'currency' => $currency,
        ];
    }

    /**
     * Fetch the cloud account balance from the GHC Python backend.
     */
    protected function getAccountBalanceFromPython(): ?array
    {
        try {
            $serviceKey = DB::connection('ghc')->table('admin_configs')->where('key', 'ghc_admin_service_key')->value('value');
            if (!$serviceKey) {
                return null;
            }
            $adminEmail = DB::connection('ghc')->table('users')->where('role', 'admin')->value('email') ?? 'admin@believoo.com';
            $auth = Http::timeout(5)->post('http://127.0.0.1:8000/api/admin/service-auth', [
                'serviceKey' => $serviceKey,
                'email' => $adminEmail,
            ]);
            if (!$auth->successful()) {
                return null;
            }
            $token = $auth->json('token');
            if (!$token) {
                return null;
            }

            $response = Http::withToken($token)->timeout(15)->get('http://127.0.0.1:8000/api/admin/cloud/balance');
            if (!$response->successful()) {
                return null;
            }
            $data = $response->json();
            if (!isset($data['balance'])) {
                return null;
            }

            return [
                'balance'  => (float) $data['balance'],
                'currency' => $data['currency'] ?? 'EUR',
            ];
        } catch (\Exception $e) {
            Log::warning('Cloud balance fetch from Python backend failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get available payment means on the OVH account.
     */
    public function getAvailablePaymentMeans(): array
    {
        try {
            return $this->get('/me/payment/mean') ?: [];
        } catch (\Exception $e) {
            Log::warning('OVH payment means fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ------------------------------------------------------------------------
    // Catalog helpers
    // ------------------------------------------------------------------------

    /**
     * Get available VPS plans from OVH order catalog.
     * Returns an array of normalized plan objects.
     */
    public function getVpsPlans(): array
    {
        $subsidiary = $this->config['ovh_subsidiary'] ?? 'FR';

        // Create a temporary cart to interrogate the VPS catalog.
        $cart = $this->post('/order/cart', [
            'ovhSubsidiary' => $subsidiary,
            'description'   => 'Believoo catalog probe',
        ]);

        $cartId = $cart['cartId'] ?? null;
        if (!$cartId) {
            throw new \RuntimeException('OVH did not return a cartId');
        }

        try {
            // Ask for available VPS offers in this cart.
            $offers = $this->get("/order/cart/{$cartId}/vps");
            return $this->normalizeVpsOffers($offers ?: []);
        } finally {
            // Clean up the probe cart.
            try {
                $this->delete("/order/cart/{$cartId}");
            } catch (\Exception $e) {
                Log::warning('OVH cart cleanup failed', ['cartId' => $cartId, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Normalize raw OVH VPS offer data into a consistent structure.
     */
    protected function normalizeVpsOffers(array $offers): array
    {
        $plans = [];
        foreach ($offers as $offer) {
            $planCode = $offer['planCode'] ?? null;
            if (!$planCode) {
                continue;
            }

            $prices = $offer['prices'] ?? [];
            $monthlyPrice = $this->extractMonthlyPrice($prices);

            $specs = $this->extractVpsSpecsFromPlanCode($planCode);

            $plans[] = [
                'plan_code'     => $planCode,
                'product_name'  => $offer['productName'] ?? $planCode,
                'display_name'  => $offer['productName'] ?? $planCode,
                'duration'      => $this->extractDuration($prices),
                'pricing_mode'  => $offer['pricingMode'] ?? 'default',
                'price_monthly' => $monthlyPrice,
                'currency_code' => $offer['currencyCode'] ?? 'EUR',
                'cpu_cores'     => $specs['cpu_cores'] ?? 1,
                'memory_gb'     => $specs['memory_gb'] ?? 1,
                'disk_gb'       => $specs['disk_gb'] ?? 20,
                'disk_type'     => 'SSD',
                'bandwidth'     => 'Unlimited',
                'raw'           => $offer,
            ];
        }

        return $plans;
    }

    /**
     * Extract a monthly price from an OVH price list.
     */
    protected function extractMonthlyPrice(array $prices): ?float
    {
        foreach ($prices as $price) {
            if (($price['duration'] ?? '') === 'P1M') {
                return (float) ($price['priceInUcents'] ?? 0) / 100_000_000;
            }
        }

        // Fallback: first price entry scaled to monthly if possible.
        if (!empty($prices[0])) {
            $duration = $prices[0]['duration'] ?? 'P1M';
            $price = (float) ($prices[0]['priceInUcents'] ?? 0) / 100_000_000;
            if (str_starts_with($duration, 'P1Y')) {
                return $price / 12;
            }
            if (str_starts_with($duration, 'P3M')) {
                return $price / 3;
            }
            return $price;
        }

        return null;
    }

    /**
     * Extract duration string from prices or return default P1M.
     */
    protected function extractDuration(array $prices): string
    {
        foreach ($prices as $price) {
            if (!empty($price['duration'])) {
                return $price['duration'];
            }
        }
        return 'P1M';
    }

    /**
     * Try to infer vCPU/RAM/disk from an OVH VPS plan code.
     * Plan codes are usually like: vps-2024-1-2-40 (1 vCPU, 2 GB RAM, 40 GB disk).
     */
    protected function extractVpsSpecsFromPlanCode(string $planCode): array
    {
        // Common patterns: vps-2024-1-2-40, vps-2024-2-4-80, vps-2024-4-8-160
        if (preg_match('/vps[-_](\d{4})[-_](\d+)[-_](\d+)[-_](\d+)/i', $planCode, $m)) {
            return [
                'cpu_cores' => (int) $m[2],
                'memory_gb' => (int) $m[3],
                'disk_gb'   => (int) $m[4],
            ];
        }

        return [];
    }

    // ------------------------------------------------------------------------
    // Order flow
    // ------------------------------------------------------------------------

    /**
     * Create an order cart for a given subsidiary.
     */
    public function createCart(string $description = 'Believoo order'): array
    {
        return $this->post('/order/cart', [
            'ovhSubsidiary' => $this->config['ovh_subsidiary'] ?? 'FR',
            'description'   => $description,
        ]);
    }

    /**
     * Add a VPS item to an order cart.
     */
    public function addVpsToCart(string $cartId, string $planCode, string $duration = 'P1M', int $quantity = 1): array
    {
        return $this->post("/order/cart/{$cartId}/vps", [
            'planCode'    => $planCode,
            'duration'    => $duration,
            'pricingMode' => 'default',
            'quantity'    => $quantity,
        ]);
    }

    /**
     * Checkout a cart and optionally pay with the preferred payment method.
     */
    public function checkoutCart(string $cartId, bool $autoPay = null): array
    {
        $autoPay = $autoPay ?? (bool) ($this->config['auto_pay'] ?? true);

        return $this->post("/order/cart/{$cartId}/checkout", [
            'autoPayWithPreferredPaymentMethod' => $autoPay,
            'waiveRetractationPeriod'           => false,
        ]);
    }

    /**
     * Pay an existing order with a registered payment mean (fidelityAccount/ovhAccount).
     */
    public function payOrder(string $orderId, string $paymentMean = 'ovhAccount'): array
    {
        return $this->post("/me/order/{$orderId}/payWithRegisteredPaymentMean", [
            'paymentMean' => $paymentMean,
        ]);
    }

    /**
     * Convenience: order a single VPS from OVH and pay with the OVH account wallet.
     */
    public function orderVps(string $planCode, int $quantity = 1, string $duration = 'P1M'): OvhOrderResult
    {
        $cart = $this->createCart('Believoo VPS order');
        $cartId = $cart['cartId'];

        $this->addVpsToCart($cartId, $planCode, $duration, $quantity);

        // Create the sales-order without auto-pay so we can explicitly pay from the wallet.
        $checkout = $this->checkoutCart($cartId, false);

        $orderId = $checkout['orderId'] ?? null;
        if (!$orderId) {
            throw new \RuntimeException('OVH checkout did not return an orderId: ' . json_encode($checkout));
        }

        // Pay using the OVH prepaid account (wallet) for the configured subsidiary.
        try {
            $this->payOrder($orderId, 'ovhAccount');
        } catch (\Exception $e) {
            Log::error('OVH wallet payment failed after order creation', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            throw new \RuntimeException('OVH order created but wallet payment failed: ' . $e->getMessage());
        }

        return new OvhOrderResult(
            orderId:   $orderId,
            cartId:    $cartId,
            url:       $checkout['url'] ?? null,
            prices:    $checkout['prices'] ?? [],
            contracts: $checkout['contracts'] ?? [],
            raw:       $checkout,
        );
    }
}

/**
 * DTO for an OVH order result.
 */
class OvhOrderResult
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $cartId,
        public readonly ?string $url,
        public readonly array $prices,
        public readonly array $contracts,
        public readonly array $raw = [],
    ) {}

    /**
     * Get total price with tax from the checkout response.
     */
    public function getTotalWithTax(): ?float
    {
        foreach ($this->prices as $price) {
            if (($price['label'] ?? '') === 'TOTAL') {
                return (float) ($price['priceInUcents'] ?? 0) / 100_000_000;
            }
        }
        return null;
    }
}
