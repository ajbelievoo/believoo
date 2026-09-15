<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Service;

class OvhProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'category',
        'family',
        'plan_code',
        'invoice_name',
        'description',
        'cpu_cores',
        'ram_gb',
        'disk_gb',
        'disk_type',
        'bandwidth_mbps',
        'currency',
        'price_monthly',
        'cost_price',
        'sale_price',
        'commission_percent',
        'durations',
        'ovh_config',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'cpu_cores' => 'integer',
        'ram_gb' => 'integer',
        'disk_gb' => 'integer',
        'bandwidth_mbps' => 'integer',
        'price_monthly' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'durations' => 'array',
        'ovh_config' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->invoice_name ?: $this->plan_code;
    }

    public function getFormattedMonthlyPriceAttribute(): string
    {
        return '₹' . number_format($this->price_monthly ?: 0, 0) . '/mo';
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'VPS' => 'VPS',
            'DEDICATED' => 'Dedicated Servers',
            'WEB_HOSTING' => 'Web Hosting',
            'PUBLIC_CLOUD' => 'Public Cloud',
            'PRIVATE_CLOUD' => 'Private Cloud',
            'DOMAINS' => 'Domains',
            default => $this->category,
        };
    }
}
