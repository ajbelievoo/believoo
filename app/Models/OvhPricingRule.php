<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OvhPricingRule extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'margin_percent' => 'float',
        'fixed_markup' => 'float',
        'min_margin_percent' => 'float',
        'max_margin_percent' => 'float',
        'active_from' => 'datetime',
        'active_until' => 'datetime',
        'is_active' => 'bool',
    ];

    public function scopeActive($query)
    {
        $now = now();
        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('active_until')->orWhere('active_until', '>=', $now);
            })
            ->orderByDesc('priority');
    }
}
