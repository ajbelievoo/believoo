<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

class StreamingProject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'streaming_subscription_id',
        'name',
        'app_id',
        'app_certificate',
        'rest_api_key',
        'region',
        'rtmp_url',
        'webrtc_url',
        'status',
    ];

    protected $hidden = ['app_certificate', 'rest_api_key'];

    protected $casts = [
        'user_id'                   => 'integer',
        'streaming_subscription_id' => 'integer',
        'deleted_at'                => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(StreamingSubscription::class, 'streaming_subscription_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(StreamingUsageLog::class, 'streaming_project_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Decrypt helpers ──────────────────────────────────────────────

    public function getDecryptedCertificate(): string
    {
        return Crypt::decryptString($this->app_certificate);
    }

    public function getDecryptedRestApiKey(): string
    {
        return Crypt::decryptString($this->rest_api_key);
    }

    // ── Masking helpers — last 8 chars visible ───────────────────────

    public function getMaskedCertificate(): string
    {
        try {
            $plain = $this->getDecryptedCertificate();
            return str_repeat('*', max(0, strlen($plain) - 8)) . substr($plain, -8);
        } catch (\Exception $e) {
            return '************************';
        }
    }

    public function getMaskedRestApiKey(): string
    {
        try {
            $plain = $this->getDecryptedRestApiKey();
            return str_repeat('*', max(0, strlen($plain) - 8)) . substr($plain, -8);
        } catch (\Exception $e) {
            return '************************';
        }
    }

    // ── Usage / metrics ──────────────────────────────────────────────

    public function currentPeriodBandwidthGb(): float
    {
        return (float) $this->usageLogs()
            ->whereMonth('recorded_at', now()->month)
            ->whereYear('recorded_at', now()->year)
            ->sum('bandwidth_used_gb');
    }

    public function bandwidthPercentage(): int
    {
        $limit = $this->subscription?->plan?->bandwidth_limit_gb ?? 1;
        if ($limit <= 0) return 0;
        return min(100, (int) round(($this->currentPeriodBandwidthGb() / $limit) * 100));
    }

    // ── Region label ─────────────────────────────────────────────────

    public function getRegionLabelAttribute(): string
    {
        return match($this->region) {
            'in'     => '🇮🇳 India',
            'global' => '🌐 Global',
            default  => ucfirst($this->region),
        };
    }
}
