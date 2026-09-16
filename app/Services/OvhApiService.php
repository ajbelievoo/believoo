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
     * Dry-run checkout. Validates the cart and configuration without creating an order.
     */
    public function dryRunCheckout(string $cartId): array
    {
        return $this->get("/order/cart/{$cartId}/checkout");
    }

    /**
     * Probe whether a plan can reach the checkout stage for any allowed datacenter.
     * Uses dry-run checkout so no real order is created.
     */
    public function isOrderable(string $category, string $planCode, string $duration = 'P1M', int $quantity = 1, ?string $domain = null): bool
    {
        $category = strtoupper($category);

        // Single dry-run is enough for VPS, web hosting and domains.
        if (in_array($category, ['VPS', 'WEB_HOSTING', 'DOMAINS', 'DOMAIN'])) {
            $cart = $this->createCart('Orderability probe ' . $planCode);
            $cartId = $cart['cartId'];

            try {
                $item = $this->addItemToCart($cartId, $category, $planCode, $duration, $quantity, $domain);
                $itemId = $item['itemId'] ?? null;

                if ($itemId) {
                    $this->autoConfigureCartItem($cartId, (string) $itemId, $category, ['domain' => $domain]);
                }

                $this->dryRunCheckout($cartId);
                return true;
            } catch (\Exception $e) {
                Log::info('Plan not orderable', [
                    'category' => $category,
                    'plan_code' => $planCode,
                    'error' => $e->getMessage(),
                ]);
                return false;
            } finally {
                try {
                    $this->delete('/order/cart/' . $cartId);
                } catch (\Exception $e) {
                    // ignore cleanup failure
                }
            }
        }

        // Dedicated: try each allowed datacenter because stock varies by region.
        if ($category === 'DEDICATED') {
            // Plans whose code ends with a 3-letter region code (e.g. -syd, -mum, -sgp)
            // are only available in that datacenter. This account only has access to
            // the bhs/fra/sbg/lon/rbx datacenters, so those plans can be rejected quickly.
            if (preg_match('/-([a-z]{3})$/', $planCode, $m) && !in_array($m[1], ['bhs', 'fra', 'sbg', 'lon', 'rbx'], true)) {
                return false;
            }

            $cart = $this->createCart('Datacenter probe ' . $planCode);
            $cartId = $cart['cartId'];
            $datacenters = [];

            try {
                $item = $this->addItemToCart($cartId, $category, $planCode, $duration, $quantity, $domain);
                $required = $this->getRequiredCartConfiguration($cartId, (string) $item['itemId']);
                $datacenters = $required['dedicated_datacenter']['allowedValues'] ?? ['bhs', 'fra', 'sbg', 'lon', 'rbx'];

                // Prefer datacenters likely to have stock for this account.
                $preferred = ['bhs', 'fra', 'sbg', 'lon', 'rbx'];
                usort($datacenters, fn ($a, $b) => (
                    (array_search($a, $preferred, true) ?: 99) <=> (array_search($b, $preferred, true) ?: 99)
                ));
            } catch (\Exception $e) {
                return false;
            }

            $first = true;
            foreach ($datacenters as $datacenter) {
                if (!$first) {
                    $cart = $this->createCart('Orderability probe ' . $planCode . ' ' . $datacenter);
                    $cartId = $cart['cartId'];

                    try {
                        $item = $this->addItemToCart($cartId, $category, $planCode, $duration, $quantity, $domain);
                    } catch (\Exception $e) {
                        break;
                    }
                }
                $first = false;

                try {
                    $itemId = $item['itemId'] ?? null;

                    if ($itemId) {
                        $this->autoConfigureCartItem($cartId, (string) $itemId, $category, ['datacenter' => $datacenter]);
                    }

                    $this->dryRunCheckout($cartId);
                    return true;
                } catch (\Exception $e) {
                    Log::info('Dedicated plan not orderable in datacenter', [
                        'plan_code' => $planCode,
                        'datacenter' => $datacenter,
                        'error' => $e->getMessage(),
                    ]);
                } finally {
                    try {
                        $this->delete('/order/cart/' . $cartId);
                    } catch (\Exception $e) {
                        // ignore cleanup failure
                    }
                }
            }

            return false;
        }

        return false;
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
     * Map a synced OVH product category to the OVH cart family endpoint segment.
     */
    protected function cartFamilyForCategory(string $category): string
    {
        return match (strtoupper($category)) {
            'VPS'            => 'vps',
            'DEDICATED'      => 'baremetalServers',
            'WEB_HOSTING'    => 'webHosting',
            'PUBLIC_CLOUD'   => 'cloud',
            'PRIVATE_CLOUD'  => 'privateCloud',
            'DOMAINS'        => 'domain',
            'DOMAIN'         => 'domain',
            default          => throw new \InvalidArgumentException("Unsupported OVH category for cart: {$category}"),
        };
    }

    /**
     * Add any catalog product to a cart using the correct family endpoint.
     */
    public function addItemToCart(string $cartId, string $category, string $planCode, string $duration = 'P1M', int $quantity = 1, ?string $domain = null): array
    {
        $family = $this->cartFamilyForCategory($category);

        $payload = [
            'planCode'    => $planCode,
            'duration'    => $duration,
            'pricingMode' => 'default',
            'quantity'    => $quantity,
        ];

        if ($family === 'domain' && $domain) {
            $payload['domain'] = $domain;
        }

        return $this->post("/order/cart/{$cartId}/{$family}", $payload);
    }

    /**
     * Fetch required configuration labels/values for a cart item.
     */
    public function getRequiredCartConfiguration(string $cartId, string $itemId): array
    {
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
    }

    /**
     * Auto-configure a cart item with sensible defaults for the given category.
     * Uses requiredConfiguration to avoid sending unsupported labels.
     */
    public function autoConfigureCartItem(string $cartId, string $itemId, string $category, array $overrides = []): void
    {
        $required = $this->getRequiredCartConfiguration($cartId, $itemId);

        $defaults = match (strtoupper($category)) {
            'VPS' => [
                'vps_datacenter' => $overrides['datacenter'] ?? 'GRA',
                'vps_os'         => $overrides['os'] ?? 'Ubuntu 22.04',
                'region'         => $overrides['region'] ?? 'europe',
                'infrastructure' => $overrides['infrastructure'] ?? 'production',
            ],
            'DEDICATED' => [
                'dedicated_datacenter' => $overrides['datacenter'] ?? 'rbx',
                'dedicated_os'         => $overrides['os'] ?? 'none_64.en',
                'region'               => $overrides['region'] ?? 'europe',
                'enable-backup'        => $overrides['enable_backup'] ?? 'false',
            ],
            'WEB_HOSTING' => [
                'district'     => $overrides['datacenter'] ?? null,
                'dns_zone'     => $overrides['dns_zone'] ?? 'NO_CHANGE',
                'legacy_domain' => $overrides['legacy_domain'] ?? $overrides['domain'] ?? null,
            ],
            'PUBLIC_CLOUD' => [
                'infrastructure' => $overrides['infrastructure'] ?? 'production',
                'description'    => $overrides['description'] ?? 'Believoo cloud project',
            ],
            'DOMAINS', 'DOMAIN' => [
                'DNS'            => $overrides['dns'] ?? 'NO_CHANGE',
                'OWNER_LEGAL_AGE' => 'true',
            ],
            default => [],
        };

        foreach ($defaults as $label => $value) {
            if (!array_key_exists($label, $required)) {
                continue;
            }

            // Skip domain/hosting fields that would be set to an empty string.
            if (in_array($label, ['legacy_domain', 'webhosting_domain'], true) && trim((string) $value) === '') {
                continue;
            }

            $allowed = $required[$label]['allowedValues'] ?? null;
            if (is_array($allowed) && !in_array((string) $value, array_map('strval', $allowed), true)) {
                // Fall back to the first allowed value if the default is not accepted.
                $value = $allowed[0] ?? $value;
            }

            // Drop null values for optional labels; OVH rejects 'null' strings for some fields.
            if ($value === null || $value === '') {
                continue;
            }

            $this->configureCartItem($cartId, $itemId, $label, (string) $value);
        }
    }

    /**
     * Place a generic OVH catalog product order.
     *
     * For dedicated servers we try each allowed datacenter in turn, because a
     * specific hardware configuration may not be available in every region.
     */
    public function orderProduct(string $category, string $planCode, int $quantity = 1, string $duration = 'P1M', array $config = []): OvhOrderResult
    {
        $domain = $config['domain'] ?? null;

        // Dedicated servers need a datacenter; find the allowed values if none supplied.
        $datacenters = null;
        if (strtoupper($category) === 'DEDICATED' && empty($config['datacenter'])) {
            $datacenters = $this->getAllowedDedicatedDatacenters($planCode, $duration, $quantity, $domain);
        }

        // If we don't have a datacenter list, use the one from config or a safe default.
        $datacenters = $datacenters ?: [($config['datacenter'] ?? 'bhs')];

        $lastError = null;
        foreach ($datacenters as $datacenter) {
            try {
                return $this->doOrderProduct(
                    $category,
                    $planCode,
                    $quantity,
                    $duration,
                    array_merge($config, ['datacenter' => $datacenter]),
                    $domain
                );
            } catch (\Exception $e) {
                // If this datacenter is simply out of stock, try the next one.
                $message = $e->getMessage();
                if (str_contains($message, 'is not available in') || str_contains($message, 'not available in')) {
                    $lastError = $e;
                    continue;
                }
                throw $e;
            }
        }

        throw $lastError ?: new \RuntimeException('Could not place OVH dedicated order for ' . $planCode);
    }

    /**
     * Probe the allowed dedicated datacenters for a plan without completing an order.
     */
    protected function getAllowedDedicatedDatacenters(string $planCode, string $duration, int $quantity, ?string $domain): array
    {
        $cart = $this->createCart('Datacenter probe');
        $cartId = $cart['cartId'];

        try {
            $item = $this->addItemToCart($cartId, 'DEDICATED', $planCode, $duration, $quantity, $domain);
            $required = $this->getRequiredCartConfiguration($cartId, (string) $item['itemId']);

            return $required['dedicated_datacenter']['allowedValues'] ?? ['bhs', 'fra', 'sbg'];
        } finally {
            try {
                $this->delete('/order/cart/' . $cartId);
            } catch (\Exception $e) {
                // ignore cleanup failure
            }
        }
    }

    /**
     * Single cart attempt for a generic OVH catalog product order.
     */
    protected function doOrderProduct(string $category, string $planCode, int $quantity, string $duration, array $config, ?string $domain): OvhOrderResult
    {
        $cart = $this->createCart('Believoo ' . $category . ' order');
        $cartId = $cart['cartId'];

        try {
            $item = $this->addItemToCart($cartId, $category, $planCode, $duration, $quantity, $domain);
            $itemId = $item['itemId'] ?? null;

            if ($itemId) {
                $this->autoConfigureCartItem($cartId, (string) $itemId, $category, $config);
            }

            $checkout = $this->checkoutCart($cartId, false);

            $orderId = $checkout['orderId'] ?? null;
            if (!$orderId) {
                throw new \RuntimeException('OVH checkout did not return an orderId: ' . json_encode($checkout));
            }

            // Free orders (e.g. public-cloud discovery) may not need a payment mean.
            $total = $this->extractTotalFromCheckout($checkout);
            if ($total !== 0.0) {
                try {
                    $this->payOrderWithFirstAvailableMean($orderId);
                } catch (\Exception $e) {
                    Log::error('OVH payment failed after order creation', [
                        'order_id' => $orderId,
                        'error'    => $e->getMessage(),
                    ]);
                    throw new \RuntimeException('OVH order created but payment failed: ' . $e->getMessage());
                }
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
            try {
                $this->delete('/order/cart/' . $cartId);
            } catch (\Exception $cleanup) {
                // Ignore cleanup errors.
            }
            throw $e;
        }
    }

    /**
     * Extract the total price (with tax) from a checkout response.
     */
    protected function extractTotalFromCheckout(array $checkout): float
    {
        $prices = $checkout['prices'] ?? [];

        if (isset($prices['withTax']['value'])) {
            return (float) $prices['withTax']['value'];
        }

        if (isset($prices['withoutTax']['value'])) {
            return (float) $prices['withoutTax']['value'];
        }

        foreach ($prices as $price) {
            if (is_array($price) && ($price['label'] ?? '') === 'TOTAL') {
                return (float) ($price['price']['value'] ?? 0);
            }
        }

        return 0.0;
    }

    /**
     * Convenience: order a single VPS from OVH and pay with the OVH account wallet.
     */
    public function orderVps(string $planCode, int $quantity = 1, string $duration = 'P1M', string $os = 'Ubuntu 22.04'): OvhOrderResult
    {
        return $this->orderProduct('VPS', $planCode, $quantity, $duration, [
            'os' => $os,
            'datacenter' => 'GRA',
        ]);
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
        if (isset($this->prices['withTax']['value'])) {
            return (float) $this->prices['withTax']['value'];
        }

        if (isset($this->prices['withoutTax']['value'])) {
            return (float) $this->prices['withoutTax']['value'];
        }

        foreach ($this->prices as $price) {
            if (is_array($price) && ($price['label'] ?? '') === 'TOTAL') {
                return (float) ($price['price']['value'] ?? $price['priceInUcents'] ?? 0) / 100_000_000;
            }
        }

        return null;
    }
}
