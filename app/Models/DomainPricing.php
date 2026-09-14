<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainPricing extends Model
{
    use HasFactory;

    protected $table = 'domain_pricing';

    protected $fillable = [
        'domain_provider_id',
        'tld',
        'years',
        'provider_register_price',
        'provider_renew_price',
        'provider_transfer_price',
        'selling_register_price',
        'selling_renew_price',
        'selling_transfer_price',
        'currency',
        'is_promo',
        'promo_start',
        'promo_end',
        'promo_price',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'years' => 'integer',
        'provider_register_price' => 'decimal:2',
        'provider_renew_price' => 'decimal:2',
        'provider_transfer_price' => 'decimal:2',
        'selling_register_price' => 'decimal:2',
        'selling_renew_price' => 'decimal:2',
        'selling_transfer_price' => 'decimal:2',
        'register_profit' => 'decimal:2',
        'renew_profit' => 'decimal:2',
        'is_promo' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'promo_start' => 'date',
        'promo_end' => 'date',
    ];

    protected $attributes = [
        'years' => 1,
        'currency' => 'USD',
        'is_active' => true,
        'is_promo' => false,
        'priority' => 100,
    ];

    /**
     * Domain provider relationship
     */
    public function domainProvider()
    {
        return $this->belongsTo(DomainProvider::class);
    }

    /**
     * Scope: Active pricing
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By TLD
     */
    public function scopeByTld($query, string $tld)
    {
        return $query->where('tld', $tld);
    }

    /**
     * Scope: By provider
     */
    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('domain_provider_id', $providerId);
    }

    /**
     * Scope: Promo pricing
     */
    public function scopePromo($query)
    {
        return $query->where('is_promo', true)
            ->where('promo_start', '<=', now())
            ->where('promo_end', '>=', now());
    }

    /**
     * Get best price (considering promo)
     */
    public function getBestRegisterPrice(): float
    {
        if ($this->isPromoActive()) {
            return $this->promo_price ?? $this->selling_register_price;
        }

        return $this->selling_register_price;
    }

    /**
     * Check if promo is active
     */
    public function isPromoActive(): bool
    {
        if (!$this->is_promo) {
            return false;
        }

        $now = now();
        return $now >= $this->promo_start && $now <= $this->promo_end;
    }

    /**
     * Calculate profit
     */
    public function calculateProfit(string $type = 'register'): float
    {
        $providerPrice = $type === 'register' ? $this->provider_register_price : 
                        ($type === 'renew' ? $this->provider_renew_price : $this->provider_transfer_price);
        
        $sellingPrice = $type === 'register' ? $this->getBestRegisterPrice() : 
                       ($type === 'renew' ? $this->selling_renew_price : $this->selling_transfer_price);

        return $sellingPrice - $providerPrice;
    }

    /**
     * Get formatted TLD
     */
    public function getFormattedTldAttribute(): string
    {
        return '.' . $this->tld;
    }

    /**
     * Get price summary
     */
    public function getPriceSummaryAttribute(): array
    {
        return [
            'register' => [
                'provider' => $this->provider_register_price,
                'selling' => $this->getBestRegisterPrice(),
                'profit' => $this->calculateProfit('register'),
            ],
            'renew' => [
                'provider' => $this->provider_renew_price,
                'selling' => $this->selling_renew_price,
                'profit' => $this->calculateProfit('renew'),
            ],
            'transfer' => [
                'provider' => $this->provider_transfer_price,
                'selling' => $this->selling_transfer_price,
                'profit' => $this->calculateProfit('transfer'),
            ],
        ];
    }

    /**
     * Bulk update pricing
     */
    public static function updatePricing(int $providerId, string $tld, array $prices): self
    {
        $pricing = self::firstOrNew([
            'domain_provider_id' => $providerId,
            'tld' => $tld,
            'years' => $prices['years'] ?? 1,
        ]);

        $pricing->fill($prices);
        $pricing->save();

        return $pricing;
    }

    /**
     * Get cheapest provider for TLD
     */
    public static function getCheapestProvider(string $tld, int $years = 1, string $type = 'register'): ?self
    {
        $query = self::active()
            ->byTld($tld)
            ->where('years', $years)
            ->with('domainProvider');

        if ($type === 'register') {
            $query->orderBy('selling_register_price');
        } elseif ($type === 'renew') {
            $query->orderBy('selling_renew_price');
        } else {
            $query->orderBy('selling_transfer_price');
        }

        return $query->first();
    }

    /**
     * Set promo pricing
     */
    public function setPromo(float $price, string $start, string $end): void
    {
        $this->update([
            'is_promo' => true,
            'promo_price' => $price,
            'promo_start' => $start,
            'promo_end' => $end,
        ]);
    }

    /**
     * Clear promo pricing
     */
    public function clearPromo(): void
    {
        $this->update([
            'is_promo' => false,
            'promo_price' => null,
            'promo_start' => null,
            'promo_end' => null,
        ]);
    }
}
