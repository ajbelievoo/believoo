<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BconnectTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $companyId,
        public string $channelType,
        public int $channelId,
        public int $memberId,
        public string $memberName
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('company.' . $this->companyId . '.chat.' . ucfirst($this->channelType) . '.' . $this->channelId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'member_id' => $this->memberId,
            'member_name' => $this->memberName,
        ];
    }
}
