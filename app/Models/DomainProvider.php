<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'class',
        'is_active',
        'priority',
        'supported_tlds',
        'default_nameservers',
        'api_url',
        'api_key',
        'api_secret',
        'username',
        'password',
        'test_mode',
        'metadata',
        'last_checked_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supported_tlds' => 'array',
        'default_nameservers' => 'array',
        'metadata' => 'encrypted:array',
        'last_checked_at' => 'datetime',
    ];

    protected $attributes = [
        'is_active' => false,
        'priority' => 100,
        'test_mode' => '0',
        'status' => 'pending',
    ];

    /**
     * Get service instance for this provider
     */
    public function getService(): ?object
    {
        if (!class_exists($this->class)) {
            return null;
        }

        return new $this->class($this);
    }

    /**
     * Test provider connection
     */
    public function testConnection(): array
    {
        $service = $this->getService();
        
        if (!$service) {
            return [
                'success' => false,
                'message' => 'Service class not found: ' . $this->class,
            ];
        }

        try {
            $result = $service->testConnection();
            
            // Update status
            $this->update([
                'status' => $result['success'] ? 'active' : 'error',
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Connection failed'),
                'last_checked_at' => now(),
            ]);

            return $result;
        } catch (\Exception $e) {
            $this->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'last_checked_at' => now(),
            ]);

            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get providers by TLD support
     */
    public function scopeSupportsTld($query, string $tld)
    {
        return $query->whereJsonContains('supported_tlds', $tld);
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'inactive' => '<span class="badge badge-secondary">Inactive</span>',
            'error' => '<span class="badge badge-danger">Error</span>',
            'pending' => '<span class="badge badge-warning">Pending</span>',
        ];

        return $badges[$this->status] ?? '<span class="badge badge-info">' . ucfirst($this->status) . '</span>';
    }

    /**
     * User domains relationship
     */
    public function userDomains()
    {
        return $this->hasMany(UserDomain::class);
    }

    /**
     * Domain pricing relationship
     */
    public function domainPricing()
    {
        return $this->hasMany(DomainPricing::class);
    }

    /**
     * Get formatted supported TLDs
     */
    public function getFormattedTldsAttribute(): string
    {
        $tlds = $this->supported_tlds ?? [];
        if (empty($tlds)) {
            return 'None configured';
        }

        return '.' . implode(', .', $tlds);
    }

    /**
     * Set metadata with encryption
     */
    public function setMetadata(array $data): void
    {
        $this->metadata = $data;
        $this->save();
    }

    /**
     * Get specific metadata value
     */
    public function getMetadata(string $key, $default = null)
    {
        $metadata = $this->metadata ?? [];
        return $metadata[$key] ?? $default;
    }
}
