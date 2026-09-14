<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StreamingPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_cycle',
        'max_viewers',
        'max_bitrate',
        'max_resolution',
        'bandwidth_gb',
        'storage_gb',
        'stream_count',
        'rtmp_support',
        'webrtc_support',
        'hls_support',
        'dash_support',
        'recording_enabled',
        'transcoding_enabled',
        'adaptive_bitrate',
        'low_latency',
        'features',
        'is_active',
        'is_addon',
        'addon_price',
        'delivery_method', // vps_embedded or cloud_hosted
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'addon_price' => 'decimal:2',
        'max_viewers' => 'integer',
        'max_bitrate' => 'integer',
        'max_resolution' => 'integer',
        'bandwidth_gb' => 'integer',
        'storage_gb' => 'integer',
        'stream_count' => 'integer',
        'rtmp_support' => 'boolean',
        'webrtc_support' => 'boolean',
        'hls_support' => 'boolean',
        'dash_support' => 'boolean',
        'recording_enabled' => 'boolean',
        'transcoding_enabled' => 'boolean',
        'adaptive_bitrate' => 'boolean',
        'low_latency' => 'boolean',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_addon' => 'boolean',
        'delivery_method' => 'string', // vps_embedded or cloud_hosted
        'sort_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($plan) {
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(StreamingApiKey::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StreamingSubscription::class);
    }

    // Spec aliases — map new column names to existing ones
    public function getBandwidthLimitGbAttribute(): int
    {
        return $this->bandwidth_limit_gb ?? $this->bandwidth_gb ?? 1000;
    }

    public function getMaxConcurrentViewersAttribute(): int
    {
        return $this->attributes['max_concurrent_viewers'] ?? $this->max_viewers ?? 100;
    }

    public function getStorageLimitGbAttribute(): int
    {
        return $this->attributes['storage_limit_gb'] ?? $this->storage_gb ?? 50;
    }

    public function getPriceMonthlyAttribute(): float
    {
        return (float) ($this->attributes['price_monthly'] ?? $this->price ?? 0);
    }

    public function getMaxProjectsAttribute(): int
    {
        return $this->attributes['max_projects'] ?? 5;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAddon($query)
    {
        return $query->where('is_addon', true);
    }

    public function scopeStandalone($query)
    {
        return $query->where('is_addon', false);
    }

    public function scopeVpsEmbedded($query)
    {
        return $query->where('delivery_method', 'vps_embedded');
    }

    public function scopeCloudHosted($query)
    {
        return $query->where('delivery_method', 'cloud_hosted');
    }

    public function isVpsEmbedded(): bool
    {
        return $this->delivery_method === 'vps_embedded';
    }

    public function isCloudHosted(): bool
    {
        return $this->delivery_method === 'cloud_hosted';
    }

    public function getFormattedBitrate(): string
    {
        if ($this->max_bitrate >= 1000) {
            return round($this->max_bitrate / 1000, 1) . ' Mbps';
        }
        return $this->max_bitrate . ' kbps';
    }

    public function getFormattedResolution(): string
    {
        return match($this->max_resolution) {
            480 => '480p (SD)',
            720 => '720p (HD)',
            1080 => '1080p (Full HD)',
            1440 => '1440p (2K)',
            2160 => '2160p (4K)',
            default => $this->max_resolution . 'p',
        };
    }

    public function getPriceForBillingCycle(string $cycle): float
    {
        $multiplier = match($cycle) {
            'monthly' => 1,
            'quarterly' => 3,
            'half_yearly' => 6,
            'yearly' => 12,
            default => 1,
        };

        $discount = match($cycle) {
            'quarterly' => 0.05, // 5% discount
            'half_yearly' => 0.10, // 10% discount
            'yearly' => 0.20, // 20% discount
            default => 0,
        };

        $price = $this->price * $multiplier;
        return round($price * (1 - $discount), 2);
    }

    public function getFeaturesList(): array
    {
        $builtIn = [
            $this->max_viewers . ' Concurrent Viewers',
            $this->getFormattedBitrate() . ' Bitrate',
            $this->getFormattedResolution(),
            $this->bandwidth_gb . 'GB Bandwidth/month',
            $this->storage_gb . 'GB Storage',
            $this->stream_count . ' Concurrent Stream' . ($this->stream_count > 1 ? 's' : ''),
        ];

        if ($this->rtmp_support) $builtIn[] = 'RTMP Ingest';
        if ($this->webrtc_support) $builtIn[] = 'WebRTC Support';
        if ($this->hls_support) $builtIn[] = 'HLS Playback';
        if ($this->dash_support) $builtIn[] = 'DASH Playback';
        if ($this->recording_enabled) $builtIn[] = 'Cloud Recording';
        if ($this->transcoding_enabled) $builtIn[] = 'Live Transcoding';
        if ($this->adaptive_bitrate) $builtIn[] = 'Adaptive Bitrate (ABR)';
        if ($this->low_latency) $builtIn[] = 'Low Latency Streaming';

        $features = is_array($this->features) ? $this->features : (json_decode($this->features, true) ?? []);
        return array_merge($builtIn, $features);
    }

    public function getStatusBadgeClass(): string
    {
        return match(true) {
            !$this->is_active => 'bg-red-500/20 text-red-500',
            $this->is_addon => 'bg-purple-500/20 text-purple-500',
            default => 'bg-green-500/20 text-green-500',
        };
    }

    public function getStatusText(): string
    {
        return match(true) {
            !$this->is_active => 'Inactive',
            $this->is_addon => 'VPS Add-on',
            default => 'Active',
        };
    }
}
