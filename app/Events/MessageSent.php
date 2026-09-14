<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.' . $this->message->session_id),
            new Channel('admin-notifications'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'session_id' => $this->message->session_id,
            'message' => $this->message->message,
            'type' => $this->message->type,
            'sender_name' => $this->message->agent_id
                ? ($this->message->agent->display_name ?: $this->message->agent->name)
                : $this->message->sender_name,
            'sender_photo' => $this->message->type === 'admin'
                ? ($this->message->agent_id
                    ? ($this->message->agent->avatar ? \Illuminate\Support\Facades\Storage::url($this->message->agent->avatar) : null)
                    : ($this->message->admin->avatar_url ?? null))
                : null,
            'created_at' => $this->message->created_at->diffForHumans(),
        ];
    }
}
