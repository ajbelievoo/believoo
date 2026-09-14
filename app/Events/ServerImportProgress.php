<?php

namespace App\Events;

use App\Models\ServerImport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerImportProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ServerImport $import;

    /**
     * Create a new event instance.
     */
    public function __construct(ServerImport $import)
    {
        $this->import = $import;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->import->user_id),
            new PrivateChannel('import.' . $this->import->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'server.import.progress';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'import_id' => $this->import->id,
            'status' => $this->import->status,
            'progress' => $this->import->progress_percent,
            'message' => $this->import->status_message,
            'files_transferred' => $this->import->files_transferred,
            'file_count' => $this->import->file_count,
            'transferred_bytes' => $this->import->transferred_bytes,
            'current_file' => $this->import->current_file,
            'sync_log' => $this->import->sync_log,
            'transfer_speed' => $this->import->transfer_speed,
            'eta_seconds' => $this->import->eta_seconds,
            'direction' => $this->import->sync_direction,
            'has_aapanel' => $this->import->hasAapanel(),
        ];
    }
}
