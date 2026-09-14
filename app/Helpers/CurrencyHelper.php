<?php

use App\Services\CurrencyService;

if (!function_exists('currency_convert')) {
    /**
     * Convert USD to current currency
     */
    function currency_convert(float $amountUsd, ?string $targetCurrency = null): float
    {
        return app(CurrencyService::class)->convertFromUsd($amountUsd, $targetCurrency);
    }
}

if (!function_exists('currency_format')) {
    /**
     * Format price with currency symbol
     */
    function currency_format(float $amount, ?string $currency = null): string
    {
        return app(CurrencyService::class)->formatPrice($amount, $currency);
    }
}

if (!function_exists('currency_display_price')) {
    /**
     * Get full display price array
     */
    function currency_display_price(float $basePriceUsd, ?string $currency = null): array
    {
        return app(CurrencyService::class)->getDisplayPrice($basePriceUsd, $currency);
    }
}

if (!function_exists('current_currency')) {
    /**
     * Get current currency code
     */
    function current_currency(): string
    {
        return app(CurrencyService::class)->getUserCurrency();
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get currency symbol
     */
    function currency_symbol(?string $currency = null): string
    {
        $currency = $currency ?? current_currency();
        return CurrencyService::SUPPORTED_CURRENCIES[$currency]['symbol'] ?? '$';
    }
}

if (!function_exists('is_inr')) {
    /**
     * Check if current currency is INR
     */
    function is_inr(): bool
    {
        return current_currency() === 'INR';
    }
}

if (!function_exists('is_usd')) {
    /**
     * Check if current currency is USD
     */
    function is_usd(): bool
    {
        return current_currency() === 'USD';
    }
}

if (!function_exists('payment_gateway_for_currency')) {
    /**
     * Get recommended payment gateway for current currency
     */
    function payment_gateway_for_currency(): string
    {
        return app(CurrencyService::class)->getPaymentGateway(current_currency());
    }
}
