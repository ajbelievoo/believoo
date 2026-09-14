<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_id',
        'referral_code',
        'referred_email',
        'referred_name',
        'status',
        'registered_at',
        'converted_at',
        'rewarded_at',
        'discount_amount',
        'reward_amount',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'converted_at' => 'datetime',
        'rewarded_at' => 'datetime',
        'discount_amount' => 'decimal:2',
        'reward_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($referral) {
            if (empty($referral->referral_code)) {
                $referral->referral_code = 'REF' . strtoupper(substr(uniqid(), -8));
            }
        });
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'rewarded' => 'green',
            'converted' => 'blue',
            'registered' => 'yellow',
            'pending' => 'gray',
            default => 'gray',
        };
    }

    public function isRewarded(): bool
    {
        return $this->status === 'rewarded';
    }

    public function markAsRegistered(int $userId): void
    {
        $this->update([
            'referred_id' => $userId,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        // Update referrer stats
        $this->referrer->increment('total_referrals');
    }

    public function markAsConverted(): void
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
            'discount_amount' => $this->calculateDiscount(),
        ]);
    }

    public function markAsRewarded(): void
    {
        $discountAmount = $this->calculateDiscount();
        $rewardAmount = $this->calculateReward();

        $this->update([
            'status' => 'rewarded',
            'rewarded_at' => now(),
            'discount_amount' => $discountAmount,
            'reward_amount' => $rewardAmount,
        ]);

        // Add discount to referrer's balance
        $this->referrer->increment('referral_discount_balance', $discountAmount);
        $this->referrer->increment('successful_referrals');

        // Notify referrer
        $this->referrer->notify(new \App\Notifications\ReferralRewardNotification($this));
    }

    protected function calculateDiscount(): float
    {
        // 10% discount on next milestone payment
        return 10.00;
    }

    protected function calculateReward(): float
    {
        // Can be extended for cash rewards, credits, etc.
        return 0.00;
    }
}
