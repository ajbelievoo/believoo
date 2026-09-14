<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMigration extends Model
{
    protected $fillable = [
        'user_id',
        'proxmox_vm_id',
        'source_ip',
        'source_ssh_port',
        'source_password_encrypted',
        'source_root_user',
        'status',
        'progress_percent',
        'status_message',
        'error_log',
        'discovered_websites',
        'discovered_databases',
        'migration_log',
        'total_bytes_transferred',
        'files_transferred',
        'databases_transferred',
        'started_at',
        'completed_at',
        'estimated_duration_seconds',
    ];
    
    protected $casts = [
        'discovered_websites' => 'array',
        'discovered_databases' => 'array',
        'migration_log' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function proxmoxVm()
    {
        return $this->belongsTo(ProxmoxVm::class);
    }
}
