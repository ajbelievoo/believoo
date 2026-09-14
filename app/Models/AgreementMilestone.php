<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementMilestone extends Model
{
    protected $fillable = [
        'agreement_id',
        'phase_name',
        'description',
        'payment_amount',
        'timeline_month',
        'due_date',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'completed' => 'blue',
            'paid' => 'green',
            default => 'gray',
        };
    }
}
