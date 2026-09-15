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
        'key_hash',
        'key_prefix',
        'permissions',
        'scopes',
        'rate_limit',
        'requests_count',
        'allowed_ips',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'scopes' => 'array',
        'allowed_ips' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'key_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function keyable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate a new plaintext API token, compute its hash and prefix,
     * and return the plaintext token (which is shown only once).
     */
    public static function generateToken(): array
    {
        $plain = 'bel_' . hash('sha256', uniqid('api_', true) . random_bytes(32));
        $hash = hash('sha256', $plain);
        $prefix = substr($plain, 0, 8);

        return [
            'plain' => $plain,
            'hash' => $hash,
            'prefix' => $prefix,
        ];
    }

    public static function findByToken(string $token): ?self
    {
        return self::where('key_hash', hash('sha256', $token))->first();
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

    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true;
        }

        return in_array($scope, $this->scopes) ||
               in_array('*', $this->scopes);
    }

    public function isIpAllowed(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true;
        }

        foreach ($this->allowed_ips as $allowed) {
            if ($allowed === $ip) {
                return true;
            }

            if (str_contains($allowed, '/')) {
                // Basic CIDR matching; IPv4 only
                if (self::ipInCidr($ip, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function recordUsage(): void
    {
        $this->increment('requests_count');
        $this->update(['last_used_at' => now()]);
    }

    protected static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - (int) $mask);

        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
