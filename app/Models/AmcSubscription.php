<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmcSubscription extends Model
{
    protected $fillable = [
        'agreement_id',
        'client_id',
        'subscription_number',
        'plan_type',
        'monthly_amount',
        'start_date',
        'end_date',
        'status',
        'last_reminder_sent',
        'reminder_count',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_amount' => 'decimal:2',
        'last_reminder_sent' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            if (empty($subscription->subscription_number)) {
                $subscription->subscription_number = 'AMC-' . strtoupper(uniqid()) . '-' . date('Y');
            }
        });
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->end_date->isFuture();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->end_date->diffInDays(now()) <= $days && $this->end_date->isFuture();
    }

    public function daysUntilExpiry(): int
    {
        return now()->diffInDays($this->end_date, false);
    }

    public function getPlanLabelAttribute(): string
    {
        return match ($this->plan_type) {
            'basic' => 'Basic Support',
            'standard' => 'Standard AMC',
            'premium' => 'Premium AMC',
            default => 'Standard AMC',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'green',
            'pending' => 'yellow',
            'expired' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
