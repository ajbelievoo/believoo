<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProxmoxVm extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vmid',
        'name',
        'hostname',
        'node',
        'cpu_cores',
        'memory_mb',
        'disk_gb',
        'storage',
        'iso',
        'iso_storage',
        'bridge',
        'ip_address',
        'mac_address',
        'nameservers',
        'control_panel',
        'bandwidth',
        'plan_name',
        'panel_status',
        'panel_installed_at',
        'panel_login_url',
        'panel_username',
        'panel_password',
        'panel_port',
        'cloud_init_script',
        'cloud_init_status',
        'status',
        'created_at_proxmox',
        'started_at',
        'stopped_at',
        'notes',
        'config_snapshot',
        'metadata',
    ];

    protected $casts = [
        'cpu_cores' => 'integer',
        'memory_mb' => 'integer',
        'disk_gb' => 'integer',
        'nameservers' => 'array',
        'config_snapshot' => 'array',
        'metadata' => 'array',
        'created_at_proxmox' => 'datetime',
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'panel_installed_at' => 'datetime',
        'panel_password' => \App\Casts\SafeEncryptedString::class,
    ];

    /**
     * Get the user that owns this VM
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if VM is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if VM is stopped
     */
    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    /**
     * Get memory in GB
     */
    public function memoryInGb(): float
    {
        return round($this->memory_mb / 1024, 2);
    }

    /**
     * Get formatted status badge
     */
    public function statusBadge(): string
    {
        return match($this->status) {
            'running' => '<span class="badge badge-success">Running</span>',
            'stopped' => '<span class="badge badge-secondary">Stopped</span>',
            'suspended' => '<span class="badge badge-warning">Suspended</span>',
            default => '<span class="badge badge-info">' . ucfirst($this->status) . '</span>',
        };
    }

    /**
     * Scope: Running VMs
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: Stopped VMs
     */
    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * Scope: By user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get total resource usage for a user
     */
    public static function getUserResourceUsage($userId): array
    {
        $vms = self::byUser($userId)->get();
        
        return [
            'total_vms' => $vms->count(),
            'running_vms' => $vms->where('status', 'running')->count(),
            'total_cpu' => $vms->sum('cpu_cores'),
            'total_memory_gb' => round($vms->sum('memory_mb') / 1024, 2),
            'total_disk_gb' => $vms->sum('disk_gb'),
        ];
    }

    /**
     * Check if panel is installed
     */
    public function isPanelInstalled(): bool
    {
        return $this->panel_status === 'installed';
    }

    /**
     * Get panel status badge
     */
    public function panelStatusBadge(): string
    {
        return match($this->panel_status) {
            'installed' => '<span class="badge badge-success">Installed</span>',
            'installing' => '<span class="badge badge-warning">Installing...</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            default => '<span class="badge badge-secondary">Pending</span>',
        };
    }

    /**
     * Get full panel login URL
     */
    public function getPanelLoginUrl(): ?string
    {
        if ($this->panel_login_url) {
            return $this->panel_login_url;
        }

        if ($this->ip_address && $this->panel_port) {
            return "https://{$this->ip_address}:{$this->panel_port}";
        }

        return null;
    }

    /**
     * Get default nameservers
     */
    public function getNameservers(): array
    {
        return $this->nameservers ?? ['ns1.believoo.com', 'ns2.believoo.com'];
    }
}
