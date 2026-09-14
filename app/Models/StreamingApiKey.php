<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class StreamingApiKey extends Model
{
    protected $table = 'streaming_api_keys';

    protected $fillable = [
        'user_id',
        'streaming_plan_id',
        'streaming_subscription_id',
        'order_id',
        'app_id',
        'app_certificate',
        'rest_api_key',
        'rest_api_secret',
        'rtmp_ingest_url',
        'webrtc_ingest_url',
        'hls_playback_url',
        'dash_playback_url',
        'web_rtc_url',
        'status',
        'expires_at',
        'last_used_at',
        'current_viewers',
        'bandwidth_used_gb',
        'storage_used_gb',
        'total_stream_minutes',
        'allowed_domains',
        'allowed_ips',
        'regeneration_locked',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'current_viewers' => 'integer',
        'bandwidth_used_gb' => 'decimal:4',
        'storage_used_gb' => 'decimal:4',
        'total_stream_minutes' => 'integer',
        'allowed_domains' => 'array',
        'allowed_ips' => 'array',
        'regeneration_locked' => 'boolean',
    ];

    protected $hidden = [
        'app_certificate',
        'rest_api_secret',
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

    public function usageLogs(): HasMany
    {
        return $this->hasMany(StreamingUsageLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canStream(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        // Check plan limits
        $plan = $this->plan;
        if (!$plan) {
            return false;
        }

        // Check bandwidth limit
        if ($this->bandwidth_used_gb >= $plan->bandwidth_gb) {
            return false;
        }

        // Check storage limit
        if ($this->storage_used_gb >= $plan->storage_gb) {
            return false;
        }

        return true;
    }

    public function regenerateKeys(): self
    {
        if ($this->regeneration_locked) {
            throw new \Exception('Key regeneration is locked for this account.');
        }

        $this->update([
            'app_id' => self::generateAppId(),
            'app_certificate' => self::generateCertificate(),
            'rest_api_key' => self::generateRestApiKey(),
            'rest_api_secret' => self::generateRestApiSecret(),
        ]);

        return $this->fresh();
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function recordUsage(float $bandwidthMb, int $durationMinutes): void
    {
        $this->increment('total_stream_minutes', $durationMinutes);
        $this->increment('bandwidth_used_gb', round($bandwidthMb / 1024, 4));
        $this->update(['last_used_at' => now()]);
    }

    public function resetMonthlyUsage(): void
    {
        $this->update([
            'bandwidth_used_gb' => 0,
            'storage_used_gb' => 0,
            'current_viewers' => 0,
        ]);
    }

    public function getStreamUrls(): array
    {
        return [
            'rtmp_ingest' => $this->rtmp_ingest_url,
            'webrtc_ingest' => $this->webrtc_ingest_url,
            'hls_playback' => $this->hls_playback_url,
            'dash_playback' => $this->dash_playback_url,
            'webrtc_playback' => $this->web_rtc_url,
        ];
    }

    public function getRemainingBandwidth(): float
    {
        $plan = $this->plan;
        if (!$plan) {
            return 0;
        }

        return max(0, $plan->bandwidth_gb - $this->bandwidth_used_gb);
    }

    public function getRemainingStorage(): float
    {
        $plan = $this->plan;
        if (!$plan) {
            return 0;
        }

        return max(0, $plan->storage_gb - $this->storage_used_gb);
    }

    public function getUsagePercentage(): int
    {
        $plan = $this->plan;
        if (!$plan || $plan->bandwidth_gb == 0) {
            return 0;
        }

        return min(100, round(($this->bandwidth_used_gb / $plan->bandwidth_gb) * 100));
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'active' => $this->isExpired() ? 'bg-yellow-500/20 text-yellow-500' : 'bg-green-500/20 text-green-500',
            'suspended' => 'bg-red-500/20 text-red-500',
            'expired' => 'bg-gray-500/20 text-gray-500',
            'cancelled' => 'bg-gray-500/20 text-gray-400',
            default => 'bg-gray-500/20 text-gray-400',
        };
    }

    // Key Generation Methods

    public static function generateAppId(): string
    {
        return 'bel_' . strtolower(substr(hash('sha256', uniqid() . random_bytes(32)), 0, 24));
    }

    public static function generateCertificate(): string
    {
        return hash('sha256', uniqid() . random_bytes(64));
    }

    public static function generateRestApiKey(): string
    {
        return 'bel_live_' . strtolower(substr(hash('sha256', uniqid() . random_bytes(32)), 0, 28));
    }

    public static function generateRestApiSecret(): string
    {
        return hash('sha512', uniqid() . random_bytes(64));
    }

    public static function generateStreamToken(string $appId, string $appCertificate, string $channel, int $uid, int $expirationSeconds = 3600): string
    {
        $timestamp = time();
        $expiration = $timestamp + $expirationSeconds;
        
        $payload = $appId . $channel . $uid . $timestamp . $expiration;
        $signature = hash_hmac('sha256', $payload, $appCertificate);
        
        return base64_encode(json_encode([
            'app_id' => $appId,
            'channel' => $channel,
            'uid' => $uid,
            'timestamp' => $timestamp,
            'expiration' => $expiration,
            'signature' => $signature,
        ]));
    }
}
