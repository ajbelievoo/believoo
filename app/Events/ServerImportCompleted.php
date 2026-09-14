<?php

namespace App\Events;

use App\Models\ServerImport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerImportCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ServerImport $import;

    public function __construct(ServerImport $import)
    {
        $this->import = $import;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->import->user_id),
            new PrivateChannel('import.' . $this->import->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'server.import.completed';
    }

    public function broadcastWith(): array
    {
        return [
            'import_id' => $this->import->id,
            'source_ip' => $this->import->source_ip,
            'status' => 'completed',
            'progress' => 100,
            'duration_seconds' => $this->import->duration_seconds,
            'files_transferred' => $this->import->files_transferred,
            'transferred_size' => $this->import->transferred_size,
            'has_aapanel' => $this->import->hasAapanel(),
            'message' => 'Server import completed successfully!',
        ];
    }
}
