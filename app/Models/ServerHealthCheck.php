<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerHealthCheck extends Model
{
    protected $fillable = [
        'proxmox_vm_id',
        'check_type',
        'status',
        'metric_value',
        'metric_unit',
        'message',
        'details',
        'alert_sent',
        'alert_sent_at',
        'alert_channels',
    ];

    protected $casts = [
        'metric_value' => 'decimal:2',
        'alert_sent' => 'boolean',
        'alert_sent_at' => 'datetime',
        'details' => 'array',
    ];

    public function vm(): BelongsTo
    {
        return $this->belongsTo(ProxmoxVm::class, 'proxmox_vm_id');
    }

    public function scopeUnalerted($query)
    {
        return $query->where('alert_sent', false)
                     ->whereIn('status', ['warning', 'critical']);
    }

    public function scopeCritical($query)
    {
        return $query->where('status', 'critical');
    }

    public function markAlertSent(string $channels): void
    {
        $this->update([
            'alert_sent' => true,
            'alert_sent_at' => now(),
            'alert_channels' => $channels,
        ]);
    }
}
