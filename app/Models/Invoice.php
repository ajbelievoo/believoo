<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'invoice_number',
        'invoice_type',
        'description',
        'amount',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'payment_gateway',
        'payment_id',
        'paid_at',
        'due_date',
        'line_items',
        'billing_details',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_date' => 'date',
        'line_items' => 'array',
        'billing_details' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-' . strtoupper(uniqid());
            }
            if (empty($invoice->total_amount)) {
                $invoice->total_amount = $invoice->amount + $invoice->tax_amount;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->isPast();
    }

    public function markAsPaid(string $gateway = 'manual', ?string $paymentId = null, array $response = []): void
    {
        $this->update([
            'status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_id' => $paymentId,
            'paid_at' => now(),
        ]);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'paid' => 'bg-green-500/20 text-green-500',
            'pending' => $this->isOverdue() ? 'bg-red-500/20 text-red-500' : 'bg-yellow-500/20 text-yellow-500',
            'cancelled' => 'bg-gray-500/20 text-gray-400',
            'refunded' => 'bg-blue-500/20 text-blue-500',
            default => 'bg-gray-500/20 text-gray-400',
        };
    }
}
