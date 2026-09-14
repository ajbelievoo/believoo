<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DnsRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_domain_id',
        'user_id',
        'record_type',
        'name',
        'value',
        'ttl',
        'priority',
        'weight',
        'port',
        'protocol',
        'service',
        'is_active',
        'is_system',
        'is_provisioned',
        'source',
        'notes',
        'last_verified_at',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'is_provisioned' => 'boolean',
        'last_verified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Valid DNS record types
     */
    const VALID_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV', 'CAA'];

    /**
     * BelieVoo default nameservers
     */
    const BELIEVOO_NS = ['ns1.believoo.com', 'ns2.believoo.com'];

    /**
     * Relationship: Domain
     */
    public function userDomain()
    {
        return $this->belongsTo(UserDomain::class);
    }

    /**
     * Relationship: User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Active records
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By domain
     */
    public function scopeByDomain($query, int $domainId)
    {
        return $query->where('user_domain_id', $domainId);
    }

    /**
     * Scope: By record type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('record_type', strtoupper($type));
    }

    /**
     * Scope: System records
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope: Provisioned records (from VPS)
     */
    public function scopeProvisioned($query)
    {
        return $query->where('is_provisioned', true);
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Create an A record for VPS
     */
    public static function createVpsARecord(int $userId, int $domainId, string $subdomain, string $ip, ?string $notes = null): self
    {
        return self::create([
            'user_domain_id' => $domainId,
            'user_id' => $userId,
            'record_type' => 'A',
            'name' => $subdomain,
            'value' => $ip,
            'ttl' => 3600,
            'is_active' => true,
            'is_provisioned' => true,
            'source' => 'vps',
            'notes' => $notes ?? 'Auto-provisioned from VPS creation',
        ]);
    }

    /**
     * Create default NS records for a domain (idempotent).
     */
    public static function createDefaultNsRecords(int $userId, int $domainId): void
    {
        foreach (self::BELIEVOO_NS as $ns) {
            self::firstOrCreate(
                [
                    'user_domain_id' => $domainId,
                    'record_type' => 'NS',
                    'name' => '@',
                    'value' => $ns,
                ],
                [
                    'user_id' => $userId,
                    'ttl' => 86400,
                    'is_active' => true,
                    'is_system' => true,
                    'source' => 'system',
                    'notes' => 'BelieVoo default nameserver',
                ]
            );
        }
    }

    /**
     * Create a complete default starter zone for a domain (idempotent).
     *
     * Builds the records a working website needs:
     *   - NS records (ns1/ns2.believoo.com)
     *   - root A (@)            -> website IP
     *   - www A                 -> website IP
     *   - ns1 / ns2 A           -> DNS server IP (in-zone glue support)
     *
     * @param string      $ip        Website/hosting IP for @ and www
     * @param string|null $nsIp      DNS server IP for ns1/ns2 (defaults to $ip)
     */
    public static function createDefaultWebRecords(int $userId, int $domainId, string $ip, ?string $nsIp = null): void
    {
        $nsIp = $nsIp ?: $ip;

        // NS records
        self::createDefaultNsRecords($userId, $domainId);

        // Root + www A records pointing to the website host
        foreach (['@', 'www'] as $name) {
            self::firstOrCreate(
                [
                    'user_domain_id' => $domainId,
                    'record_type' => 'A',
                    'name' => $name,
                ],
                [
                    'user_id' => $userId,
                    'value' => $ip,
                    'ttl' => 3600,
                    'is_active' => true,
                    'is_system' => true,
                    'source' => 'system',
                    'notes' => 'Default web A record',
                ]
            );
        }

        // ns1 / ns2 A records (support in-zone references)
        foreach (self::BELIEVOO_NS as $ns) {
            $host = explode('.', $ns)[0]; // ns1 / ns2
            self::firstOrCreate(
                [
                    'user_domain_id' => $domainId,
                    'record_type' => 'A',
                    'name' => $host,
                ],
                [
                    'user_id' => $userId,
                    'value' => $nsIp,
                    'ttl' => 3600,
                    'is_active' => true,
                    'is_system' => true,
                    'source' => 'system',
                    'notes' => 'BelieVoo nameserver A record',
                ]
            );
        }
    }

    /**
     * Get formatted record display
     */
    public function getDisplayValueAttribute(): string
    {
        return match ($this->record_type) {
            'A', 'AAAA' => $this->value,
            'MX' => ($this->priority ?? 10) . ' ' . $this->value,
            'SRV' => ($this->priority ?? 0) . ' ' . ($this->weight ?? 0) . ' ' . ($this->port ?? 0) . ' ' . $this->value,
            'CNAME', 'NS' => $this->value . '.',
            default => $this->value,
        };
    }

    /**
     * Get record type badge
     */
    public function getTypeBadgeAttribute(): string
    {
        $colors = [
            'A' => 'bg-blue-100 text-blue-800',
            'AAAA' => 'bg-purple-100 text-purple-800',
            'CNAME' => 'bg-green-100 text-green-800',
            'MX' => 'bg-orange-100 text-orange-800',
            'TXT' => 'bg-gray-100 text-gray-800',
            'NS' => 'bg-red-100 text-red-800',
            'SRV' => 'bg-yellow-100 text-yellow-800',
            'CAA' => 'bg-indigo-100 text-indigo-800',
        ];

        $color = $colors[$this->record_type] ?? 'bg-gray-100 text-gray-800';
        
        return sprintf(
            '<span class="px-2 py-1 rounded-full text-xs font-medium %s">%s</span>',
            $color,
            $this->record_type
        );
    }

    /**
     * Validate record type
     */
    public static function isValidType(string $type): bool
    {
        return in_array(strtoupper($type), self::VALID_TYPES);
    }

    /**
     * Delete provisioned records for a domain
     */
    public static function deleteProvisionedRecords(int $domainId, ?string $type = null): void
    {
        $query = self::where('user_domain_id', $domainId)
            ->where('is_provisioned', true);
        
        if ($type) {
            $query->where('record_type', $type);
        }
        
        $query->delete();
    }

    /**
     * Boot — auto-sync PowerDNS zone file after any create/update/delete.
     */
    protected static function booted(): void
    {
        $sync = function (self $record) {
            try {
                $domain = $record->userDomain;
                if ($domain && $domain->use_believoo_dns) {
                    \Illuminate\Support\Facades\Artisan::queue('dns:sync-zones', [
                        'domain' => $domain->domain_name,
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('DNS auto-sync failed', [
                    'record_id' => $record->id,
                    'error'     => $e->getMessage(),
                ]);
            }
        };

        static::created($sync);
        static::updated($sync);
        static::deleted($sync);
    }
}
