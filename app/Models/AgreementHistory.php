<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementHistory extends Model
{
    protected $fillable = [
        'agreement_id',
        'user_id',
        'action',
        'description',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionIconAttribute(): string
    {
        return match ($this->action) {
            'created' => 'heroicon-o-document-plus',
            'sent' => 'heroicon-o-paper-airplane',
            'viewed' => 'heroicon-o-eye',
            'signed' => 'heroicon-o-pencil-square',
            'updated' => 'heroicon-o-pencil',
            'cancelled' => 'heroicon-o-x-circle',
            'milestone_completed' => 'heroicon-o-check-circle',
            'payment_received' => 'heroicon-o-currency-dollar',
            default => 'heroicon-o-clock',
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created' => 'gray',
            'sent' => 'blue',
            'viewed' => 'yellow',
            'signed' => 'green',
            'updated' => 'purple',
            'cancelled' => 'red',
            'milestone_completed' => 'blue',
            'payment_received' => 'green',
            default => 'gray',
        };
    }
}
