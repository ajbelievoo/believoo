<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BconnectWhiteboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $companyId,
        public int $projectId,
        public array $stroke,
        public int $memberId,
        public string $memberName
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('company.' . $this->companyId . '.whiteboard.' . $this->projectId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'stroke' => $this->stroke,
            'member_id' => $this->memberId,
            'member_name' => $this->memberName,
        ];
    }
}
