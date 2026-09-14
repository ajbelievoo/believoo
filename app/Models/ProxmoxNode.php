<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProxmoxNode extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'hostname',
        'port',
        'country_code',
        'city',
        'region',
        'latitude',
        'longitude',
        'status',
        'is_default',
        'max_vms',
        'current_vms',
        'total_cpu_cores',
        'total_memory_bytes',
        'total_disk_bytes',
        'total_bandwidth_bytes',
        'used_cpu_percent',
        'used_memory_percent',
        'used_disk_percent',
        'last_synced_at',
        'api_token_encrypted',
        'root_password_encrypted',
        'network_gateway',
        'network_subnet',
        'ip_pools',
        'provider_name',
        'provider_server_id',
        'provider_metadata',
        'provider_api_key_encrypted',
        'provider_api_secret_encrypted',
        'provider_api_endpoint',
        'provider_account_id',
        'jit_ip_enabled',
        'jit_ip_type',
        'jit_ip_cost',
        'flag_emoji',
        'latency_hint',
    ];
    
    protected $casts = [
        'is_default' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'ip_pools' => 'array',
        'provider_metadata' => 'array',
        'jit_ip_enabled' => 'boolean',
        'last_synced_at' => 'datetime',
    ];
    
    /**
     * Get only active nodes
     */
    public static function active()
    {
        return self::where('status', 'active');
    }
    
    /**
     * Get nodes by country
     */
    public static function byCountry(string $countryCode)
    {
        return self::where('country_code', strtoupper($countryCode));
    }
    
    /**
     * Get default node
     */
    public static function defaultNode()
    {
        return self::where('is_default', true)
            ->where('status', 'active')
            ->first();
    }
    
    /**
     * Check if node has available capacity
     */
    public function hasCapacity(): bool
    {
        return $this->current_vms < $this->max_vms;
    }
    
    /**
     * Get available VM slots
     */
    public function availableSlots(): int
    {
        return max(0, $this->max_vms - $this->current_vms);
    }
    
    /**
     * Get formatted location string
     */
    public function getLocationAttribute(): string
    {
        return "{$this->city}, {$this->country_code}";
    }
    
    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $badges = [
            'active' => '<span class="badge badge-success">Active</span>',
            'maintenance' => '<span class="badge badge-warning">Maintenance</span>',
            'offline' => '<span class="badge badge-danger">Offline</span>',
            'coming_soon' => '<span class="badge badge-secondary">Coming Soon</span>',
        ];
        
        return $badges[$this->status] ?? '<span class="badge badge-secondary">Unknown</span>';
    }
    
    /**
     * Decrypt API token
     */
    public function getDecryptedApiToken(): ?string
    {
        if (!$this->api_token_encrypted) {
            return null;
        }
        
        try {
            // Try Laravel's Crypt first
            return \Illuminate\Support\Facades\Crypt::decryptString($this->api_token_encrypted);
        } catch (\Exception $e) {
            // Fallback to EncryptionService for legacy data
            try {
                $encryption = app(\App\Services\EncryptionService::class);
                return $encryption->decrypt($this->api_token_encrypted);
            } catch (\Exception $e2) {
                Log::error('Failed to decrypt API token', ['node_id' => $this->id]);
                return null;
            }
        }
    }
    
    /**
     * Decrypt root password
     */
    public function getDecryptedRootPassword(): ?string
    {
        if (!$this->root_password_encrypted) {
            return null;
        }
        
        try {
            $encryption = app(\App\Services\EncryptionService::class);
            return $encryption->decrypt($this->root_password_encrypted);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt root password', ['node_id' => $this->id]);
            return null;
        }
    }
    
    /**
     * Decrypt provider API key
     */
    public function getDecryptedProviderApiKey(): ?string
    {
        if (!$this->provider_api_key_encrypted) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($this->provider_api_key_encrypted);
        } catch (\Exception $e) {
            try {
                $encryption = app(\App\Services\EncryptionService::class);
                return $encryption->decrypt($this->provider_api_key_encrypted);
            } catch (\Exception $e2) {
                Log::error('Failed to decrypt provider API key', ['node_id' => $this->id]);
                return null;
            }
        }
    }

    /**
     * Decrypt provider API secret
     */
    public function getDecryptedProviderApiSecret(): ?string
    {
        if (!$this->provider_api_secret_encrypted) {
            return null;
        }
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($this->provider_api_secret_encrypted);
        } catch (\Exception $e) {
            try {
                $encryption = app(\App\Services\EncryptionService::class);
                return $encryption->decrypt($this->provider_api_secret_encrypted);
            } catch (\Exception $e2) {
                Log::error('Failed to decrypt provider API secret', ['node_id' => $this->id]);
                return null;
            }
        }
    }

    /**
     * Check if this node is configured for JIT IP acquisition
     */
    public function supportsJitIp(): bool
    {
        return $this->jit_ip_enabled
            && !empty($this->provider_name)
            && !empty($this->provider_server_id)
            && !empty($this->getDecryptedProviderApiKey());
    }

    /**
     * Relationship: VMs on this node
     */
    public function vms()
    {
        return $this->hasMany(ProxmoxVm::class, 'node', 'name');
    }
    
    /**
     * Scope: Available for new VMs
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
            ->whereRaw('current_vms < max_vms');
    }
}
