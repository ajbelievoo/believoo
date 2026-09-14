<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgreementWorkItem extends Model
{
    protected $fillable = [
        'agreement_id',
        'item_name',
        'description',
        'amount',
        'timeline_days',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }
}
