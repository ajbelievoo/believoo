<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseOrder extends Model
{
    protected $fillable = [
        'user_id',
        'proxmox_vm_id',
        'license_type',
        'order_number',
        'amount',
        'currency',
        'billing_cycle',
        'payment_status',
        'payment_method',
        'payment_id',
        'payment_response',
        'activation_status',
        'vps_license_id',
        'paid_at',
        'activated_at',
        'expires_at',
        'server_ip',
        'server_hostname',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'payment_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vm(): BelongsTo
    {
        return $this->belongsTo(ProxmoxVm::class, 'proxmox_vm_id');
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(VpsLicense::class, 'vps_license_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            $order->order_number = 'LIC-' . strtoupper(\Illuminate\Support\Str::random(8));
        });
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isActive(): bool
    {
        return $this->activation_status === 'active' && $this->expires_at && $this->expires_at->isFuture();
    }
}
