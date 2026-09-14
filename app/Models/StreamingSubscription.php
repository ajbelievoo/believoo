<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StreamingSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'streaming_plan_id',
        'order_id',
        'vps_hosting_id',
        'delivery_method',
        'status',
        'price',
        'starts_at',
        'ends_at',
        'activated_at',
        'streaming_config',
    ];

    protected $casts = [
        'user_id'           => 'integer',
        'streaming_plan_id' => 'integer',
        'order_id'          => 'integer',
        'vps_hosting_id'    => 'integer',
        'delivery_method'   => 'string',
        'price'             => 'decimal:2',
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
        'activated_at'      => 'datetime',
        'streaming_config'  => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(StreamingPlan::class, 'streaming_plan_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(UserHosting::class, 'vps_hosting_id');
    }

    public function isVpsEmbedded(): bool
    {
        return $this->delivery_method === 'vps_embedded' || $this->vps_hosting_id !== null;
    }

    public function isCloudHosted(): bool
    {
        return $this->delivery_method === 'cloud_hosted' || ($this->delivery_method !== 'vps_embedded' && $this->vps_hosting_id === null);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(StreamingProject::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
