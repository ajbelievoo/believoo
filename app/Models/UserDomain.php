<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDomain extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'domain_provider_id',
        'domain_name',
        'tld',
        'sld',
        'registration_date',
        'expiry_date',
        'registration_period',
        'provider_domain_id',
        'provider_order_id',
        'status',
        'auto_renew',
        'nameservers',
        'use_provider_dns',
        'use_believoo_dns',
        'external_registrar',
        'registrant_contact',
        'admin_contact',
        'technical_contact',
        'billing_contact',
        'whois_privacy',
        'purchase_price',
        'selling_price',
        'profit_margin',
        'currency',
        'auth_code',
        'metadata',
        'notes',
        'last_synced_at',
        'renewal_reminder_sent_at',
    ];

    protected $casts = [
        'nameservers' => 'array',
        'registrant_contact' => 'array',
        'admin_contact' => 'array',
        'technical_contact' => 'array',
        'billing_contact' => 'array',
        'metadata' => 'array',
        'registration_date' => 'date',
        'expiry_date' => 'date',
        'auto_renew' => 'boolean',
        'use_provider_dns' => 'boolean',
        'use_believoo_dns' => 'boolean',
        'whois_privacy' => 'boolean',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'renewal_reminder_sent_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
        'auto_renew' => true,
        'use_believoo_dns' => true,
        'use_provider_dns' => false,
        'whois_privacy' => false,
        'currency' => 'USD',
    ];

    /**
     * User relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Domain provider relationship
     */
    public function domainProvider()
    {
        return $this->belongsTo(DomainProvider::class);
    }

    /**
     * Scope: Active domains
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Expired domains
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope: Expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>=', now())
            ->where('status', 'active');
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: By TLD
     */
    public function scopeByTld($query, string $tld)
    {
        return $query->where('tld', $tld);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'expired' => '<span class="badge badge-danger">Expired</span>',
            'suspended' => '<span class="badge badge-warning">Suspended</span>',
            'pending_transfer' => '<span class="badge badge-info">Transfer Pending</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>';
    }

    /**
     * Check if domain is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date < now();
    }

    /**
     * Check if domain is expiring soon
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date <= now()->addDays($days) && $this->expiry_date >= now();
    }

    /**
     * Get days until expiry
     */
    public function daysUntilExpiry(): int
    {
        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get full domain name
     */
    public function getFullDomainAttribute(): string
    {
        return $this->sld . '.' . $this->tld;
    }

    /**
     * Get registrar/provider name
     */
    public function getRegistrarNameAttribute(): string
    {
        return $this->domainProvider?->name ?? 'Unknown';
    }

    /**
     * Get DNS records from provider
     */
    public function getDnsRecords(): array
    {
        $service = $this->domainProvider?->getService();
        
        if (!$service) {
            return [];
        }

        try {
            return $service->getDnsRecords($this->domain_name);
        } catch (\Exception $e) {
            Log::error('Failed to get DNS records', [
                'domain' => $this->domain_name,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Sync domain info from provider
     */
    public function syncFromProvider(): bool
    {
        $service = $this->domainProvider?->getService();
        
        if (!$service) {
            return false;
        }

        try {
            $info = $service->getDomainInfo($this->domain_name);
            
            if ($info) {
                $this->update([
                    'expiry_date' => $info['expiry_date'] ?? $this->expiry_date,
                    'nameservers' => $info['nameservers'] ?? $this->nameservers,
                    'status' => $this->determineStatus($info),
                    'last_synced_at' => now(),
                    'metadata' => array_merge($this->metadata ?? [], ['last_provider_info' => $info]),
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to sync domain from provider', [
                'domain' => $this->domain_name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Determine status from provider info
     */
    protected function determineStatus(array $info): string
    {
        if (isset($info['is_expired']) && $info['is_expired']) {
            return 'expired';
        }

        if (isset($info['status'])) {
            $status = strtolower($info['status']);
            if (str_contains($status, 'active')) {
                return 'active';
            }
            if (str_contains($status, 'expired')) {
                return 'expired';
            }
        }

        return $this->status ?? 'active';
    }

    /**
     * Get contact info formatted
     */
    public function getContactInfo(string $type = 'registrant'): ?array
    {
        $field = $type . '_contact';
        return $this->$field;
    }

    /**
     * Check if using Believoo DNS
     */
    public function isUsingBelievooDns(): bool
    {
        return $this->use_believoo_dns;
    }

    /**
     * Get Believoo nameservers
     */
    public static function getBelievooNameservers(): array
    {
        return ['ns1.believoo.com', 'ns2.believoo.com'];
    }
}
