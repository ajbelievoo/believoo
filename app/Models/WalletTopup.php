<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTopup extends Model
{
    protected $fillable = [
        'user_id',
        'topup_number',
        'amount',
        'currency',
        'status',
        'payment_gateway',
        'gateway_order_id',
        'gateway_payment_id',
        'gateway_signature',
        'payment_response',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_response' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($topup) {
            if (empty($topup->topup_number)) {
                $topup->topup_number = 'TOP-' . strtoupper(uniqid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
