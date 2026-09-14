<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementInvoice extends Model
{
    protected $fillable = [
        'agreement_id',
        'milestone_id',
        'client_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'tax_type',
        'discount_amount',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'payment_method',
        'sent_at',
        'paid_at',
        'notes',
        'terms_conditions',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'AG-INV-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            }
            if (empty($invoice->balance_due)) {
                $invoice->balance_due = $invoice->total_amount - $invoice->amount_paid;
            }
        });
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(AgreementMilestone::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    public function markAsPaid(string $method = 'online'): void
    {
        $this->update([
            'status' => 'paid',
            'payment_method' => $method,
            'paid_at' => now(),
            'amount_paid' => $this->total_amount,
            'balance_due' => 0,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'sent' => 'info',
            'draft' => 'gray',
            'overdue' => 'danger',
            'cancelled' => 'warning',
            default => 'gray',
        };
    }
}
