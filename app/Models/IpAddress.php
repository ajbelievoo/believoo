<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpAddress extends Model
{
    protected $fillable = [
        'ip_address',
        'gateway',
        'netmask',
        'node',
        'status',
        'source',
        'provider_name',
        'provider_ref',
        'cost',
        'proxmox_vm_id',
        'user_hosting_id',
        'vmid',
        'rdns',
        'notes',
    ];

    /**
     * Create an IpAddress record from a JIT acquisition result.
     */
    public static function createFromJitResult(\App\Services\JitIpResult $result, string $nodeName, string $providerName): self
    {
        return self::create([
            'ip_address'    => $result->ip,
            'gateway'       => $result->gateway,
            'netmask'       => $result->netmask,
            'node'          => $nodeName,
            'status'        => 'assigned',
            'source'        => 'jit',
            'provider_name' => $providerName,
            'provider_ref'  => $result->providerRef,
            'cost'          => $result->cost,
        ]);
    }

    /**
     * Get next available IP from pool for a given node
     * Returns null if pool is empty (fallback to DHCP)
     */
    public static function getNextAvailable(?string $node = null): ?self
    {
        $query = self::where('status', 'available')->where('source', 'pool');

        if ($node) {
            // Prefer IPs for this specific node, fallback to any available pool IP
            $ip = (clone $query)->where('node', $node)->lockForUpdate()->first();
            if ($ip) return $ip;
        }

        return $query->lockForUpdate()->first();
    }

    /**
     * Assign IP to a VM
     */
    public function assignToVm(int $vmid, ?int $proxmoxVmId = null, ?int $userHostingId = null): void
    {
        $this->update([
            'status'          => 'assigned',
            'vmid'            => $vmid,
            'proxmox_vm_id'   => $proxmoxVmId,
            'user_hosting_id' => $userHostingId,
        ]);
    }

    /**
     * Release IP back to pool
     */
    public function release(): void
    {
        $this->update([
            'status'          => 'available',
            'vmid'            => null,
            'proxmox_vm_id'   => null,
            'user_hosting_id' => null,
        ]);
    }

    /**
     * Check if pool has available IPs
     */
    public static function hasAvailable(?string $node = null): bool
    {
        $query = self::where('status', 'available')->where('source', 'pool');
        if ($node) {
            return $query->where('node', $node)->exists()
                || (clone $query)->exists();
        }
        return $query->exists();
    }

    /**
     * Get pool stats
     */
    public static function getPoolStats(): array
    {
        return [
            'total'     => self::where('source', 'pool')->count(),
            'available' => self::where('status', 'available')->where('source', 'pool')->count(),
            'assigned'  => self::where('status', 'assigned')->where('source', 'pool')->count(),
            'reserved'  => self::where('status', 'reserved')->where('source', 'pool')->count(),
            'jit'       => self::where('source', 'jit')->count(),
        ];
    }

    public function proxmoxVm()
    {
        return $this->belongsTo(ProxmoxVm::class);
    }

    public function userHosting()
    {
        return $this->belongsTo(UserHosting::class);
    }
}
