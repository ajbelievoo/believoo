<?php

namespace App\Events;

use App\Models\ServerImport;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerImportFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ServerImport $import;
    public string $error;

    public function __construct(ServerImport $import, string $error)
    {
        $this->import = $import;
        $this->error = $error;
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
        return 'server.import.failed';
    }

    public function broadcastWith(): array
    {
        return [
            'import_id' => $this->import->id,
            'status' => 'failed',
            'error' => $this->error,
            'message' => 'Server import failed: ' . $this->error,
        ];
    }
}
