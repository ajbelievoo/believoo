<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'content',
        'icon',
        'image',
        'price',
        'price_label',
        'pricing_tiers',
        'billing_cycles',
        'features',
        'is_active',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'faqs',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'pricing_tiers' => 'array',
        'billing_cycles' => 'array',
        'features' => 'array',
        'faqs' => 'array',
    ];
    
    // Default billing cycles with recommended discounts
    public static function getDefaultBillingCycles(): array
    {
        return [
            ['months' => 1, 'label' => 'Monthly', 'discount_percent' => 0, 'recommended' => false],
            ['months' => 3, 'label' => '3 Months', 'discount_percent' => 5, 'recommended' => false],
            ['months' => 6, 'label' => '6 Months', 'discount_percent' => 10, 'recommended' => true],
            ['months' => 12, 'label' => 'Yearly', 'discount_percent' => 20, 'recommended' => true],
        ];
    }
    
    // Calculate price for a specific billing cycle
    public function ovhProduct()
    {
        return $this->hasOne(OvhProduct::class, 'service_id');
    }

    public function getPriceForCycle(int $months, ?string $tierName = null): float
    {
        $basePrice = $this->price;
        
        // Get tier price if specified
        if ($tierName && !empty($this->pricing_tiers)) {
            foreach ($this->pricing_tiers as $tier) {
                if ($tier['name'] === $tierName) {
                    $basePrice = $tier['price'];
                    break;
                }
            }
        }
        
        // Get discount for this billing cycle
        $discountPercent = 0;
        if (!empty($this->billing_cycles)) {
            foreach ($this->billing_cycles as $cycle) {
                if ($cycle['months'] === $months) {
                    $discountPercent = $cycle['discount_percent'] ?? 0;
                    break;
                }
            }
        }
        
        // Calculate discounted price
        $monthlyPrice = $basePrice * (1 - $discountPercent / 100);
        return round($monthlyPrice * $months, 2);
    }
}
