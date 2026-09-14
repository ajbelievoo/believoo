<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;

class AdminChat extends Component
{
    use WithFileUploads;

    public $activeSessionId = null;
    public $sessions = [];
    public $messages = [];
    public $replyMessage = '';
    public $replyAttachment;
    public $isTyping = false;
    public $clientIsTyping = false;
    public $typingTimeout;

    public function updatedReplyMessage()
    {
        if ($this->activeSessionId) {
            broadcast(new \App\Events\ClientTyping($this->activeSessionId, Auth::user()->name))->toOthers();
        }
    }

    public $activeAssignment = null;

    public function mount($sessionId = null)
    {
        $this->heartbeat();
        $this->activeSessionId = $sessionId;
        $this->loadSessions();
        
        // Auto-select latest session if none is active and sessions exist
        if (!$this->activeSessionId && count($this->sessions) > 0) {
            $this->activeSessionId = $this->sessions[0]['session_id'];
        }

        if ($this->activeSessionId) {
            $this->loadMessages();
            // Mark as read
            Message::where('session_id', $this->activeSessionId)
                ->where('type', 'user')
                ->update(['is_read' => true]);
            $this->loadSessions();
        }
    }

    public function loadSessions()
    {
        $this->sessions = Message::select('session_id', 'sender_name', 'sender_email', 'phone_number', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('session_id')
            ->map(function ($group) {
                $lastMessage = $group->first();
                $unreadCount = $group->where('is_read', false)->where('type', 'user')->count();
                return [
                    'session_id' => $lastMessage->session_id,
                    'sender_name' => $lastMessage->sender_name,
                    'sender_email' => $lastMessage->sender_email,
                    'phone_number' => $lastMessage->phone_number,
                    'last_message' => $lastMessage->message,
                    'last_time' => $lastMessage->created_at->diffForHumans(),
                    'unread_count' => $unreadCount,
                ];
            })
            ->values()
            ->toArray();
    }

    public function selectSession($sessionId)
    {
        $this->activeSessionId = $sessionId;
        $this->loadMessages();
        
        // Mark as read
        Message::where('session_id', $sessionId)
            ->where('type', 'user')
            ->update(['is_read' => true]);
            
        $this->loadSessions();

        // Set default template for manual reply (Template 1)
        $session = collect($this->sessions)->firstWhere('session_id', $sessionId);
        $senderName = $session['sender_name'] ?? 'Client';
        
        $this->replyMessage = "Namaste,

Thank you for reaching out to Believoo. We have reviewed your inquiry.

Our team is already looking into the best possible infrastructure/software solution for you. We will get back to you with a detailed proposal shortly.

Best Regards,
AJ
Founder, Believoo";

        $this->dispatch('scroll-chat-to-bottom');
    }

    public function loadMessages()
    {
        if (!$this->activeSessionId) return;

        $msgs = Message::where('session_id', $this->activeSessionId)
            ->with('admin')
            ->orderBy('created_at', 'asc')
            ->get();
            
        $this->messages = [];
        foreach ($msgs as $msg) {
            $this->messages[] = [
                'id' => $msg->id,
                'message' => $msg->message,
                'attachment' => $msg->attachment,
                'type' => $msg->type,
                'sender_name' => $msg->type === 'admin' ? ($msg->admin->name ?? 'Admin') : $msg->sender_name,
                'sender_photo' => $msg->type === 'admin' ? ($msg->admin->avatar_url ?? null) : null,
                'created_at' => $msg->created_at->diffForHumans(),
            ];
            
            if ($msg->admin_response) {
                $this->messages[] = [
                    'id' => 'resp-' . $msg->id,
                    'message' => $msg->admin_response,
                    'type' => 'admin',
                    'sender_name' => 'Support Agent',
                    'created_at' => $msg->updated_at->diffForHumans(),
                ];
            }
        }
    }

    private function myAgent()
    {
        return \App\Models\SupportAgent::where('user_id', Auth::id())->first();
    }

    private function heartbeat()
    {
        $agent = $this->myAgent();
        if ($agent) {
            $agent->update(['last_seen_at' => now(), 'is_online' => true]);
        }
    }

    private function currentAssignment()
    {
        return \App\Models\ChatAssignment::where('session_id', $this->activeSessionId)
            ->whereIn('status', ['waiting', 'active'])
            ->orderBy('id', 'desc')
            ->first();
    }

    public function sendReply()
    {
        if (!$this->activeSessionId) return;
        if (empty(trim($this->replyMessage)) && !$this->replyAttachment) return;

        $this->heartbeat();
        $agent = $this->myAgent();

        $attachmentPath = null;
        if ($this->replyAttachment) {
            $attachmentPath = $this->replyAttachment->store('chat-attachments', 'public');
        }

        $displayName = $agent ? ($agent->display_name ?: $agent->name) : Auth::user()->name;

        $reply = Message::create([
            'session_id' => $this->activeSessionId,
            'sender_name' => $displayName,
            'sender_email' => Auth::user()->email,
            'message' => $this->replyMessage,
            'attachment' => $attachmentPath,
            'type' => 'admin',
            'admin_id' => Auth::id(),
            'agent_id' => $agent->id ?? null,
            'is_read' => true,
        ]);

        // Track first reply SLA for the assignment
        $assignment = $this->currentAssignment();
        if ($assignment) {
            $assignment->markFirstReply();
        }

        try {
            broadcast(new MessageSent($reply))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Admin broadcast failed: ' . $e->getMessage());
        }

        $this->messages[] = [
            'id' => $reply->id,
            'message' => $reply->message,
            'attachment' => $reply->attachment,
            'type' => 'admin',
            'sender_name' => $displayName,
            'sender_photo' => Auth::user()->avatar_url ?? null,
            'created_at' => 'Just now',
        ];

        $this->replyMessage = '';
        $this->replyAttachment = null;
        $this->dispatch('scroll-chat-to-bottom');
    }

    /**
     * End the live chat. $consent = client agreed to close (ZTP policy).
     */
    public function closeChat($consent = false)
    {
        if (!$this->activeSessionId) return;

        $assignment = $this->currentAssignment();
        if ($assignment) {
            $assignment->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => 'agent',
                'closed_without_consent' => !$consent,
            ]);

            $agent = $assignment->agent;
            if ($agent) {
                if ($agent->active_chats > 0) {
                    $agent->decrement('active_chats');
                }
                if (!$consent) {
                    $agent->increment('unpermitted_closes'); // ZTP flag
                }
            }
        }

        Message::create([
            'session_id' => $this->activeSessionId,
            'sender_name' => Auth::user()->name,
            'sender_email' => Auth::user()->email,
            'message' => '--- Chat ended by support agent ---',
            'type' => 'admin',
            'admin_id' => Auth::id(),
            'is_read' => true,
        ]);

        $this->loadMessages();
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function ping()
    {
        $this->heartbeat();
    }

    public function getListeners()
    {
        return [
            "echo:admin-notifications,MessageSent" => 'onNewMessage',
            "echo:admin-notifications,ClientTyping" => 'onClientTyping',
        ];
    }

    public function onClientTyping($event)
    {
        if ($this->activeSessionId && isset($event['sessionId']) && $event['sessionId'] === $this->activeSessionId) {
            if (isset($event['senderName']) && $event['senderName'] !== Auth::user()->name) {
                $this->clientIsTyping = true;
                $this->dispatch('reset-client-typing');
            }
        }
    }

    public function onNewMessage($event)
    {
        $this->loadSessions();
        
        if ($this->activeSessionId && $event['session_id'] === $this->activeSessionId) {
            $this->clientIsTyping = false;
            
            // Check if message already exists
            $exists = collect($this->messages)->contains('id', $event['id']);
            if (!$exists) {
                $this->messages[] = [
                    'id' => $event['id'],
                    'message' => $event['message'],
                    'attachment' => $event['attachment'] ?? null,
                    'type' => $event['type'],
                    'sender_name' => $event['sender_name'],
                    'sender_photo' => $event['sender_photo'] ?? null,
                    'created_at' => 'Just now',
                ];
                
                // Mark as read if session is active
                Message::where('id', $event['id'])->update(['is_read' => true]);
                $this->loadSessions();
                
                $this->dispatch('scroll-chat-to-bottom');
                $this->dispatch('play-ping-sound');
            }
        } else {
            $this->dispatch('play-ping-sound');
            
            // Only show toast if not already looking at the chat
            if (!$this->activeSessionId || $event['session_id'] !== $this->activeSessionId) {
                \Filament\Notifications\Notification::make()
                    ->title('New Message from ' . ($event['sender_name'] ?? 'User'))
                    ->body($event['message'] ?? '')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->send()
                    ->sendToDatabase(Auth::user());
            }
        }
    }

    public function render()
    {
        return view('livewire.admin-chat');
    }
}
