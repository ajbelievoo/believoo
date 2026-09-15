<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResellerCustomer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'custom_prices' => 'array',
        'settings' => 'array',
        'onboarded_at' => 'datetime',
        'margin_percent' => 'float',
    ];

    public function reseller()
    {
        return $this->belongsTo(Reseller::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
