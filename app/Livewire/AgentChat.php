<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Message;
use App\Models\SupportAgent;
use App\Models\ChatAssignment;
use App\Events\MessageSent;

class AgentChat extends Component
{
    use WithFileUploads;

    public $agent;
    public $activeSessionId = null;
    public $sessions = [];
    public $messages = [];
    public $replyMessage = '';
    public $replyAttachment;
    public $clientIsTyping = false;

    // Canned / quick replies
    public $cannedReplies = [];
    public $showCannedManager = false;
    public $newCannedTitle = '';
    public $newCannedMessage = '';

    protected $defaultCannedReplies = [
        ['title' => 'Greeting', 'message' => 'Hello! How may I help you today?'],
        ['title' => 'Hindi Greeting', 'message' => 'Namaste! Main aapki kaise madad kar sakta hoon?'],
        ['title' => 'Ask details', 'message' => 'Please share your registered email or phone number so I can check your account.'],
        ['title' => 'Ask details (Hindi)', 'message' => 'Kripya apna registered email ya phone number share karein taki main aapka account check kar sakon.'],
        ['title' => 'Need a moment', 'message' => 'Please wait a moment, I am checking this for you.'],
        ['title' => 'Checking (Hindi)', 'message' => 'Thoda wait karein, main aapke liye check kar raha hoon.'],
        ['title' => 'Closing', 'message' => 'Thank you for contacting Believoo. Is there anything else I can help with?'],
        ['title' => 'Closing (Hindi)', 'message' => 'Shukriya Believoo se sampark karne ke liye. Kya main aur kuch madad kar sakta hoon?'],
    ];

    public function mount()
    {
        $this->agent = SupportAgent::find(session('support_agent_id'));
        if (!$this->agent) {
            $base = request()->getHost() === 'agent.believoo.com' ? '' : '/agent';
            return redirect($base . '/login');
        }
        $this->cannedReplies = $this->agent->canned_replies ?: $this->defaultCannedReplies;
        $this->heartbeat();
        $this->loadSessions();
        if (!$this->activeSessionId && count($this->sessions) > 0) {
            $this->selectSession($this->sessions[0]['session_id']);
        }
    }

    public function heartbeat()
    {
        if ($this->agent) {
            $this->agent->update(['last_seen_at' => now(), 'is_online' => true]);
        }
    }

    public $activeSection = 'chats'; // chats, tickets, stats, profile
    public $tickets = [];
    public $stats = [];
    public $lastUnreadTotal = 0;

    // Profile fields
    public $profileName = '';
    public $profileDisplayName = '';
    public $profileEmail = '';
    public $profilePhone = '';
    public $profilePhoto;
    public $profilePassword = '';
    public $profileSaved = false;

    public function ping()
    {
        $this->heartbeat();
        $this->loadSessions();

        // Notify agent about new client messages (browser notification + sound)
        $unreadTotal = collect($this->sessions)->sum('unread_count');
        if ($unreadTotal > $this->lastUnreadTotal) {
            $latest = collect($this->sessions)->firstWhere('unread_count', '>', 0);
            $this->dispatch('agent-new-message', [
                'from' => $latest['sender_name'] ?? 'Client',
                'preview' => $latest['last_message'] ?? '',
            ]);
            $this->dispatch('play-ping-sound');
        }
        $this->lastUnreadTotal = $unreadTotal;

        if ($this->activeSessionId) {
            $this->loadMessages();
        }
        if ($this->activeSection === 'tickets') {
            $this->loadTickets();
        }
        if ($this->activeSection === 'stats') {
            $this->loadStats();
        }
    }

    public function showSection($section)
    {
        $this->activeSection = $section;
        if ($section === 'tickets') $this->loadTickets();
        if ($section === 'stats') $this->loadStats();
        if ($section === 'profile') $this->loadProfile();
    }

    public function loadProfile()
    {
        $this->profileName = $this->agent->name;
        $this->profileDisplayName = $this->agent->display_name;
        $this->profileEmail = $this->agent->email;
        $this->profilePhone = $this->agent->phone;
        $this->profilePassword = '';
        $this->profileSaved = false;
    }

    public function saveProfile()
    {
        // Agents may only change their photo and password.
        // Name / display name / email are managed by the admin only.
        $this->validate([
            'profilePhoto' => 'nullable|image|max:2048',
            'profilePassword' => 'nullable|min:6',
        ], [], [
            'profilePhoto' => 'photo',
            'profilePassword' => 'password',
        ]);

        $data = [];
        if ($this->profilePhoto) {
            $data['avatar'] = $this->profilePhoto->store('agent-avatars', 'public');
        }
        if ($this->profilePassword) {
            $data['password'] = $this->profilePassword;
        }

        if ($data) {
            $this->agent->update($data);
        }
        $this->agent->refresh();
        $this->profilePhoto = null;
        $this->profilePassword = '';
        $this->profileSaved = true;
    }

    public function toggleStatus()
    {
        $this->agent->update([
            'is_online' => !$this->agent->is_online,
            'last_seen_at' => now(),
        ]);
        $this->agent->refresh();
    }

    public function loadTickets()
    {
        $this->tickets = \App\Models\Ticket::latest()
            ->limit(30)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'ticket_id' => $t->ticket_id,
                'name' => $t->name,
                'email' => $t->email,
                'subject' => $t->subject,
                'priority' => $t->priority,
                'status' => $t->status,
                'created_at' => $t->created_at->diffForHumans(),
            ])->toArray();
    }

    public function loadStats()
    {
        $agentId = $this->agent->id;

        $this->stats = [
            'total_chats' => $this->agent->total_chats,
            'active_chats' => $this->agent->active_chats,
            'today_chats' => ChatAssignment::where('agent_id', $agentId)->whereDate('assigned_at', today())->count(),
            'week_chats' => ChatAssignment::where('agent_id', $agentId)->where('assigned_at', '>=', now()->subDays(7))->count(),
            'messages_sent' => Message::where('agent_id', $agentId)->where('type', 'admin')->count(),
            'ai_conversations' => \App\Models\AiMessage::whereIn('session_id',
                    ChatAssignment::where('agent_id', $agentId)->pluck('session_id'))
                ->where('type', 'user')->count(),
            'avg_rating' => $this->agent->avg_rating,
            'rating_count' => $this->agent->rating_count,
            'late_replies' => $this->agent->late_replies,
            'ztp_flags' => $this->agent->unpermitted_closes,
            'avg_first_reply' => ChatAssignment::where('agent_id', $agentId)
                ->whereNotNull('first_reply_seconds')
                ->avg('first_reply_seconds'),
        ];
    }

    public function loadSessions()
    {
        $sessionIds = ChatAssignment::where('agent_id', $this->agent->id)
            ->pluck('session_id');

        $this->sessions = Message::whereIn('session_id', $sessionIds)
            ->select('session_id', 'sender_name', 'sender_email', 'phone_number', 'created_at', 'is_read', 'type', 'message')
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
                    'last_message' => $lastMessage->message,
                    'last_time' => $lastMessage->created_at->diffForHumans(),
                    'unread_count' => $unreadCount,
                ];
            })
            ->sortByDesc(fn ($s) => $s['unread_count'])
            ->values()
            ->toArray();
    }

    // ══════════════════════════════════════════════════════════════════
    // Batch 2: transfer, tags, notes, customer history, translation
    // ══════════════════════════════════════════════════════════════════
    public function openTransferModal()
    {
        $this->availableAgents = SupportAgent::where('id', '!=', $this->agent->id)
            ->where('is_online', true)
            ->where('is_active', true)
            ->get();
    }

    public function transferChat()
    {
        if (!$this->activeSessionId || !$this->transferTo) return;

        $assignment = ChatAssignment::where('session_id', $this->activeSessionId)
            ->where('status', '!=', 'closed')
            ->first();
        if (!$assignment) return;

        $from = $this->agent->id;
        $to = $this->transferTo;
        $assignment->update(['agent_id' => $to]);

        \App\Models\ChatTransfer::create([
            'session_id' => $this->activeSessionId,
            'from_agent_id' => $from,
            'to_agent_id' => $to,
            'reason' => $this->transferReason,
        ]);

        Message::create([
            'session_id' => $this->activeSessionId,
            'sender_name' => 'System',
            'message' => "Chat transferred to another agent. Reason: " . ($this->transferReason ?: 'No reason'),
            'type' => 'admin',
            'is_read' => true,
        ]);

        $this->transferTo = null;
        $this->transferReason = '';
        $this->loadSessions();
        $this->activeSessionId = null;
        $this->messages = [];
    }

    public function addTag()
    {
        if (!$this->activeSessionId || !$this->newTag) return;
        \App\Models\ChatTag::create(['session_id' => $this->activeSessionId, 'tag' => $this->newTag]);
        $this->newTag = '';
        $this->loadTags();
    }

    public function removeTag($tagId)
    {
        \App\Models\ChatTag::destroy($tagId);
        $this->loadTags();
    }

    private function loadTags()
    {
        $this->chatTags = \App\Models\ChatTag::where('session_id', $this->activeSessionId)->get();
    }

    public function addNote()
    {
        if (!$this->activeSessionId || !$this->agentNote) return;
        \App\Models\ChatNote::create([
            'session_id' => $this->activeSessionId,
            'agent_id' => $this->agent->id,
            'note' => $this->agentNote,
        ]);
        $this->agentNote = '';
        $this->loadNotes();
    }

    private function loadNotes()
    {
        $this->notes = \App\Models\ChatNote::where('session_id', $this->activeSessionId)->with('agent')->latest()->get();
    }

    // ══════════════════════════════════════════════════════════════════
    // Audio / Video call via Jitsi Meet
    // ══════════════════════════════════════════════════════════════════
    public function startAudioCall()
    {
        if (!$this->activeSessionId) return;
        $room = 'believoo-call-' . $this->activeSessionId;
        $url = "https://meet.jit.si/{$room}#config.startAudioOnly=true&config.startWithAudioMuted=false&config.startWithVideoMuted=true&interfaceConfig.TOOLBAR_BUTTONS=['microphone','hangup']";
        $this->dispatch('open-call-window', ['url' => $url, 'name' => 'Audio Call']);
    }

    public function startVideoCall()
    {
        if (!$this->activeSessionId) return;
        $room = 'believoo-call-' . $this->activeSessionId;
        $url = "https://meet.jit.si/{$room}#config.startWithAudioMuted=false&config.startWithVideoMuted=false";
        $this->dispatch('open-call-window', ['url' => $url, 'name' => 'Video Call']);
    }

    public function loadCustomerHistory()
    {
        if (!$this->activeSessionId) return;
        $current = Message::where('session_id', $this->activeSessionId)->where('type', 'user')->first();
        if ($current && $current->sender_email) {
            $sessions = Message::where('sender_email', $current->sender_email)
                ->where('session_id', '!=', $this->activeSessionId)
                ->select('session_id', 'message', 'created_at', 'sender_name')
                ->orderByDesc('created_at')
                ->limit(100)
                ->get()
                ->groupBy('session_id');
            $this->customerHistory = $sessions;
        } else {
            $this->customerHistory = collect([]);
        }
        $this->showHistory = true;
    }

    public function selectSession($sessionId)
    {
        $this->activeSessionId = $sessionId;
        $this->loadMessages();
        Message::where('session_id', $sessionId)->where('type', 'user')->update(['is_read' => true]);
        $this->loadTags();
        $this->loadNotes();
        $this->loadSessions();
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function loadMessages()
    {
        if (!$this->activeSessionId) return;
        $msgs = Message::where('session_id', $this->activeSessionId)->orderBy('created_at', 'asc')->get();
        $this->messages = $msgs->map(fn ($m) => [
            'id' => $m->id,
            'message' => $m->message,
            'attachment' => $m->attachment,
            'type' => $m->type,
            'sender_name' => $m->type === 'admin' ? ($this->agent->display_name ?: $this->agent->name) : $m->sender_name,
            'created_at' => $m->created_at->diffForHumans(),
        ])->toArray();
    }

    public function sendReply()
    {
        if (!$this->activeSessionId) return;
        if (empty(trim($this->replyMessage)) && !$this->replyAttachment) return;

        $this->heartbeat();

        $attachmentPath = $this->replyAttachment
            ? $this->replyAttachment->store('chat-attachments', 'public')
            : null;

        $displayName = $this->agent->display_name ?: $this->agent->name;

        $reply = Message::create([
            'session_id' => $this->activeSessionId,
            'sender_name' => $displayName,
            'sender_email' => $this->agent->email ?? '',
            'message' => $this->replyMessage,
            'attachment' => $attachmentPath,
            'type' => 'admin',
            'agent_id' => $this->agent->id,
            'is_read' => true,
        ]);

        $assignment = ChatAssignment::where('session_id', $this->activeSessionId)
            ->where('agent_id', $this->agent->id)
            ->whereIn('status', ['waiting', 'active'])
            ->latest()->first();
        if ($assignment) {
            $assignment->markFirstReply();
        }

        try {
            broadcast(new MessageSent($reply))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Agent broadcast failed: ' . $e->getMessage());
        }

        $this->replyMessage = '';
        $this->replyAttachment = null;
        $this->loadMessages();
        $this->dispatch('scroll-chat-to-bottom');
    }

    // ══════════════════════════════════════════════════════════════
    // Feature 5: Agent assist — AI drafts a reply suggestion
    // ══════════════════════════════════════════════════════════════
    public $aiSuggestion = '';
    public $aiSuggestionLoading = false;

    // Batch 2: transfer, tags, notes, customer history, translation
    public $availableAgents = [];
    public $transferTo = null;
    public $transferReason = '';
    public $newTag = '';
    public $chatTags = [];
    public $agentNote = '';
    public $notes = [];
    public $showHistory = false;
    public $customerHistory = [];
    public $translateLang = 'hi';
    public $replyLanguage = 'en'; // agent's typed language

    public function askAiSuggestion()
    {
        $this->aiSuggestionLoading = true;
        $this->aiSuggestion = '';

        // Last client message + a bit of context
        $clientMsgs = collect($this->messages)->where('type', 'user')->take(-5);
        if ($clientMsgs->isEmpty()) {
            $this->aiSuggestion = 'Namaste! How may I help you today?';
            $this->aiSuggestionLoading = false;
            return;
        }
        $context = $clientMsgs->map(fn($m) => "Client: {$m['message']}")->implode("\n");
        $last = $clientMsgs->last()['message'] ?? '';

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $prompt = "You are a support agent at Believoo (hosting/VPS/domains company). Draft a short, professional, friendly reply to the client's latest message. Reply in the same language the client used. Do not make up order details you don't have — ask for their email/order ID if needed.\n\nRecent conversation:\n{$context}\n\nDraft a reply to: \"{$last}\"";

        $suggestion = null;
        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';
        if ($apiKey) {
            try {
                $model = $settings['ai_gemini_model'] ?? 'gemini-2.5-flash-lite';
                $resp = \Illuminate\Support\Facades\Http::timeout(20)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    ['contents' => [['parts' => [['text' => $prompt]]]], 'generationConfig' => ['maxOutputTokens' => 300]]
                );
                if ($resp->successful()) {
                    $suggestion = $resp->json('candidates.0.content.parts.0.text');
                }
            } catch (\Exception $e) {
                \Log::error('Agent assist AI failed: ' . $e->getMessage());
            }
        }

        if (!$suggestion) {
            // Fallback — polite generic
            $suggestion = str_contains($last, 'hindi') || preg_match('/[\x{0900}-\x{097F}]/u', $last)
                ? "Ji, main check kar raha hoon. Kripya apna registered email ya order ID share karein."
                : "Let me check that for you. Please share your registered email or order ID.";
        }

        $this->aiSuggestion = trim($suggestion);
        $this->aiSuggestionLoading = false;
    }

    public function useAiSuggestion()
    {
        if ($this->aiSuggestion) {
            $this->replyMessage = $this->aiSuggestion;
            $this->aiSuggestion = '';
        }
    }

    // ══════════════════════════════════════════════════════════════
    // Feature 6: Chat summary on close
    // ══════════════════════════════════════════════════════════════
    private function generateChatSummary($sessionId)
    {
        $msgs = Message::where('session_id', $sessionId)->orderBy('created_at')->get();
        if ($msgs->count() < 3) return null;

        $transcript = $msgs->map(fn($m) => "{$m['sender_name']}: " . mb_substr($m->message, 0, 300))->implode("\n");
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';
        if (!$apiKey) return null;

        try {
            $model = $settings['ai_gemini_model'] ?? 'gemini-2.5-flash-lite';
            $resp = \Illuminate\Support\Facades\Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                ['contents' => [['parts' => [['text' => "Summarise this support chat in 2-3 short lines (what the client wanted, what was resolved):\n\n{$transcript}"]]]], 'generationConfig' => ['maxOutputTokens' => 150]]
            );
            if ($resp->successful()) {
                return $resp->json('candidates.0.content.parts.0.text');
            }
        } catch (\Exception $e) {
            \Log::error('Chat summary AI failed: ' . $e->getMessage());
        }
        return null;
    }

    // Feature 24: archive chat on close
    private function archiveChat($sessionId)
    {
        $clientMsg = Message::where('session_id', $sessionId)->where('type', 'user')->first();
        $all = Message::where('session_id', $sessionId)->orderBy('created_at')->get();
        $assignment = ChatAssignment::where('session_id', $sessionId)->orderByDesc('id')->first();

        \App\Models\ChatArchive::create([
            'session_id' => $sessionId,
            'client_name' => $clientMsg?->sender_name,
            'client_email' => $clientMsg?->sender_email,
            'transcript' => $all->map(fn($m) => "[{$m->created_at->format('H:i')}] {$m->sender_name}: {$m->message}")->implode("\n"),
            'tags' => \App\Models\ChatTag::where('session_id', $sessionId)->pluck('tag')->implode(', '),
            'rating' => $assignment?->client_rating,
            'summary' => $assignment?->summary,
        ]);
    }

    // Feature 7: send transcript to client's email when chat closes
    private function sendChatTranscript($sessionId)
    {
        $clientMsg = Message::where('session_id', $sessionId)->where('type', 'user')->latest()->first();
        if (!$clientMsg || !$clientMsg->sender_email) return;

        $msgs = Message::where('session_id', $sessionId)->orderBy('created_at')->get();
        $transcript = $msgs->map(fn($m) => "[{$m->created_at->format('H:i')}] {$m->sender_name}: {$m->message}")->implode("\n");

        $body = "Hi {$clientMsg->sender_name},\n\nHere's your chat transcript with Believoo Support:\n\n{$transcript}\n\nThanks,\nBelievoo Support Team";

        try {
            \Illuminate\Support\Facades\Mail::raw($body, function ($m) use ($clientMsg) {
                $m->to($clientMsg->sender_email)->subject('Your Believoo Support Chat Transcript');
            });
        } catch (\Exception $e) {
            \Log::error('Transcript email failed: ' . $e->getMessage());
        }
    }

    public function closeChat($consent = false)
    {
        if (!$this->activeSessionId) return;

        $assignment = ChatAssignment::where('session_id', $this->activeSessionId)
            ->where('agent_id', $this->agent->id)
            ->whereIn('status', ['waiting', 'active'])
            ->latest()->first();

        if ($assignment) {
            $summary = $this->generateChatSummary($this->activeSessionId);
            $updateData = [
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => 'agent',
                'closed_without_consent' => !$consent,
            ];
            if ($summary) {
                $updateData['summary'] = $summary;
            }
            $assignment->update($updateData);
            if ($this->agent->active_chats > 0) $this->agent->decrement('active_chats');
            if (!$consent) $this->agent->increment('unpermitted_closes');
        }

        Message::create([
            'session_id' => $this->activeSessionId,
            'sender_name' => $this->agent->display_name ?: $this->agent->name,
            'message' => '--- Chat ended by support agent ---',
            'type' => 'admin',
            'agent_id' => $this->agent->id,
            'is_read' => true,
        ]);

        // Feature 7: send chat transcript to client email
        $this->sendChatTranscript($this->activeSessionId);

        // Feature 24: archive chat for search + GDPR + analytics
        $this->archiveChat($this->activeSessionId);

        $this->loadMessages();
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function useCannedReply($message)
    {
        $this->replyMessage = $message;
        $this->sendReply();
    }

    public function useCannedReplyByIndex($index)
    {
        $index = (int) $index;
        if (isset($this->cannedReplies[$index])) {
            $this->replyMessage = $this->cannedReplies[$index]['message'];
            $this->sendReply();
        }
    }

    public function addCannedReply()
    {
        $this->validate([
            'newCannedTitle' => 'required|string|max:100',
            'newCannedMessage' => 'required|string|max:2000',
        ], [], [
            'newCannedTitle' => 'title',
            'newCannedMessage' => 'message',
        ]);

        $this->cannedReplies[] = [
            'title' => $this->newCannedTitle,
            'message' => $this->newCannedMessage,
        ];

        $this->agent->update(['canned_replies' => $this->cannedReplies]);

        $this->newCannedTitle = '';
        $this->newCannedMessage = '';
        $this->showCannedManager = false;
        $this->dispatch('notify', type: 'success', message: 'Quick reply saved.');
    }

    public function removeCannedReply($index)
    {
        $replies = collect($this->cannedReplies)->values()->toArray();
        if (isset($replies[$index])) {
            unset($replies[$index]);
        }
        $this->cannedReplies = array_values($replies);
        $this->agent->update(['canned_replies' => $this->cannedReplies]);
    }

    public function resetCannedReplies()
    {
        $this->cannedReplies = $this->defaultCannedReplies;
        $this->agent->update(['canned_replies' => $this->cannedReplies]);
    }

    public function render()
    {
        return view('livewire.agent-chat');
    }
}
