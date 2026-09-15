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

    protected const PYTHON_BASE_URL = 'http://127.0.0.1:8000';

    protected const PYTHON_SERVICE_KEY_CONFIG = 'ghc_admin_service_key';

    protected ?string $pythonServiceKey = null;

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
     * Routes through the GHC Python backend because the PHP cURL/OpenSSL
     * stack cannot handshake with OVH's TLS 1.3 endpoints.
     */
    protected function call(string $method, string $path, array $params = []): mixed
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('OVH API is not configured or enabled');
        }

        // Try GHC Python backend first (newer OpenSSL/cURL stack).
        $serviceKey = $this->getPythonServiceKey();
        if ($serviceKey !== null) {
            try {
                Log::info('OVH API call via GHC Python', [
                    'method' => $method,
                    'path'   => $path,
                ]);

                return $this->callThroughPython($method, $path, $params);
            } catch (\Exception $e) {
                // If the proxy returned a valid OVH API error (e.g. 403/500 from OVH),
                // do not hide it behind a PHP TLS handshake failure.
                if (str_starts_with($e->getMessage(), 'GHC Python OVH proxy returned HTTP')) {
                    throw $e;
                }

                Log::warning('GHC Python OVH proxy failed, falling back to direct', [
                    'method' => $method,
                    'path'   => $path,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        // Fallback: direct PHP OVH client (requires up-to-date cURL/OpenSSL).
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

    /**
     * Fetch the GHC admin service key from the GHC SQLite database.
     */
    protected function getPythonServiceKey(): ?string
    {
        if ($this->pythonServiceKey !== null) {
            return $this->pythonServiceKey;
        }

        try {
            $value = DB::connection('ghc')->table('admin_configs')
                ->where('key', self::PYTHON_SERVICE_KEY_CONFIG)
                ->value('value');

            $this->pythonServiceKey = is_string($value) && $value !== '' ? $value : null;
        } catch (\Exception $e) {
            Log::warning('GHC service key lookup failed', ['error' => $e->getMessage()]);
            $this->pythonServiceKey = null;
        }

        return $this->pythonServiceKey;
    }

    /**
     * Call the GHC Python backend OVH proxy.
     */
    protected function callThroughPython(string $method, string $path, array $params = []): mixed
    {
        $key = $this->getPythonServiceKey();
        if ($key === null) {
            throw new \RuntimeException('GHC Python service key is not configured');
        }

        $response = Http::timeout(60)
            ->withHeaders(['X-Service-Key' => $key])
            ->post(self::PYTHON_BASE_URL . '/api/admin/ovh/proxy', [
                'method' => $method,
                'path'   => $path,
                'params' => empty($params) ? (object) [] : $params,
            ]);

        if (!$response->successful()) {
            $body = $response->body();
            Log::error('GHC Python OVH proxy returned error', [
                'status' => $response->status(),
                'body'   => $body,
            ]);
            throw new \RuntimeException('GHC Python OVH proxy returned HTTP ' . $response->status() . ': ' . $body);
        }

        return $response->json();
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

    /**
     * Check whether the OVH account has any usable payment method registered.
     */
    public function hasPaymentMeans(): bool
    {
        $means = $this->getAvailablePaymentMeans();
        if (!empty($means)) {
            return true;
        }

        try {
            $auto = $this->get('/me/availableAutomaticPaymentMeans');
            if (is_array($auto)) {
                foreach ($auto as $value) {
                    if ($value === true) {
                        return true;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('OVH automatic payment means fetch failed', ['error' => $e->getMessage()]);
        }

        return false;
    }

    // ------------------------------------------------------------------------
    // Catalog helpers
    // ------------------------------------------------------------------------

    /**
     * Get available VPS plans from OVH order catalog.
     * Returns an array of normalized plan objects.
     *
     * Uses the GHC Python backend catalog because the public OVH catalog is
     * already filtered and parsed there (PHP's cart endpoint returns add-ons).
     */
    public function getVpsPlans(): array
    {
        $response = Http::timeout(60)
            ->get(self::PYTHON_BASE_URL . '/api/catalog/plans?category=VPS');

        if (!$response->successful()) {
            throw new \RuntimeException('GHC catalog fetch failed: ' . $response->body());
        }

        $plans = $response->json() ?? [];
        return $this->normalizeVpsCatalog($plans);
    }

    /**
     * Get OVH catalog plans for any supported category from the GHC Python backend.
     */
    public function getCatalogPlans(string $category): array
    {
        $response = Http::timeout(120)
            ->get(self::PYTHON_BASE_URL . '/api/catalog/plans?category=' . urlencode($category));

        if (!$response->successful()) {
            throw new \RuntimeException('GHC catalog fetch failed for ' . $category . ': ' . $response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * Probe a VPS plan's required cart configuration (OS list, datacenters, etc.).
     * Creates a temporary cart, fetches /requiredConfiguration, then deletes it.
     */
    public function getVpsPlanConfigOptions(string $planCode, string $duration = 'P1M'): array
    {
        $cart = $this->createCart('Believoo plan config probe');
        $cartId = $cart['cartId'];

        try {
            $item = $this->addVpsToCart($cartId, $planCode, $duration);
            $itemId = $item['itemId'] ?? null;

            if (!$itemId) {
                throw new \RuntimeException('OVH did not return an itemId for plan config probe: ' . $planCode);
            }

            $configs = $this->get("/order/cart/{$cartId}/item/{$itemId}/requiredConfiguration") ?: [];

            $options = [];
            foreach ($configs as $config) {
                $label = $config['label'] ?? null;
                if ($label) {
                    $options[$label] = [
                        'required'      => $config['required'] ?? false,
                        'type'          => $config['type'] ?? null,
                        'allowedValues' => $config['allowedValues'] ?? null,
                    ];
                }
            }

            return $options;
        } finally {
            try {
                $this->delete("/order/cart/{$cartId}");
            } catch (\Exception $e) {
                Log::warning('OVH plan config probe cart cleanup failed', ['cartId' => $cartId, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Normalize GHC Python catalog plans into the structure the sync job expects.
     */
    protected function normalizeVpsCatalog(array $plans): array
    {
        $normalized = [];
        foreach ($plans as $plan) {
            $planCode = $plan['plan_code'] ?? null;
            if (!$planCode) {
                continue;
            }

            // Only base VPS plans; skip add-ons and options.
            if (!str_starts_with(strtolower($planCode), 'vps-') && !str_starts_with(strtolower($planCode), 's')) {
                continue;
            }
            if (str_contains(strtolower($planCode), 'option') || str_contains(strtolower($planCode), 'ftpbackup')) {
                continue;
            }

            $monthly = $this->extractMonthlyDurationPrice($plan['durations'] ?? []);

            $normalized[] = [
                'plan_code'     => $planCode,
                'product_name'  => $plan['invoice_name'] ?? $planCode,
                'display_name'  => $plan['invoice_name'] ?? $planCode,
                'duration'      => 'P1M',
                'pricing_mode'  => 'default',
                'price_monthly' => $monthly['price'],
                'currency_code' => $monthly['currency'] ?? ($plan['currency'] ?? 'EUR'),
                'cpu_cores'     => $plan['cpu_cores'] ?? 1,
                'memory_gb'     => $plan['ram_gb'] ?? 1,
                'disk_gb'       => $plan['disk_gb'] ?? 20,
                'disk_type'     => $plan['disk_type'] ?? 'SSD',
                'bandwidth'     => 'Unlimited',
                'raw'           => $plan,
            ];
        }

        return $normalized;
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
     * Create an order cart for a given subsidiary and assign it to the account.
     */
    public function createCart(string $description = 'Believoo order'): array
    {
        $cart = $this->post('/order/cart', [
            'ovhSubsidiary' => $this->config['ovh_subsidiary'] ?? 'FR',
            'description'   => $description,
        ]);

        $cartId = $cart['cartId'] ?? null;
        if ($cartId) {
            $this->assignCart($cartId);
        }

        return $cart;
    }

    /**
     * Assign a cart to the authenticated OVH account.
     */
    public function assignCart(string $cartId): mixed
    {
        return $this->post("/order/cart/{$cartId}/assign");
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
     * Add a configuration value to a cart item (datacenter, OS, etc.).
     */
    public function configureCartItem(string $cartId, string $itemId, string $label, string $value): array
    {
        return $this->post("/order/cart/{$cartId}/item/{$itemId}/configuration", [
            'label' => $label,
            'value' => $value,
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
     * Get the payment means available for a specific order.
     */
    public function getAvailableOrderPaymentMeans(string $orderId): array
    {
        try {
            return $this->get("/me/order/{$orderId}/availableRegisteredPaymentMean") ?: [];
        } catch (\Exception $e) {
            Log::warning('Could not fetch order payment means', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
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
     * Pay an order using the first payment mean OVH allows for it.
     */
    public function payOrderWithFirstAvailableMean(string $orderId): array
    {
        $means = $this->getAvailableOrderPaymentMeans($orderId);

        if (empty($means)) {
            throw new \RuntimeException('No registered payment mean available for OVH order ' . $orderId);
        }

        // Prefer the OVH account wallet if it is allowed.
        if (in_array('ovhAccount', $means, true)) {
            return $this->payOrder($orderId, 'ovhAccount');
        }

        return $this->payOrder($orderId, (string) $means[0]);
    }

    /**
     * Convenience: order a single VPS from OVH and pay with the OVH account wallet.
     */
    public function orderVps(string $planCode, int $quantity = 1, string $duration = 'P1M', string $os = 'Ubuntu 22.04'): OvhOrderResult
    {
        $cart = $this->createCart('Believoo VPS order');
        $cartId = $cart['cartId'];

        try {
            $item = $this->addVpsToCart($cartId, $planCode, $duration, $quantity);
            $itemId = $item['itemId'] ?? null;

            if ($itemId) {
                $this->configureCartItem($cartId, (string) $itemId, 'vps_datacenter', 'GRA');
                $this->configureCartItem($cartId, (string) $itemId, 'vps_os', $os);
            }

            // Create the sales-order without auto-pay so we can explicitly pay from the wallet.
            $checkout = $this->checkoutCart($cartId, false);

            $orderId = $checkout['orderId'] ?? null;
            if (!$orderId) {
                throw new \RuntimeException('OVH checkout did not return an orderId: ' . json_encode($checkout));
            }

            // Pay using whichever registered payment mean OVH allows for this order.
            try {
                $this->payOrderWithFirstAvailableMean($orderId);
            } catch (\Exception $e) {
                Log::error('OVH payment failed after order creation', [
                    'order_id' => $orderId,
                    'error'    => $e->getMessage(),
                ]);
                throw new \RuntimeException('OVH order created but payment failed: ' . $e->getMessage());
            }

            return new OvhOrderResult(
                orderId:   $orderId,
                cartId:    $cartId,
                url:       $checkout['url'] ?? null,
                prices:    $checkout['prices'] ?? [],
                contracts: $checkout['contracts'] ?? [],
                raw:       $checkout,
            );
        } catch (\Exception $e) {
            // Best-effort cleanup so we do not leave stale OVH carts behind.
            try {
                $this->delete('/order/cart/' . $cartId);
            } catch (\Exception $cleanup) {
                // Ignore cleanup errors.
            }
            throw $e;
        }
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
