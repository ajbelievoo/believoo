<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerImport extends Model
{
    use HasFactory;

    protected $table = 'server_imports';

    protected $fillable = [
        'user_id',
        'user_hosting_id',
        'sync_direction', // pull (import) or push (export)
        'source_ip',
        'source_root_password',
        'source_port',
        'source_hostname',
        'source_os',
        'ssh_private_key',
        'ssh_public_key',
        'ssh_key_setup_complete',
        'status',
        'progress_percent',
        'status_message',
        'total_bytes',
        'transferred_bytes',
        'transfer_speed',
        'eta_seconds',
        'current_file',
        'sync_log',
        'file_count',
        'files_transferred',
        'started_at',
        'completed_at',
        'duration_seconds',
        'error_log',
        'output_log',
        'fstab_updated',
        'network_configured',
        'aapanel_migrated',
        'metadata',
    ];

    protected $casts = [
        'source_port' => 'integer',
        'progress_percent' => 'integer',
        'total_bytes' => 'integer',
        'transferred_bytes' => 'integer',
        'eta_seconds' => 'integer',
        'file_count' => 'integer',
        'files_transferred' => 'integer',
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'ssh_key_setup_complete' => 'boolean',
        'fstab_updated' => 'boolean',
        'network_configured' => 'boolean',
        'aapanel_migrated' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userHosting(): BelongsTo
    {
        return $this->belongsTo(UserHosting::class, 'user_hosting_id');
    }

    /**
     * Check if sync is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'connecting', 'key_setup', 'syncing', 'verifying', 'config_updating']);
    }

    /**
     * Check if this is a pull (import) operation
     */
    public function isPull(): bool
    {
        return $this->sync_direction === 'pull';
    }

    /**
     * Check if this is a push (export) operation
     */
    public function isPush(): bool
    {
        return $this->sync_direction === 'push';
    }

    /**
     * Get sync direction label
     */
    public function getDirectionLabel(): string
    {
        return $this->isPull() ? 'Import to BelieVoo' : 'Export to External';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadgeAttribute(): string
    {
        $colors = [
            'pending' => 'gray',
            'connecting' => 'yellow',
            'syncing' => 'blue',
            'verifying' => 'purple',
            'config_updating' => 'orange',
            'completed' => 'green',
            'failed' => 'red',
        ];

        $labels = [
            'pending' => 'Pending',
            'connecting' => 'Connecting',
            'syncing' => 'Syncing Data',
            'verifying' => 'Verifying',
            'config_updating' => 'Updating Config',
            'completed' => 'Completed',
            'failed' => 'Failed',
        ];

        $color = $colors[$this->status] ?? 'gray';
        $label = $labels[$this->status] ?? ucfirst($this->status);

        return sprintf(
            '<span class="px-2 py-1 rounded-full text-xs font-black bg-%s-500/20 text-%s-500">%s</span>',
            $color,
            $color,
            $label
        );
    }

    /**
     * Format bytes to human readable
     */
    public function getTransferredSizeAttribute(): string
    {
        return $this->formatBytes($this->transferred_bytes);
    }

    public function getTotalSizeAttribute(): string
    {
        return $this->total_bytes ? $this->formatBytes($this->total_bytes) : 'Unknown';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Encrypt password when setting
     */
    public function setSourceRootPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['source_root_password'] = encrypt($value);
        }
    }

    /**
     * Decrypt password when getting
     */
    public function getSourceRootPasswordAttribute($value)
    {
        return $value ? decrypt($value) : null;
    }

    /**
     * Scope for active imports
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'connecting', 'syncing', 'verifying', 'config_updating']);
    }

    /**
     * Scope for user's imports
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Check if aaPanel was detected
     */
    public function hasAapanel(): bool
    {
        return $this->metadata['has_aapanel'] ?? false;
    }

    /**
     * Get aaPanel details
     */
    public function getAapanelDetails(): ?array
    {
        return $this->metadata['aapanel_details'] ?? null;
    }
}
