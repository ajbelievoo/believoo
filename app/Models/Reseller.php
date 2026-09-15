<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reseller extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'bool',
        'approved_at' => 'datetime',
        'default_margin_percent' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function customers()
    {
        return $this->hasMany(ResellerCustomer::class);
    }

    public function brandedDomain(): ?string
    {
        return $this->custom_domain ?? config('app.url');
    }

    public function applyMargin(float $basePrice): float
    {
        return round($basePrice * (1 + $this->default_margin_percent / 100), 2);
    }
}
