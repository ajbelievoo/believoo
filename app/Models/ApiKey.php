<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'keyable_type',
        'keyable_id',
        'name',
        'key',
        'secret',
        'permissions',
        'rate_limit',
        'requests_count',
        'allowed_ips',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keyable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function generate(): string
    {
        return 'bel_' . hash('sha256', uniqid() . random_bytes(32));
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return true; // No restrictions
        }

        return in_array($permission, $this->permissions) || 
               in_array('*', $this->permissions);
    }

    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    public function recordUsage(): void
    {
        $this->increment('requests_count');
        $this->update(['last_used_at' => now()]);
    }
}
