<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VmMigration extends Model
{
    protected $fillable = [
        'user_id',
        'proxmox_vm_id',
        'source_node',
        'target_node',
        'vmid',
        'status',
        'progress_percent',
        'status_message',
        'proxmox_upid',
        'task_status',
        'is_live_migration',
        'total_bytes_transferred',
        'bytes_remaining',
        'migration_type',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_log',
        'metadata',
    ];
    
    protected $casts = [
        'is_live_migration' => 'boolean',
        'total_bytes_transferred' => 'integer',
        'bytes_remaining' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
        'progress_percent' => 'integer',
        'duration_seconds' => 'integer',
    ];
    
    /**
     * Get the user that owns this migration
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the Proxmox VM being migrated
     */
    public function proxmoxVm()
    {
        return $this->belongsTo(ProxmoxVm::class);
    }
    
    /**
     * Check if migration is in progress
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, ['pending', 'migrating']);
    }
    
    /**
     * Check if migration is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if migration failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
    
    /**
     * Get formatted status badge
     */
    public function statusBadge(): string
    {
        return match($this->status) {
            'completed' => '<span class="badge badge-success">Completed</span>',
            'migrating' => '<span class="badge badge-warning">Migrating...</span>',
            'pending' => '<span class="badge badge-info">Pending</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
    
    /**
     * Get progress bar color
     */
    public function progressColor(): string
    {
        return match($this->status) {
            'completed' => 'bg-green-500',
            'failed' => 'bg-red-500',
            'migrating' => 'bg-blue-500',
            default => 'bg-gray-500',
        };
    }
}
