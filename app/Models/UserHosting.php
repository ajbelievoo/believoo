<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHosting extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'service_id',
        'hosting_type',
        'provider_name',
        'provider_order_id',
        'provider_service_id',
        'provider_metadata',
        'plan_name',
        'status',
        'price',
        'billing_cycle',
        'start_date',
        'expiry_date',
        'server_ip',
        'control_panel_url',
        'control_panel_username',
        'control_panel_password',
        'primary_domain',
        'ssl_enabled',
        'ssl_expiry',
        'disk_space',
        'bandwidth',
        'email_accounts',
        'ftp_accounts',
        'databases',
        'admin_notes',
        // Server Specifications
        'cpu_cores',
        'ram_size',
        'storage_size',
        'storage_used',
        'bandwidth_used',
        'os_name',
        'datacenter_location',
        'server_hostname',
        'ipv6',
        'gateway',
        'root_password',
        'boot_mode',
        'automated_backup',
        'backup_status',
        'backup_retention_days',
        'uptime_percentage',
        'last_reboot',
        // VM IDs for power actions
        'vps_id',
        'virtualizor_vps_id',
        'has_streaming_addon',
        'recording_enabled',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'start_date' => 'date',
        'expiry_date' => 'date',
        'ssl_enabled' => 'boolean',
        'ssl_expiry' => 'date',
        'cpu_cores' => 'integer',
        'automated_backup' => 'boolean',
        'has_streaming_addon' => 'boolean',
        'recording_enabled' => 'boolean',
        'backup_retention_days' => 'integer',
        'uptime_percentage' => 'decimal:2',
        'last_reboot' => 'datetime',
        'provider_metadata' => 'array',
        'root_password' => \App\Casts\SafeEncryptedString::class,
        'control_panel_password' => \App\Casts\SafeEncryptedString::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the linked Proxmox VM for this hosting
     */
    public function proxmoxVm(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ProxmoxVm::class, 'vmid', 'vps_id');
    }

    /**
     * Get the primary UserDomain record linked to this hosting.
     * Matches by primary_domain name first, falls back to any domain for this user.
     */
    public function domain(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        // If primary_domain is set, match by domain name
        if ($this->primary_domain) {
            return $this->hasOne(UserDomain::class, 'user_id', 'user_id')
                ->where('domain_name', $this->primary_domain);
        }
        // Fallback: first active domain for this user
        return $this->hasOne(UserDomain::class, 'user_id', 'user_id')
            ->where('status', 'active')
            ->orderBy('id');
    }

    /**
     * Get all domains belonging to this hosting's user.
     */
    public function domains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserDomain::class, 'user_id', 'user_id');
    }

    /**
     * Get the Proxmox node name for this hosting's VM
     */
    public function getProxmoxNode(): ?string
    {
        return $this->proxmoxVm?->node ?? config('proxmox.node', 'ns548195');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expiry_date->isFuture();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiry_date->diffInDays(now()) <= 7;
    }

    public function getDaysRemaining(): int
    {
        return (int) now()->diffInDays($this->expiry_date, false);
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            'active' => $this->isExpiringSoon() ? 'bg-yellow-500/20 text-yellow-500' : 'bg-green-500/20 text-green-500',
            'suspended' => 'bg-red-500/20 text-red-500',
            'cancelled' => 'bg-gray-500/20 text-gray-400',
            'expired' => 'bg-red-500/20 text-red-500',
            'pending' => 'bg-blue-500/20 text-blue-500',
            default => 'bg-gray-500/20 text-gray-400',
        };
    }

    // Progress bar helpers
    public function getStoragePercentage(): int
    {
        if (!$this->storage_size || !$this->storage_used) return 0;
        
        $total = $this->parseSize($this->storage_size);
        $used = $this->parseSize($this->storage_used);
        
        if ($total == 0) return 0;
        return min(100, round(($used / $total) * 100));
    }

    public function getStorageRemaining(): string
    {
        if (!$this->storage_size || !$this->storage_used) return 'N/A';
        
        $total = $this->parseSize($this->storage_size);
        $used = $this->parseSize($this->storage_used);
        $remaining = $total - $used;
        
        return round($remaining, 1) . ' GB';
    }

    private function parseSize(string $size): float
    {
        $size = trim($size);
        $value = (float) preg_replace('/[^0-9.]/', '', $size);
        $unit = strtoupper(preg_replace('/[0-9.\s]/', '', $size));
        
        return match($unit) {
            'TB' => $value * 1024,
            'GB' => $value,
            'MB' => $value / 1024,
            default => $value,
        };
    }

    public function getRamSizeNumber(): int
    {
        if (!$this->ram_size) return 0;
        return (int) preg_replace('/[^0-9]/', '', $this->ram_size);
    }

    public function getStorageSizeNumber(): int
    {
        if (!$this->storage_size) return 0;
        return (int) preg_replace('/[^0-9]/', '', $this->storage_size);
    }
}
