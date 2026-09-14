<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProof extends Model
{
    protected $fillable = [
        'milestone_id',
        'agreement_id',
        'user_id',
        'payment_method',
        'transaction_id',
        'screenshot_path',
        'notes',
        'amount',
        'status',
        'admin_notes',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(AgreementMilestone::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'easypaisa' => 'EasyPaisa',
            'jazzcash' => 'JazzCash',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst(str_replace('_', ' ', $this->payment_method)),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending_verification' => 'yellow',
            'verified' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
