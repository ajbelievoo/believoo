<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpsSnapshot extends Model
{
    protected $fillable = [
        'user_id',
        'proxmox_vm_id',
        'name',
        'description',
        'proxmox_snapshot_id',
        'type',
        'size_bytes',
        'storage_location',
        'status',
        'snapshotted_at',
        'expires_at',
        'restored_at',
        'restore_log',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'snapshotted_at' => 'datetime',
        'expires_at' => 'datetime',
        'restored_at' => 'datetime',
        'restore_log' => 'array',
    ];

    /**
     * Get the VM this snapshot belongs to
     */
    public function vm(): BelongsTo
    {
        return $this->belongsTo(ProxmoxVm::class, 'proxmox_vm_id');
    }

    /**
     * Get the user who created this snapshot
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
