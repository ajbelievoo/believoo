<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'base_currency',
        'target_currency',
        'rate',
        'previous_rate',
        'fetched_at',
        'source',
        'api_response',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'previous_rate' => 'decimal:6',
        'fetched_at' => 'datetime',
        'api_response' => 'array',
    ];

    /**
     * Get current exchange rate
     */
    public static function getRate(string $from, string $to): ?float
    {
        $rate = self::where('base_currency', $from)
            ->where('target_currency', $to)
            ->latest('fetched_at')
            ->first();

        return $rate?->rate;
    }

    /**
     * Get USD to INR rate (most common conversion)
     */
    public static function getUsdToInrRate(): float
    {
        return self::getRate('USD', 'INR') ?? 83.00; // Fallback rate
    }

    /**
     * Convert USD to target currency
     */
    public static function convertFromUsd(float $amount, string $to = 'INR'): float
    {
        if ($to === 'USD') {
            return $amount;
        }

        $rate = self::getRate('USD', $to) ?? 83.00;
        return round($amount * $rate);
    }

    /**
     * Convert any currency to USD
     */
    public static function convertToUsd(float $amount, string $from = 'INR'): float
    {
        if ($from === 'USD') {
            return $amount;
        }

        $rate = self::getRate('USD', $from) ?? 83.00;
        return round($amount / $rate, 2);
    }

    /**
     * Check if rate is stale (older than 2 hours)
     */
    public function isStale(): bool
    {
        return $this->fetched_at->diffInHours(now()) > 2;
    }

    /**
     * Get rate change percentage
     */
    public function getChangePercent(): ?float
    {
        if (!$this->previous_rate || $this->previous_rate == 0) {
            return null;
        }

        return round((($this->rate - $this->previous_rate) / $this->previous_rate) * 100, 2);
    }
}
