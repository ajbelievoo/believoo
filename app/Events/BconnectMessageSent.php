<?php
namespace App\Events;
use App\Models\Bconnect\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BconnectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load('member.user');
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('company.' . $this->message->company_id . '.chat.' . class_basename($this->message->channel_type) . '.' . $this->message->channel_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'message' => $this->message->message,
            'attachments' => $this->message->attachments ?? [],
            'member_id' => $this->message->member_id,
            'member' => ['name' => $this->message->member->user->name],
            'created_at' => $this->message->created_at->format('H:i'),
        ];
    }
}
