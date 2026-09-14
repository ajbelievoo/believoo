<?php

namespace App\Events;

use App\Models\VmMigration;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VmMigrationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public VmMigration $migration;

    public function __construct(VmMigration $migration)
    {
        $this->migration = $migration;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->migration->user_id),
            new PrivateChannel('vm.' . $this->migration->proxmox_vm_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'vm.migration.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'migration_id' => $this->migration->id,
            'vm_id' => $this->migration->proxmox_vm_id,
            'vmid' => $this->migration->vmid,
            'source_node' => $this->migration->source_node,
            'target_node' => $this->migration->target_node,
            'status' => 'completed',
            'progress' => 100,
            'message' => 'Migration completed successfully!',
            'duration_seconds' => $this->migration->duration_seconds,
            'completed_at' => $this->migration->completed_at?->toIso8601String(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
