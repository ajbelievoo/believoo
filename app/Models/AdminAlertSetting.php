<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAlertSetting extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_enabled',
        'telegram_chat_id',
        'telegram_bot_token',
        'email_enabled',
        'email_address',
        'cpu_threshold',
        'ram_threshold',
        'disk_threshold',
        'vm_down_threshold',
        'alert_vm_down',
        'alert_high_resource',
        'alert_license_expiring',
        'alert_ticket_priority_high',
        'alert_payment_received',
    ];

    protected $casts = [
        'telegram_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'alert_vm_down' => 'boolean',
        'alert_high_resource' => 'boolean',
        'alert_license_expiring' => 'boolean',
        'alert_ticket_priority_high' => 'boolean',
        'alert_payment_received' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldAlertOnResource(int $cpu, int $ram, int $disk): bool
    {
        return $this->alert_high_resource && 
               ($cpu > $this->cpu_threshold || 
                $ram > $this->ram_threshold || 
                $disk > $this->disk_threshold);
    }

    public function getActiveChannels(): array
    {
        $channels = [];
        if ($this->telegram_enabled && $this->telegram_chat_id) {
            $channels[] = 'telegram';
        }
        if ($this->email_enabled && $this->email_address) {
            $channels[] = 'email';
        }
        return $channels;
    }
}
