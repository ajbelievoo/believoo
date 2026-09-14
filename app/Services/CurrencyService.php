<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Supported currencies
     */
    const SUPPORTED_CURRENCIES = [
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'flag' => '🇺🇸',
            'locale' => 'en_US',
        ],
        'INR' => [
            'name' => 'Indian Rupee',
            'symbol' => '₹',
            'flag' => '🇮🇳',
            'locale' => 'en_IN',
        ],
    ];

    /**
     * Default currency
     */
    const DEFAULT_CURRENCY = 'USD';

    /**
     * Get user currency preference
     */
    public function getUserCurrency(?User $user = null): string
    {
        // Check if user is authenticated
        if (!$user && auth()->check()) {
            $user = auth()->user();
        }

        // Return user's preferred currency if set
        if ($user && $user->preferred_currency) {
            return $user->preferred_currency;
        }

        // Check session
        if (session()->has('currency')) {
            return session('currency');
        }

        // Respect the configured site currency
        $configuredCurrency = Setting::getValue('currency', self::DEFAULT_CURRENCY);
        if ($this->isSupported($configuredCurrency)) {
            return $configuredCurrency;
        }

        // Auto-detect based on IP/Country as fallback
        $detectedCurrency = $this->detectCurrencyByLocation();
        if ($detectedCurrency) {
            return $detectedCurrency;
        }

        return self::DEFAULT_CURRENCY;
    }

    /**
     * Set user currency preference
     */
    public function setUserCurrency(string $currency, ?User $user = null): void
    {
        if (!array_key_exists($currency, self::SUPPORTED_CURRENCIES)) {
            throw new \InvalidArgumentException("Unsupported currency: {$currency}");
        }

        // Update session
        session(['currency' => $currency]);

        // Update user preference if logged in
        if (!$user && auth()->check()) {
            $user = auth()->user();
        }

        if ($user) {
            $user->update(['preferred_currency' => $currency]);
        }
    }

    /**
     * Format price with currency symbol
     */
    public function formatPrice(float $amount, ?string $currency = null): string
    {
        $currency = $currency ?? $this->getUserCurrency();
        $symbol = self::SUPPORTED_CURRENCIES[$currency]['symbol'] ?? '$';

        if ($currency === 'INR') {
            // Indian format: ₹1,40,000
            return $symbol . number_format($amount, 0);
        }

        // International format: $1,400.00
        return $symbol . number_format($amount, 2);
    }

    /**
     * Convert USD amount to user's currency
     */
    public function convertFromUsd(float $amountUsd, ?string $targetCurrency = null): float
    {
        $targetCurrency = $targetCurrency ?? $this->getUserCurrency();

        if ($targetCurrency === 'USD') {
            return round($amountUsd, 2);
        }

        return ExchangeRate::convertFromUsd($amountUsd, $targetCurrency);
    }

    /**
     * Get display price for VPS plan
     */
    public function getDisplayPrice(float $basePriceUsd, ?string $currency = null): array
    {
        $currency = $currency ?? $this->getUserCurrency();
        $convertedAmount = $this->convertFromUsd($basePriceUsd, $currency);

        return [
            'amount' => $convertedAmount,
            'currency' => $currency,
            'symbol' => self::SUPPORTED_CURRENCIES[$currency]['symbol'],
            'formatted' => $this->formatPrice($convertedAmount, $currency),
            'base_usd' => $basePriceUsd,
        ];
    }

    /**
     * Fetch latest exchange rates from API
     * Tries multiple free APIs, no API key required
     */
    public function fetchLatestRates(): bool
    {
        // Try APIs in order - no API key required for free tier
        $apis = [
            'frankfurter' => 'https://api.frankfurter.app/latest?from=USD&to=INR',
            'exchangerate_host' => 'https://api.exchangerate.host/latest?base=USD&symbols=INR',
        ];

        // If API key is configured, try exchangerate-api first (more reliable)
        $apiKey = config('services.exchangerate_api.key');
        if ($apiKey) {
            $apis = array_merge(
                ['exchangerate_api' => "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/USD"],
                $apis
            );
        }

        foreach ($apis as $apiName => $apiUrl) {
            try {
                Log::info("Trying exchange rate API: {$apiName}");
                
                $response = Http::timeout(30)->get($apiUrl);

                if (!$response->successful()) {
                    Log::warning("API {$apiName} failed with status: " . $response->status());
                    continue;
                }

                $data = $response->json();
                $rate = $this->extractRateFromResponse($data, $apiName);

                if ($rate === null) {
                    Log::warning("Could not extract rate from {$apiName} response", $data);
                    continue;
                }

                // Store USD to INR rate
                $this->storeRate('USD', 'INR', $rate, ['api' => $apiName, 'response' => $data]);

                Log::info('Exchange rates updated successfully', [
                    'api' => $apiName,
                    'inr_rate' => $rate,
                    'time' => now(),
                ]);

                return true;

            } catch (\Exception $e) {
                Log::warning("API {$apiName} error: " . $e->getMessage());
                continue;
            }
        }

        Log::error('All exchange rate APIs failed');
        return false;
    }

    /**
     * Extract INR rate from different API responses
     */
    private function extractRateFromResponse(array $data, string $apiName): ?float
    {
        switch ($apiName) {
            case 'exchangerate_api':
                return $data['conversion_rates']['INR'] ?? null;
            
            case 'frankfurter':
                return $data['rates']['INR'] ?? null;
            
            case 'exchangerate_host':
                return $data['rates']['INR'] ?? null;
            
            default:
                return null;
        }
    }

    /**
     * Store exchange rate in database
     */
    private function storeRate(string $from, string $to, float $rate, array $apiResponse): void
    {
        $existingRate = ExchangeRate::where('base_currency', $from)
            ->where('target_currency', $to)
            ->first();

        ExchangeRate::updateOrCreate(
            [
                'base_currency' => $from,
                'target_currency' => $to,
            ],
            [
                'previous_rate' => $existingRate?->rate,
                'rate' => $rate,
                'fetched_at' => now(),
                'source' => 'exchangerate-api',
                'api_response' => $apiResponse,
            ]
        );

        // Cache for quick access
        Cache::put("exchange_rate_{$from}_{$to}", $rate, now()->addHours(2));
    }

    /**
     * Detect currency based on user location
     */
    private function detectCurrencyByLocation(): ?string
    {
        // Get country from IP (requires geoip package or service)
        $country = $this->getCountryFromIp();

        return match ($country) {
            'IN' => 'INR',  // India
            default => 'USD', // International default
        };
    }

    /**
     * Get country code from IP
     */
    private function getCountryFromIp(): ?string
    {
        // Try to get from request headers first (Cloudflare, etc.)
        $country = request()->header('CF-IPCountry') 
            ?? request()->server('HTTP_CF_IPCOUNTRY')
            ?? request()->server('GEOIP_COUNTRY_CODE');

        if ($country) {
            return strtoupper($country);
        }

        // Could integrate with GeoIP2 or similar service
        // For now, return null and use default
        return null;
    }

    /**
     * Get available currencies list
     */
    public function getAvailableCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Check if currency is supported
     */
    public function isSupported(string $currency): bool
    {
        return array_key_exists($currency, self::SUPPORTED_CURRENCIES);
    }

    /**
     * Get recommended payment gateway for currency
     */
    public function getPaymentGateway(string $currency): string
    {
        return match ($currency) {
            'INR' => 'razorpay',
            'USD' => 'stripe', // or 'paypal'
            default => 'stripe',
        };
    }

    /**
     * Get all supported payment gateways
     */
    public function getSupportedGateways(): array
    {
        return [
            'INR' => ['razorpay', 'cashfree', 'payu'],
            'USD' => ['stripe', 'paypal', 'payu'],
        ];
    }
}
