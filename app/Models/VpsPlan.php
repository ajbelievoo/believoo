<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VpsPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'display_name',
        'description',
        'cpu_cores',
        'memory_gb',
        'disk_gb',
        'disk_type',
        'bandwidth',
        'unlimited_traffic',
        'daily_backup',
        'price_monthly',
        'base_price_usd',
        'sale_price_usd',
        'cost_price',
        'setup_fee',
        'installation_free',
        'features',
        'is_active',
        'is_sold_out',
        'is_recommended',
        'sort_order',
        'ovh_plan_code',
        'ovh_config',
        'streaming_addon_price',
    ];

    protected $casts = [
        'features' => 'array',
        'ovh_config' => 'array',
        'is_active' => 'boolean',
        'is_sold_out' => 'boolean',
        'is_recommended' => 'boolean',
        'unlimited_traffic' => 'boolean',
        'daily_backup' => 'boolean',
        'installation_free' => 'boolean',
        'price_monthly' => 'decimal:2',
        'base_price_usd' => 'decimal:2',
        'sale_price_usd' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'streaming_addon_price' => 'decimal:2',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_sold_out' => false,
        'is_recommended' => false,
        'unlimited_traffic' => true,
        'daily_backup' => true,
        'installation_free' => true,
        'disk_type' => 'SSD',
    ];

    // Categories
    const CATEGORIES = [
        'vps_2026' => [
            'name' => 'VPS 2026',
            'icon' => 'fa-server',
            'description' => 'Virtually isolated CPU, RAM, and storage. Reliable performance at lower prices.',
        ],
        'n8n' => [
            'name' => 'n8n VPS',
            'icon' => 'fa-robot',
            'description' => 'Automate everything. Maintain control. Perfect for workflow automation.',
        ],
        'plesk' => [
            'name' => 'Plesk VPS',
            'icon' => 'fa-cpanel',
            'description' => 'Web hosting made simple. A unified interface for managing websites.',
        ],
        'cpanel' => [
            'name' => 'cPanel VPS',
            'icon' => 'fa-server',
            'description' => 'Centralized web hosting. Unified management with industry-standard cPanel.',
        ],
        'wordpress' => [
            'name' => 'WordPress VPS',
            'icon' => 'fa-wordpress',
            'description' => 'Customizable and scalable server. Unlimited traffic for WordPress sites.',
        ],
        'apps' => [
            'name' => 'Apps, OS, and Panels',
            'icon' => 'fa-th-large',
            'description' => 'Available OS and software. One-click app installations.',
        ],
    ];

    /**
     * Get category info
     */
    public function getCategoryInfo(): array
    {
        return self::CATEGORIES[$this->category] ?? [
            'name' => ucfirst($this->category),
            'icon' => 'fa-server',
            'description' => '',
        ];
    }

    /**
     * Scope: Active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Available (not sold out)
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_sold_out', false);
    }

    /**
     * Scope: Order by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₹' . number_format($this->price_monthly, 0) . '/month';
    }

    /**
     * Get price in specified currency
     */
    public function getPriceInCurrency(?string $currency = null): array
    {
        $currency = $currency ?? (auth()->check() ? auth()->user()->preferred_currency : 'USD');
        
        // Use base_price_usd if available, otherwise fallback to price_monthly
        $baseUsd = $this->base_price_usd ?? ($this->price_monthly / 83); // Approximate conversion
        
        // Calculate sale price if applicable
        $saleUsd = $this->sale_price_usd ?? null;
        
        if ($currency === 'USD') {
            return [
                'currency' => 'USD',
                'symbol' => '$',
                'amount' => round($saleUsd ?? $baseUsd, 2),
                'original_amount' => $saleUsd ? round($baseUsd, 2) : null,
                'is_sale' => !is_null($saleUsd),
                'formatted' => '$' . number_format($saleUsd ?? $baseUsd, 2),
            ];
        }
        
        // Convert to INR
        $inrRate = ExchangeRate::getUsdToInrRate();
        $inrAmount = round(($saleUsd ?? $baseUsd) * $inrRate);
        $originalInr = $saleUsd ? round($baseUsd * $inrRate) : null;
        
        return [
            'currency' => 'INR',
            'symbol' => '₹',
            'amount' => $inrAmount,
            'original_amount' => $originalInr,
            'is_sale' => !is_null($saleUsd),
            'formatted' => '₹' . number_format($inrAmount, 0),
        ];
    }

    /**
     * Get price attribute (alias for price_monthly in INR)
     * Fixes blades that use $plan->price instead of $plan->price_monthly
     */
    public function getPriceAttribute(): float
    {
        return (float) ($this->attributes['price_monthly'] ?? 0);
    }

    /**
     * Get monthly price attribute (dynamic based on currency)
     */
    public function getDisplayPriceAttribute(): float
    {
        $priceData = $this->getPriceInCurrency();
        return $priceData['amount'];
    }

    /**
     * Get formatted price with currency symbol
     */
    public function getFormattedPriceWithCurrencyAttribute(): string
    {
        $priceData = $this->getPriceInCurrency();
        return $priceData['formatted'] . '/month';
    }

    /**
     * Get profit margin
     */
    public function getProfitMarginAttribute(): float
    {
        if (!$this->cost_price) {
            return 0;
        }
        return $this->price_monthly - $this->cost_price;
    }

    /**
     * Get profit percentage
     */
    public function getProfitPercentageAttribute(): float
    {
        if (!$this->cost_price) {
            return 0;
        }
        return (($this->price_monthly - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Get all categories with counts
     */
    public static function getCategoriesWithCounts(): array
    {
        $result = [];
        
        foreach (self::CATEGORIES as $key => $info) {
            $count = self::where('category', $key)
                ->where('is_active', true)
                ->count();
            
            $result[$key] = array_merge($info, [
                'count' => $count,
                'slug' => $key,
            ]);
        }
        
        return $result;
    }

    /**
     * Get plans grouped by category
     */
    public static function getPlansByCategory(): array
    {
        $result = [];
        
        foreach (self::CATEGORIES as $key => $info) {
            $plans = self::where('category', $key)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            
            $result[$key] = [
                'info' => $info,
                'plans' => $plans,
            ];
        }
        
        return $result;
    }

    /**
     * Check if plan is available for purchase
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !$this->is_sold_out;
    }

    /**
     * Get display features
     */
    public function getDisplayFeaturesAttribute(): array
    {
        return $this->features ?? [
            $this->cpu_cores . ' vCores',
            $this->memory_gb . ' GB RAM',
            $this->disk_gb . ' GB ' . $this->disk_type,
            'Daily backup',
            'Unlimited traffic',
            $this->bandwidth,
        ];
    }
}
