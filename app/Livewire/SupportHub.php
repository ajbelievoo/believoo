<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\CallRequest;
use App\Models\User;
use App\Events\MessageSent;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;
use App\Models\AiMessage;
use App\Models\AiFeedback;

class SupportHub extends Component
{
    use WithFileUploads;

    public $isOpen = false;
    public $activeTab = 'chat'; // 'chat', 'callback', or 'tickets'
    public $sessionId;
    public $isRegistered = false;

    // Ticket fields
    public $ticketSubject;
    public $ticketMessage;
    public $ticketPriority = 'medium';
    public $ticketAttachment;
    public $userTickets = [];
    public $selectedTicket = null;

    // Message fields
    public $name;
    public $email;
    public $message;
    public $chatAttachment;
    public $chatMessages = [];
    public $agentChat = null; // live-agent assignment info for rating UI
    public $adminIsTyping = false;
    public $adminName = 'Admin';

    public function updatedMessage()
    {
        if ($this->isRegistered) {
            broadcast(new \App\Events\ClientTyping($this->sessionId, $this->name))->toOthers();
        }
    }

    // Callback fields
    public $phone;
    // Only safe, display-safe keys go to the browser. NEVER expose API keys,
    // secrets, SMTP creds, etc. via public Livewire props (they leak into
    // wire:snapshot in the HTML source).
    public $settings;

    // AI Assistant fields
    public $aiMessages = [];
    public $aiQuestion = '';
    public $aiIsLoading = false;

    public function mount()
    {
        $this->sessionId = session()->get('chat_session_id');
        // Only expose display-safe settings to the browser — never API keys or secrets
        $all = \App\Models\Setting::pluck('value', 'key')->toArray();
        $safeKeys = ['site_name', 'site_logo', 'footer_text', 'favicon', 'contact_phone', 'contact_email', 'support_email', 'whatsapp', 'whatsapp_number', 'ai_enabled', 'ai_model'];
        $this->settings = array_intersect_key($all, array_flip($safeKeys));
        
        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->isRegistered = true;
        }

        if (!$this->sessionId) {
            $this->sessionId = Str::random(40);
            session()->put('chat_session_id', $this->sessionId);
        }

        $this->loadMessages();
        $this->loadTickets();

        $this->loadAiMessages();
    }

    public function loadAiMessages()
    {
        $this->aiMessages = AiMessage::where('session_id', $this->sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'type' => $m->type,
                    'message' => $m->message,
                    'created_at' => $m->created_at->diffForHumans(),
                    'lang' => $m->lang,
                    'attachment' => $m->attachment,
                ];
            })->toArray();

        // Show a welcome message for new visitors
        if (empty($this->aiMessages)) {
            $welcome = 'Hello! I am Believoo AI. Ask me anything about domains, hosting, websites, apps, SEO, or support tickets. I can also help in Hindi.';
            $this->aiMessages[] = [
                'type' => 'ai',
                'message' => $welcome,
                'created_at' => 'Just now',
                'lang' => 'en',
                'id' => null,
            ];
        }

        if (!$this->isRegistered && count($this->chatMessages) > 0) {
            $lastMsg = end($this->chatMessages);
            $this->name = $lastMsg['sender_name'] ?? '';
            $this->email = $lastMsg['sender_email'] ?? '';
            $this->isRegistered = true;
        }
    }

    public function loadMessages()
    {
        $messages = Message::where('session_id', $this->sessionId)
            ->with(['admin', 'agent'])
            ->orderBy('created_at', 'asc')
            ->get();
            
        $this->chatMessages = [];
        
        foreach ($messages as $msg) {
            $this->chatMessages[] = [
                'id' => $msg->id,
                'message' => $msg->message,
                'attachment' => $msg->attachment,
                'type' => $msg->type,
                'sender_name' => $msg->type === 'admin'
                    ? ($msg->agent ? ($msg->agent->display_name ?: $msg->agent->name) : ($msg->admin->name ?? 'Admin'))
                    : $msg->sender_name,
                'sender_photo' => $msg->type === 'admin'
                    ? ($msg->agent && $msg->agent->avatar
                        ? \Illuminate\Support\Facades\Storage::url($msg->agent->avatar)
                        : ($msg->admin->avatar_url ?? null))
                    : null,
                'is_read' => (bool) $msg->is_read,
                'created_at' => $msg->created_at->diffForHumans(),
            ];
            
            if ($msg->admin_response) {
                $this->chatMessages[] = [
                    'id' => 'resp-' . $msg->id,
                    'message' => $msg->admin_response,
                    'type' => 'admin',
                    'sender_name' => 'Support Agent',
                    'sender_photo' => null,
                    'created_at' => $msg->updated_at->diffForHumans(),
                ];
            }
        }

        // Live-agent assignment status for this session (for rating UI)
        $assignment = \App\Models\ChatAssignment::where('session_id', $this->sessionId)
            ->with('agent')
            ->orderBy('id', 'desc')
            ->first();
        $this->agentChat = $assignment ? [
            'id' => $assignment->id,
            'agent_name' => $assignment->agent ? ($assignment->agent->display_name ?: $assignment->agent->name) : 'Agent',
            'status' => $assignment->status,
            'rated' => $assignment->client_rating !== null,
        ] : null;
    }

    public function getListeners()
    {
        $listeners = [
            "echo:chat.{$this->sessionId},MessageSent" => 'onMessageReceived',
            "echo:chat.{$this->sessionId},ClientTyping" => 'onAdminTyping',
        ];

        // Also listen for any ticket updates if we have the user's email
        if ($this->email) {
            // Since we can't easily listen to multiple dynamic ticket channels in Livewire 
            // without complex setups, we'll refresh tickets when any relevant event occurs
            // or if we have a selected ticket, we listen to its specific channel
            if ($this->selectedTicket) {
                $ticketId = is_array($this->selectedTicket) ? $this->selectedTicket['id'] : $this->selectedTicket->id;
                $listeners["echo:ticket.{$ticketId},TicketMessageSent"] = 'onTicketMessageReceived';
            }
        }

        return $listeners;
    }

    public function onTicketMessageReceived($event)
    {
        $this->loadTickets();
        $this->dispatch('play-notification-sound');
    }

    public function onAdminTyping($event)
    {
        if (isset($event['senderName']) && $event['senderName'] !== $this->name) {
            $this->adminIsTyping = true;
            $this->adminName = $event['senderName'];
            $this->dispatch('reset-admin-typing');
        }
    }

    public function onMessageReceived($event)
    {
        $this->adminIsTyping = false;
        
        // Check if message already exists (to avoid duplicates if broadcasted back)
        $exists = collect($this->chatMessages)->contains('id', $event['id']);
        
        if (!$exists) {
            $this->chatMessages[] = [
                'id' => $event['id'],
                'message' => $event['message'],
                'type' => $event['type'],
                'sender_name' => $event['sender_name'],
                'sender_photo' => $event['sender_photo'] ?? null,
                'created_at' => 'Just now',
            ];
            $this->dispatch('scroll-chat-to-bottom');
            $this->dispatch('play-notification-sound');
        }
    }

    public function updatedIsOpen($value)
    {
        if ($value) {
            $this->loadMessages();
            $this->dispatch('scroll-chat-to-bottom');
        }
    }

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->loadMessages();
            $this->dispatch('scroll-chat-to-bottom');
            $this->dispatch('request-notification-permission');
        }
    }

    public function sendMessage()
    {
        $hasAgentChat = $this->agentChat !== null;

        if (!$this->isRegistered) {
            if ($hasAgentChat) {
                // Agent already connected — allow guest messaging
                $this->validate([
                    'message' => 'required_without:chatAttachment|min:1',
                    'chatAttachment' => 'nullable|file|max:10240',
                ]);
                if (!$this->name) $this->name = 'Guest';
                $this->isRegistered = true;
            } else {
                $this->validate([
                    'name' => 'required|min:3',
                    'email' => 'required|email',
                    'phone' => 'nullable|min:10',
                    'message' => 'required_without:chatAttachment|min:2',
                    'chatAttachment' => 'nullable|file|max:10240',
                ]);
                $this->isRegistered = true;
            }
        } else {
            $this->validate([
                'message' => 'required_without:chatAttachment|min:1',
                'chatAttachment' => 'nullable|file|max:10240',
            ]);
        }

        $attachmentPath = null;
        if ($this->chatAttachment) {
            $attachmentPath = $this->chatAttachment->store('chat-attachments', 'public');
        }

        $msg = Message::create([
            'session_id' => $this->sessionId,
            'sender_name' => $this->name,
            'sender_email' => $this->email,
            'phone_number' => $this->phone,
            'message' => $this->message,
            'attachment' => $attachmentPath,
            'type' => 'user',
        ]);

        try {
            broadcast(new MessageSent($msg))->toOthers();
        } catch (\Exception $e) {
            // Log error but continue so the chat doesn't break for the user
            \Log::error('Broadcasting failed in SupportHub: ' . $e->getMessage());
        }
        
        $this->chatMessages[] = [
            'id' => $msg->id,
            'message' => $msg->message,
            'type' => 'user',
            'sender_name' => $msg->sender_name,
            'sender_photo' => null,
            'is_read' => false,
            'created_at' => $msg->created_at->diffForHumans(),
        ];

        // Notify Admins
        try {
            $admins = User::all();
            foreach ($admins as $admin) {
                try {
                    $admin->notify(new \App\Notifications\NewMessageNotification($msg));
                } catch (\Exception $ne) {
                    \Log::error('Admin notification failed: ' . $ne->getMessage());
                }
                
                \Filament\Notifications\Notification::make()
                    ->title('New Live Chat Message')
                    ->body("From: {$this->name}")
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url('/admin/support-chats'),
                    ])
                    ->sendToDatabase($admin)
                    ->broadcast($admin);
            }
        } catch (\Exception $e) {
            \Log::error('Admin notification process failed in SupportHub: ' . $e->getMessage());
        }

        $this->message = '';
        $this->chatAttachment = null;
        $this->dispatch('scroll-chat-to-bottom');
    }

    public function requestCall()
    {
        $this->validate([
            'phone' => 'required',
        ]);

        $call = CallRequest::create([
            'phone_number' => $this->phone,
            'status' => 'pending',
        ]);

        // Notify Admins — Filament + Slack + Discord
        try {
            $leadName = $this->name ?: 'N/A';
            \App\Services\NotifyService::notifyAll('Callback Requested', "Phone: {$this->phone} | Name: {$leadName}", 'warning');
            $admins = User::all();
            foreach ($admins as $admin) {
                \Filament\Notifications\Notification::make()
                    ->title('Callback Requested')
                    ->body("Phone: {$this->phone}")
                    ->icon('heroicon-o-phone')
                    ->color('warning')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url('/admin/call-requests'),
                    ])
                    ->sendToDatabase($admin)
                    ->broadcast($admin);
            }
        } catch (\Exception $e) {
            \Log::error('Admin notification for call failed: ' . $e->getMessage());
        }

        $this->reset(['phone']);
        session()->flash('call_success', 'Callback requested! We will call you back as soon as possible.');
    }

    public function loadTickets()
    {
        if ($this->email) {
            $this->userTickets = \App\Models\Ticket::where('email', $this->email)
                ->with('messages')
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            
            if ($this->selectedTicket) {
                $this->selectedTicket = collect($this->userTickets)->firstWhere('id', $this->selectedTicket['id']);
            }
        }
    }

    public function selectTicket($ticketId)
    {
        $this->selectedTicket = collect($this->userTickets)->firstWhere('id', $ticketId);
    }

    public function backToTickets()
    {
        $this->selectedTicket = null;
    }

    public function replyToTicket($ticketId)
    {
        $this->validate([
            'ticketMessage' => 'required_without:ticketAttachment|min:2',
            'ticketAttachment' => 'nullable|file|max:10240',
        ]);

        $ticket = \App\Models\Ticket::find($ticketId);
        if ($ticket) {
            $attachmentPath = null;
            if ($this->ticketAttachment) {
                $attachmentPath = $this->ticketAttachment->store('ticket-attachments', 'public');
            }

            $msg = $ticket->messages()->create([
                'sender_type' => 'user',
                'sender_name' => $this->name,
                'message' => $this->ticketMessage,
                'attachment' => $attachmentPath,
            ]);

            try {
                broadcast(new \App\Events\TicketMessageSent($msg))->toOthers();
            } catch (\Exception $e) {
                \Log::error('Ticket broadcast failed: ' . $e->getMessage());
            }

            $this->ticketMessage = '';
            $this->loadTickets();
            
            // Notify Admin
            $admin = User::where('email', 'admin@believoo.com')->first() ?? User::first();
            if ($admin) {
                try {
                    $admin->notify(new \App\Notifications\AdminTicketReplyNotification($ticket, $msg));
                } catch (\Exception $e) {
                    \Log::error('Admin email notification failed: ' . $e->getMessage());
                }
                
                \Filament\Notifications\Notification::make()
                    ->title('New Reply on Ticket')
                    ->body("From: {$this->name} - {$ticket->ticket_id}")
                    ->icon('heroicon-o-chat-bubble-left')
                    ->color('info')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(\App\Filament\Resources\TicketResource::getUrl('view', ['record' => $ticket])),
                    ])
                    ->sendToDatabase($admin)
                    ->broadcast($admin);
            }
        }
    }

    public function createTicket()
    {
        $this->validate([
            'name' => 'required',
            'email' => 'required|email',
            'ticketSubject' => 'required|min:5',
            'ticketMessage' => 'required_without:ticketAttachment|min:10',
            'ticketPriority' => 'required',
            'ticketAttachment' => 'nullable|file|max:10240',
        ]);

        $attachmentPath = null;
        if ($this->ticketAttachment) {
            $attachmentPath = $this->ticketAttachment->store('ticket-attachments', 'public');
        }

        $ticket = \App\Models\Ticket::create([
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $this->ticketSubject,
            'priority' => $this->ticketPriority,
            'status' => 'in_progress', // Changed from 'open' to 'in_progress'
        ]);

        // User's initial message
        $msg = $ticket->messages()->create([
            'sender_type' => 'user',
            'sender_name' => $this->name,
            'message' => $this->ticketMessage ?? '',
            'attachment' => $attachmentPath,
        ]);

        // Auto-Response Message (Template 2)
        $autoMsg = $ticket->messages()->create([
            'sender_type' => 'admin',
            'sender_name' => 'Team Believoo',
            'message' => "Hello,

Thanks for contacting Believoo! This is an automated confirmation that we’ve received your message.

Ticket ID: {$ticket->ticket_id}
Status: In Progress

One of our specialists will review your requirements and respond within 24 hours. In the meantime, feel free to explore our portfolio to see our next-gen work.

Excellence is on its way.

Team Believoo",
        ]);

        try {
            broadcast(new \App\Events\TicketMessageSent($msg))->toOthers();
            broadcast(new \App\Events\TicketMessageSent($autoMsg))->toOthers();
        } catch (\Exception $e) {
            \Log::error('Ticket broadcast from support hub failed: ' . $e->getMessage());
        }

        // Notify Client via Email (Template 2)
        try {
            \Illuminate\Support\Facades\Notification::route('mail', $this->email)
                ->notify(new \App\Notifications\TicketCreatedNotification($ticket));
        } catch (\Exception $e) {
            \Log::error('Client email notification failed: ' . $e->getMessage());
        }

        $ticket_subject = $this->ticketSubject;
        $this->reset(['ticketSubject', 'ticketMessage', 'ticketPriority']);
        $this->loadTickets();
        
        $this->dispatch('ticket-created');
        
        $admin = User::where('email', 'admin@believoo.com')->first() ?? User::first();
        if ($admin) {
                \Filament\Notifications\Notification::make()
                    ->title('New Support Ticket')
                    ->body("From: {$this->name} - {$ticket_subject}")
                    ->icon('heroicon-o-ticket')
                    ->color('warning')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->button()
                            ->url(\App\Filament\Resources\TicketResource::getUrl('view', ['record' => $ticket])),
                    ])
                    ->sendToDatabase($admin)
                    ->broadcast($admin);
        }
    }

    public $aiLang = 'en';
    public $aiTicketState = null; // 'subject', 'message', 'name', 'email', 'preview', 'confirm'
    public $aiTicketRawSubject = '';
    public $aiTicketRawMessage = '';
    public $aiTicketCleanSubject = '';
    public $aiTicketCleanMessage = '';
    public $aiLastAction = null;
    public $aiVoiceEnabled = true;
    public $aiLastAttachment = null;

    // Order/ticket lookup state: 'ask_lookup' = waiting for email/phone/order-id
    public $lookupState = null;
    // Lead collection: 'ask_name', 'ask_phone', 'ask_time'
    public $leadState = null;
    public $leadName = '';
    public $leadPhone = '';
    public $leadTime = '';
    // Auto-escalation: count of consecutive "not understood" replies
    public $aiMissCount = 0;
    // WhatsApp link shown when user wants to continue there
    public $whatsappLink = null;
    // Intent: 'support', 'sales', 'billing' — detected per message
    public $aiIntent = null;
    // Lead score: 'hot', 'warm', 'cold' — based on conversation
    public $leadScore = null;
    // Onboarding state: 'ask_experience'
    public $onboardingState = null;
    // Cross-sell: suggested service after answering
    public $crossSellSuggestion = null;

    public function askAi()
    {
        // Feature 23: rate limiting per session — prevent spam
        $recentCount = AiMessage::where('session_id', $this->sessionId)->where('type', 'user')->where('created_at', '>=', now()->subMinute())->count();
        if ($recentCount > 20) {
            $this->dispatch('rate-limited');
            $this->addSystemMessage('You are sending messages too quickly. Please wait a moment.');
            return;
        }

        $this->validate([
            'aiQuestion' => 'required_without:chatAttachment|min:2',
            'chatAttachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf|max:10240',
        ]);

        $question = trim($this->aiQuestion) ?: 'Please look at this attachment.';
        $attachment = null;
        $lang = $this->detectLanguage($question);
        $this->aiLang = $lang;

        // Save user message to DB
        if ($this->chatAttachment) {
            $attachment = $this->chatAttachment->store('ai-chat', 'public');
            $this->aiLastAttachment = $attachment;
            $this->chatAttachment = null;
        } else {
            $this->aiLastAttachment = null;
        }

        AiMessage::create([
            'session_id' => $this->sessionId,
            'type' => 'user',
            'message' => $question,
            'lang' => $lang,
            'attachment' => $attachment,
        ]);

        $this->aiMessages[] = [
            'type' => 'user',
            'message' => $question,
            'created_at' => 'Just now',
            'attachment' => $attachment,
        ];
        $this->aiQuestion = '';
        $this->aiIsLoading = true;

        // If we are in the middle of a guided flow, continue it first
        if ($this->aiTicketState) {
            $answer = $this->handleAiTicketFlow($question, $lang);
        } elseif ($this->lookupState) {
            $answer = $this->handleLookupFlow($question, $lang);
        } elseif ($this->leadState) {
            $answer = $this->handleLeadFlow($question, $lang);
        } elseif ($this->onboardingState) {
            $answer = $this->handleOnboarding($question, $lang);
        } else {
            $answer = $this->getAiResponse($question, $lang);
        }

        $this->aiIsLoading = false;

        // Save AI reply to DB
        $aiMsg = AiMessage::create([
            'session_id' => $this->sessionId,
            'type' => 'ai',
            'message' => $answer,
            'lang' => $lang,
        ]);

        $this->aiMessages[] = [
            'id' => $aiMsg->id,
            'type' => 'ai',
            'message' => $answer,
            'created_at' => 'Just now',
            'lang' => $lang,
        ];

        $this->dispatch('scroll-chat-to-bottom');
        $this->dispatch('ai-reply', ['message' => $answer, 'lang' => $lang]);
    }

    private function getAiResponse($question, $lang)
    {
        $q = strtolower($question);

        // Check if the user wants to go to dashboard
        if ($this->wantsDashboard($question, $lang)) {
            return $this->translated('go_dashboard', $lang);
        }

        // Check if the user wants to view existing tickets
        if ($this->wantsViewTickets($question, $lang)) {
            return $this->translated('view_tickets', $lang);
        }

        // If user just created a ticket and is asking where to find it or its status
        if ($this->aiLastAction === 'ticket_created' && (str_contains($q, 'kaha') || str_contains($q, 'where') || str_contains($q, 'kaise') || str_contains($q, 'dekhu') || str_contains($q, 'dekho') || str_contains($q, 'dikhao') || str_contains($q, 'status') || str_contains($q, 'ab kya') || str_contains($q, 'age'))) {
            return $this->translated('view_tickets', $lang);
        }

        // ── Sentiment detection: angry/frustrated client → auto-escalate ──
        if ($this->isFrustrated($question)) {
            $this->aiMissCount = 0;
            return $this->humanHandoff($lang, 'frustrated');
        }

        // ── Intent detection: support vs sales vs billing ─────────────
        $this->aiIntent = $this->detectIntent($question);

        // ── Lead scoring: hot/warm/cold based on conversation ──────────
        $this->leadScore = $this->scoreLead($question);

        // ── Abandoned cart: user had pending order, remind them ─────────
        if ($this->checkAbandonedCart($question)) {
            return $this->abandonedCartReply($lang);
        }

        // ── Onboarding: new user asking "how to start" ──────────────────
        if ($this->wantsOnboarding($question)) {
            $this->onboardingState = 'ask_experience';
            return $lang === 'hi'
                ? "Bilkul! Main aapko step-by-step guide karunga. Pehle batao — aapke paas pehle se koi website/domain hai ya bilkul naye ho?"
                : "Great! I'll guide you step-by-step. First — do you already have a website/domain, or starting completely fresh?";
        }

        // Check if the user wants a real human agent
        if ($this->wantsHuman($question, $lang)) {
            return $this->humanHandoff($lang);
        }

        // ── Order / ticket status lookup ──────────────────────────────
        if ($this->wantsStatusLookup($question, $lang)) {
            // If the message itself already contains an email / order id / ticket id, look it up directly
            $result = $this->lookupStatus($question, $lang);
            if ($result !== null) {
                return $result;
            }
            $this->lookupState = 'ask_lookup';
            return $lang === 'hi'
                ? "Ji, bilkul! Apna registered email, phone number, ya order/ticket ID bata dijiye — main abhi check karke batata hoon."
                : "Sure! Please share your registered email, phone number, or order/ticket ID and I'll check the status right away.";
        }

        // ── WhatsApp ──────────────────────────────────────────────────
        if ($this->wantsWhatsapp($question, $lang)) {
            $this->whatsappLink = $this->buildWhatsappLink();
            return $lang === 'hi'
                ? "Ji! Aap humein WhatsApp pe bhi baat kar sakte hain — neeche 'WhatsApp pe baat karein' button se direct chat shuru ho jayegi."
                : "Sure! You can also reach us on WhatsApp — tap the 'Chat on WhatsApp' button below to continue there.";
        }

        // ── Lead / callback request ───────────────────────────────────
        if ($this->wantsCallback($question, $lang)) {
            $this->leadState = 'ask_name';
            return $lang === 'hi'
                ? "Bilkul! Humari team aapko call karegi. Pehle apna naam bata dijiye?"
                : "Of course! Our team will call you back. May I have your name first?";
        }

        // ── Knowledge base ────────────────────────────────────────────
        $kb = $this->searchKnowledgeBase($question);
        if ($kb) {
            $this->aiMissCount = 0;
            $kb->increment('used_count');
            $answer = $kb->content;
            if ($lang === 'hi') {
                $answer .= "\n\nKya isse aapka sawaal solve ho gaya? Agar aur kuch poochna ho toh bataiye!";
            } else {
                $answer .= "\n\nDid that answer your question? Feel free to ask anything else!";
            }
            return $answer;
        }

        // Check if the user is asking to create a ticket
        if ($this->wantsTicket($question, $lang)) {
            $this->aiTicketState = 'subject';
            return $this->translated('ticket_ask_subject', $lang);
        }

        // Try API if enabled
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $aiEnabled = ($settings['ai_enabled'] ?? '0') == '1';
        $model = $settings['ai_model'] ?? 'local';

        $apiKey = match ($model) {
            'openai' => $settings['ai_openai_api_key'] ?? $settings['ai_api_key'] ?? '',
            'gemini' => $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '',
            'claude' => $settings['ai_claude_api_key'] ?? $settings['ai_api_key'] ?? '',
            'divine' => $settings['ai_divine_api_key'] ?? $settings['ai_api_key'] ?? '',
            default => $settings['ai_api_key'] ?? '',
        };

        if ($aiEnabled && $apiKey) {
            try {
                if ($model == 'openai') {
                    $answer = $this->callOpenAi($question, $settings, $apiKey);
                } elseif ($model == 'gemini') {
                    $answer = $this->callGemini($question, $settings, $apiKey, null, $this->aiLastAttachment);
                } elseif ($model == 'claude') {
                    $answer = $this->callClaude($question, $settings, $apiKey);
                } elseif ($model == 'divine') {
                    $answer = $this->callDivine($question, $settings, $apiKey);
                } else {
                    $answer = null;
                }

                if ($answer) {
                    return $answer;
                }
            } catch (\Exception $e) {
                \Log::error('AI API call failed: ' . $e->getMessage());
            }
        }

        $answer = $this->localAiAnswer($question, $lang);

        // If an attachment was sent but we could not analyze it, acknowledge it
        if ($this->aiLastAttachment) {
            $answer .= ($lang === 'hi')
                ? "\n\nAapki attachment bhi mil gayi hai. Iske baare mein detail se jaankari ke liye ticket bana dijiye — humari team dekh legi."
                : "\n\nI have also received your attachment. For detailed help with it, please create a ticket and our team will review it.";
        }

        return $answer;
    }

    private function handleAiTicketFlow($question, $lang)
    {
        // Check if user said no/cancel while in a confirming state
        if ($this->isNegative($question) && in_array($this->aiTicketState, ['preview', 'confirm'])) {
            $this->aiTicketState = 'subject';
            return $this->translated('ticket_again_subject', $lang);
        }

        switch ($this->aiTicketState) {
            case 'subject':
                $this->aiTicketRawSubject = $question;
                $this->aiTicketState = 'message';
                return $this->translated('ticket_ask_message', $lang);

            case 'message':
                $this->aiTicketRawMessage = $question;

                if (!$this->name || !$this->email) {
                    $this->aiTicketState = 'name';
                    return $this->translated('ticket_ask_name', $lang);
                }

                return $this->showTicketPreview($lang);

            case 'name':
                $this->name = $question;
                $this->aiTicketState = 'email';
                return $this->translated('ticket_ask_email', $lang);

            case 'email':
                if (!filter_var($question, FILTER_VALIDATE_EMAIL)) {
                    return $this->translated('ticket_invalid_email', $lang) . ' ' . $this->translated('ticket_ask_email', $lang);
                }
                $this->email = $question;
                return $this->showTicketPreview($lang);

            case 'preview':
                if ($this->isPositive($question)) {
                    return $this->createAiTicket($lang);
                }
                return $this->translated('ticket_confirm_hint', $lang);

            case 'confirm':
                if ($this->isPositive($question)) {
                    return $this->createAiTicket($lang);
                }
                if ($this->isNegative($question)) {
                    $this->aiTicketState = 'subject';
                    return $this->translated('ticket_again_subject', $lang);
                }
                return $this->translated('ticket_confirm_hint', $lang);
        }

        return $this->translated('fallback', $lang);
    }

    private function isPositive($question)
    {
        $q = strtolower(trim($question));
        return in_array($q, ['ok', 'okay', 'yes', 'y', 'haan', 'ha', 'haa', 'han', 'h', 'bilkul', 'sahi hai', 'sahi', 'theek hai', 'theek', 'done', 'confirm', 'submit', 'create', 'banado', 'banao', 'bana do', 'haan bhai', 'ji']) ||
               str_starts_with($q, 'ok ') ||
               str_starts_with($q, 'haan ');
    }

    private function isNegative($question)
    {
        $q = strtolower(trim($question));
        return in_array($q, ['no', 'n', 'nahi', 'na', 'naheen', 'galat', 'sahi nahi', 'change', 'edit', 'badlo', 'dubara']) ||
               str_starts_with($q, 'nahi ');
    }

    private function showTicketPreview($lang)
    {
        // Try to clean up with Gemini if key is present and working
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = $settings['ai_api_key'] ?? '';

        if ($apiKey) {
            $cleaned = $this->cleanTicketWithGemini($this->aiTicketRawSubject, $this->aiTicketRawMessage, $lang, $settings, $apiKey);
            if ($cleaned['subject'] !== $this->aiTicketRawSubject || $cleaned['message'] !== $this->aiTicketRawMessage) {
                $this->aiTicketCleanSubject = $cleaned['subject'];
                $this->aiTicketCleanMessage = $cleaned['message'];
            } else {
                $local = $this->localCleanTicket($this->aiTicketRawSubject, $this->aiTicketRawMessage);
                $this->aiTicketCleanSubject = $local['subject'];
                $this->aiTicketCleanMessage = $local['message'];
            }
        } else {
            $local = $this->localCleanTicket($this->aiTicketRawSubject, $this->aiTicketRawMessage);
            $this->aiTicketCleanSubject = $local['subject'];
            $this->aiTicketCleanMessage = $local['message'];
        }

        $this->aiTicketState = 'preview';

        return $this->translated('ticket_preview', $lang, [
            'subject' => $this->aiTicketCleanSubject,
            'message' => $this->aiTicketCleanMessage,
        ]);
    }

    private function localCleanTicket($subject, $message)
    {
        $cleanSubject = ' ' . strtolower(trim($subject)) . ' ';
        $cleanMessage = ' ' . strtolower(trim($message)) . ' ';

        // Multi-word and phrase fixes (must be done first with spaces)
        $phrases = [
            'kitne dino me' => 'kitne din mein',
            'kitne dino' => 'kitne din',
            'banwaye ge' => 'banwana hai',
            'banane ge' => 'banwana hai',
            'banao ge' => 'banwana hai',
            ' e comars ' => ' ecommerce ',
        ];
        foreach ($phrases as $from => $to) {
            $cleanSubject = str_ireplace($from, $to, $cleanSubject);
            $cleanMessage = str_ireplace($from, $to, $cleanMessage);
        }

        // Word-level typo fixes using regex with word boundaries
        $typos = [
            'websiet' => 'website',
            'webisite' => 'website',
            'comarce' => 'commerce',
            'comars' => 'commerce',
            'ecomars' => 'ecommerce',
            'banbani' => 'banwani',
            'erega' => 'hoga',
            'pric' => 'price',
            'prie' => 'price',
            'usk' => 'uska',
            'usko' => 'uska',
            'ky' => 'kya',
            'h' => 'hai',
        ];

        $pattern = '/\b(' . implode('|', array_map('preg_quote', array_keys($typos))) . ')\b/i';

        $replace = function ($matches) use ($typos) {
            return $typos[strtolower($matches[1])] ?? $matches[1];
        };

        $cleanSubject = preg_replace_callback($pattern, $replace, $cleanSubject);
        $cleanMessage = preg_replace_callback($pattern, $replace, $cleanMessage);

        return [
            'subject' => ucfirst(trim($cleanSubject)),
            'message' => ucfirst(trim($cleanMessage)),
        ];
    }

    private function createAiTicket($lang)
    {
        try {
            $ticket = \App\Models\Ticket::create([
                'name' => $this->name,
                'email' => $this->email,
                'subject' => $this->aiTicketCleanSubject,
                'priority' => 'medium',
                'status' => 'open',
            ]);

            $ticket->messages()->create([
                'sender_type' => 'user',
                'sender_name' => $this->name,
                'message' => $this->aiTicketCleanMessage,
            ]);

            $ticket->messages()->create([
                'sender_type' => 'admin',
                'sender_name' => 'Team Believoo',
                'message' => "Hello,\n\nThanks for contacting Believoo! We have received your ticket.\n\nTicket ID: {$ticket->ticket_id}\nStatus: Open\n\nOne of our specialists will review and respond within 24 hours.\n\nTeam Believoo",
            ]);

            // Reset state
            $this->aiTicketState = null;
            $this->aiTicketRawSubject = '';
            $this->aiTicketRawMessage = '';
            $this->aiTicketCleanSubject = '';
            $this->aiTicketCleanMessage = '';
            $this->aiLastAction = 'ticket_created';

            // Notify admin
            $admin = User::where('email', 'admin@believoo.com')->first() ?? User::first();
            if ($admin) {
                try {
                    $admin->notify(new \App\Notifications\TicketCreatedNotification($ticket));
                } catch (\Exception $e) {
                    \Log::error('Admin ticket notification failed: ' . $e->getMessage());
                }
            }

            return $this->translated('ticket_created', $lang, ['id' => $ticket->ticket_id, 'subject' => $ticket->subject]);
        } catch (\Exception $e) {
            \Log::error('AI ticket creation failed: ' . $e->getMessage());
            return $this->translated('ticket_failed', $lang);
        }
    }

    private function detectLanguage($question)
    {
        // Detect native scripts first
        if (preg_match('/[\x{0900}-\x{097F}]/u', $question)) {
            return 'hi'; // Devanagari (Hindi)
        }
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $question)) {
            return 'ur'; // Arabic/Urdu script
        }
        if (preg_match('/[\x{0980}-\x{09FF}]/u', $question)) {
            return 'bn'; // Bengali
        }
        if (preg_match('/[\x{0B80}-\x{0BFF}]/u', $question)) {
            return 'ta'; // Tamil
        }
        if (preg_match('/[\x{0C00}-\x{0C7F}]/u', $question)) {
            return 'te'; // Telugu
        }
        if (preg_match('/[\x{0A80}-\x{0AFF}]/u', $question)) {
            return 'gu'; // Gujarati
        }
        if (preg_match('/[\x{0A00}-\x{0A7F}]/u', $question)) {
            return 'pa'; // Punjabi (Gurmukhi)
        }
        if (preg_match('/[\x{0C80}-\x{0CFF}]/u', $question)) {
            return 'kn'; // Kannada
        }
        if (preg_match('/[\x{0D00}-\x{0D7F}]/u', $question)) {
            return 'ml'; // Malayalam
        }

        $hinglishWords = ['kyu', 'kyon', 'kyun', 'kaise', 'kya', 'kaun', 'kon', 'kahan', 'kaha', 'kab', 'kitna', 'kitne', 'karo', 'kare', 'karega', 'hoga', 'hai', 'hain', 'ho', 'hona', 'mujhe', 'tujhe', 'aap', 'tum', 'hum', 'mera', 'tera', 'meri', 'teri', 'meraa', 'teraa', 'apna', 'iska', 'uska', 'yeh', 'ye', 'woh', 'wo', 'batao', 'bata', 'bataiye', 'samjhao', 'samajh', 'madad', 'sahayata', 'banado', 'banao', 'bana do', 'bana lo', 'banaiye', 'banaye', 'banaun', 'chahiye', 'chahie', 'dekhna', 'dikhao', 'ho sakta', 'sakta', 'pata', 'jankari', 'jaankari', 'hindi', 'hindime', 'hindi me', 'hindi mein', 'hindi mai'];

        $q = strtolower($question);

        // Use word boundaries so English words like "how" don't trigger on the Hindi word "ho"
        $pattern = '/\b(?:' . implode('|', array_map(function ($word) {
            return preg_quote($word, '/');
        }, $hinglishWords)) . ')\b/i';

        if (preg_match($pattern, $q)) {
            return 'hi';
        }

        return 'en';
    }

    private function wantsTicket($question, $lang)
    {
        $q = strtolower($question);

        // If the user is only asking where to view the ticket, do not treat it as a ticket creation request
        if ($this->wantsViewTickets($question, $lang) || $this->wantsDashboard($question, $lang)) {
            return false;
        }

        $creationWords = ['banao', 'banado', 'banaya', 'bana', 'banaye', 'banau', 'banaun', 'generate', 'create', 'karo', 'karado', 'raise', 'file', 'new', 'naya', 'neww', 'bhejo', 'bana do', 'bana lo', 'chahiye', 'chahie', 'dedo', 'de do', 'do', 'dijiye'];
        $hasTicketWord = str_contains($q, 'ticket') || str_contains($q, 'tikat') || str_contains($q, 'tiket') || str_contains($q, 'tickit') || str_contains($q, 'shikayat') || str_contains($q, 'shiqayat') || str_contains($q, 'samasya') || str_contains($q, 'complaint');

        foreach ($creationWords as $word) {
            if (str_contains($q, $word) && $hasTicketWord) {
                return true;
            }
        }

        // English direct requests
        if ($lang === 'en') {
            return (str_contains($q, 'create') || str_contains($q, 'raise') || str_contains($q, 'open') || str_contains($q, 'new')) && (str_contains($q, 'ticket') || str_contains($q, 'support'));
        }

        return false;
    }

    private function wantsViewTickets($question, $lang)
    {
        $q = strtolower($question);

        $viewWords = ['dekho', 'dekhu', 'dekha', 'dikhao', 'dikhado', 'where', 'kaha', 'kahan', 'kha', 'kaise', 'kaunsa', 'which', 'my', 'mera', 'mere', 'sab', 'all', 'status', 'list', 'view'];
        $ticketWords = ['ticket', 'tikat', 'tiket', 'tickit'];

        $hasTicketWord = false;
        foreach ($ticketWords as $word) {
            if (str_contains($q, $word)) {
                $hasTicketWord = true;
                break;
            }
        }

        if (!$hasTicketWord) {
            return false;
        }

        foreach ($viewWords as $word) {
            if (str_contains($q, $word)) {
                return true;
            }
        }

        return false;
    }

    private function wantsHuman($question, $lang)
    {
        $q = strtolower($question);
        $humanWords = ['human', 'agent', 'operator', 'real person', 'insaan', 'bande', 'bandha', 'bande se', 'kisi se baat', 'baat karni', 'baat karvao', 'baat karwao', 'call karwao', 'call lagao', 'callback', 'executive', 'representative', 'customer care', 'live agent', 'talk to human', 'speak to human', 'talk to agent', 'speak to agent', 'speak to someone', 'kisi se'];

        foreach ($humanWords as $word) {
            if (str_contains($q, $word)) {
                return true;
            }
        }

        return false;
    }

    private function wantsDashboard($question, $lang)
    {
        $q = strtolower($question);
        return str_contains($q, 'dashboard') || str_contains($q, 'portal') || str_contains($q, 'my account') || str_contains($q, 'login') || str_contains($q, 'client area') || (str_contains($q, 'client') && str_contains($q, 'dashboard'));
    }

    private function translated($key, $lang, $params = [])
    {
        $messages = [
            'en' => [
                'ticket_ask_subject' => 'Sure, I can create a support ticket for you. What is the subject or title of the issue?',
                'ticket_ask_message' => 'Please describe the issue in detail so we can help you.',
                'ticket_ask_name' => 'What is your name?',
                'ticket_ask_email' => 'What is your email address?',
                'ticket_invalid_email' => 'That does not look like a valid email.',
                'ticket_preview' => 'I understood your request. Here is the cleaned-up ticket before I submit it:\n\nSubject: :subject\nMessage: :message\n\nIs this correct? Reply "ok" or "yes" to submit, or "no" to edit.',
                'ticket_confirm_hint' => 'Please reply "ok" to submit the ticket, or "no" to change it.',
                'ticket_again_subject' => 'No problem. Please tell me the subject again.',
                'ticket_created' => 'Your ticket has been created successfully. Ticket ID: :id | Subject: :subject. You can view it anytime in the Ticket tab. Our team will respond within 24 hours.',
                'ticket_failed' => 'Sorry, I could not create the ticket right now. Please use the Ticket tab or try again.',
                'view_tickets' => 'You can view all your tickets in the Ticket tab of this widget. If you are logged in, you can also see them in your client dashboard.',
                'go_dashboard' => 'Please log in to your client dashboard to manage tickets, invoices, and services.',
                'fallback' => 'I am not sure I understood that. You can ask about hosting, domains, pricing, say "create a ticket" to raise a support ticket, or "where is my ticket" to view it.',
            ],
            'hi' => [
                'ticket_ask_subject' => 'Bilkul, main aapke liye support ticket bana sakta hoon. Bataiye iska subject kya hai?',
                'ticket_ask_message' => 'Aapki samasya ka poora zikr kijiye taaki hum aapki madad kar saken.',
                'ticket_ask_name' => 'Aapka naam kya hai?',
                'ticket_ask_email' => 'Aapka email address kya hai?',
                'ticket_invalid_email' => 'Yeh sahi email address nahi lag raha.',
                'ticket_preview' => 'Maine aapki baat samajh li. Submit karne se pehle yeh final ticket dekhiye:\n\nSubject: :subject\nMessage: :message\n\nKya yeh sahi hai? Submit karne ke liye "ok" ya "haan" likhein, edit karne ke liye "no" ya "nahi" likhein.',
                'ticket_confirm_hint' => 'Ticket submit karne ke liye "ok" likhein, ya badalne ke liye "no" likhein.',
                'ticket_again_subject' => 'Theek hai. Fir se subject bataiye.',
                'ticket_created' => 'Aapka ticket ban gaya hai. Ticket ID: :id | Subject: :subject. Aap isse isi widget ke Ticket tab mein dekh sakte hain. Hamari team 24 ghante mein jawab degi.',
                'ticket_failed' => 'Maaf kijiye, ticket abhi nahi ban paya. Kripya Ticket tab use karein ya dobara koshish karein.',
                'view_tickets' => 'Apne sab tickets isi widget ke Ticket tab mein dekh sakte hain. Agar aap logged in hain to client dashboard mein bhi dekh sakte hain.',
                'go_dashboard' => 'Tickets, invoices aur services manage karne ke liye apne client dashboard mein login karein.',
                'fallback' => 'Main samajh nahi paya. Aap hosting, domain, price ke bare mein pooch sakte hain, "ticket banao" kahiye to main ticket bana dunga, ya "ticket kaha dekhu" kahiye to location bata dunga.',
            ],
        ];

        $text = $messages[$lang][$key] ?? $messages['en'][$key] ?? '';

        foreach ($params as $k => $v) {
            $text = str_replace(':' . $k, $v, $text);
        }

        return $text;
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 1: Order / Ticket status lookup
    // ══════════════════════════════════════════════════════════════════
    private function wantsStatusLookup($question, $lang)
    {
        $q = strtolower($question);
        $words = [
            'order status', 'my order', 'order ka', 'order kya', 'order ka status', 'order id', 'ord-',
            'ticket status', 'my ticket', 'ticket ka', 'ticket id', 'tk-', 'tkt-',
            'status batao', 'status bata', 'status kya hai', 'kya hua', 'kya hua mera', 'kahan hai',
            'where is my', 'track order', 'track ticket', 'check order', 'check ticket',
            'mera order', 'meri ticket', 'mera ticket', 'order check', 'ticket check',
            'payment status', 'invoice status', 'renewal', 'expiry', 'expire',
        ];
        foreach ($words as $w) {
            if (str_contains($q, $w)) return true;
        }
        return false;
    }

    private function lookupStatus($question, $lang)
    {
        // Try to extract an email, phone, or id from the question
        $email = null;
        if (preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $question, $m)) $email = $m[0];
        $phone = null;
        if (preg_match('/(\+?\d[\d\s\-]{7,14}\d)/', $question, $m)) $phone = preg_replace('/\D/', '', $m[1]);
        $orderNum = null;
        if (preg_match('/(ord[-_]?\w+|order\s*#?\s*\w+|#\d+)/i', $question, $m)) $orderNum = trim($m[1], " #");
        $ticketNum = null;
        if (preg_match('/(tk[t-]?\d+|ticket\s*#?\s*\w+)/i', $question, $m)) $ticketNum = trim($m[1], " #");

        $h = $lang === 'hi';
        $found = [];

        // Search orders
        $order = null;
        if ($orderNum) {
            $order = \App\Models\Order::where('order_number', 'like', "%{$orderNum}%")
                ->orWhere('id', is_numeric($orderNum) ? $orderNum : -1)->first();
        }
        if (!$order && $email) {
            $order = \App\Models\Order::whereHas('user', fn($u) => $u->where('email', $email))
                ->latest()->first();
        }
        if ($order) {
            $status = ucfirst($order->status ?? 'pending');
            $service = $order->service->title ?? $order->service_name ?? 'Service';
            $found[] = $h
                ? "📦 Order #{$order->order_number} ({$service}) — Status: {$status}. Banaya: {$order->created_at->format('d M Y')}."
                : "📦 Order #{$order->order_number} ({$service}) — Status: {$status}. Placed on {$order->created_at->format('d M Y')}.";
        }

        // Search tickets
        $ticket = null;
        if ($ticketNum) {
            $ticket = \App\Models\Ticket::where('ticket_id', 'like', "%{$ticketNum}%")
                ->orWhere('id', is_numeric($ticketNum) ? $ticketNum : -1)->first();
        }
        if (!$ticket && $email) {
            $ticket = \App\Models\Ticket::where('email', $email)->latest()->first();
        }
        if ($ticket) {
            $tStatus = ucfirst($ticket->status ?? 'open');
            $found[] = $h
                ? "🎫 Ticket {$ticket->ticket_id} ({$ticket->subject}) — Status: {$tStatus}. Banaya: {$ticket->created_at->format('d M Y')}."
                : "🎫 Ticket {$ticket->ticket_id} ({$ticket->subject}) — Status: {$tStatus}. Opened on {$ticket->created_at->format('d M Y')}.";
        }

        if ($found) {
            $this->lookupState = null;
            $this->aiMissCount = 0;
            $suffix = $h ? "\n\nAur kuch chahiye? Ticket ya WhatsApp se bhi baat kar sakte hain." : "\n\nAnything else? You can also reach us via ticket or WhatsApp.";
            return implode("\n", $found) . $suffix;
        }

        if ($email || $phone || $orderNum || $ticketNum) {
            $this->lookupState = null;
            return $h
                ? "Is detail se koi order ya ticket nahi mila. Kripya sahi email / order ID / ticket ID se dobara try karein, ya 'ticket banao' kahiye to humari team check karegi."
                : "No order or ticket found with those details. Please try again with the correct email / order ID / ticket ID, or say 'create ticket' and our team will check.";
        }

        return null; // nothing extractable — ask for details
    }

    private function handleLookupFlow($question, $lang)
    {
        $this->lookupState = null;
        $result = $this->lookupStatus($question, $lang);
        if ($result !== null) return $result;
        return $lang === 'hi'
            ? "Maaf kijiye, isme se email / phone / ID samajh nahi aayi. Kripya apna registered email ya order/ticket ID clearly likhein."
            : "Sorry, I couldn't find an email / phone / ID in that. Please type your registered email or order/ticket ID clearly.";
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 7: WhatsApp integration
    // ══════════════════════════════════════════════════════════════════
    private function wantsWhatsapp($question, $lang)
    {
        $q = strtolower($question);
        return str_contains($q, 'whatsapp') || str_contains($q, 'whats app') || str_contains($q, 'wa pe') || str_contains($q, 'whatsapp pe');
    }

    private function buildWhatsappLink()
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $number = preg_replace('/\D/', '', $settings['whatsapp_number'] ?? $settings['contact_phone'] ?? '917830014237');
        $text = urlencode("Hi! I'm chatting with Believoo support (session {$this->sessionId}) and want to continue on WhatsApp.");
        return "https://wa.me/{$number}?text={$text}";
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 4: Lead / callback collection
    // ══════════════════════════════════════════════════════════════════
    private function wantsCallback($question, $lang)
    {
        $q = strtolower($question);
        $words = ['call me', 'call back', 'callback', 'call kar', 'call karo', 'call karna', 'mujhe call', 'phone pe baat', 'baat karna hai call', 'contact me', 'reach me', 'talk on phone', 'phone pe', 'number pe', 'team se baat', 'sales team', 'demo chahiye', 'demo', 'quote chahiye', 'price discuss', 'rates discuss'];
        foreach ($words as $w) {
            if (str_contains($q, $w)) return true;
        }
        return false;
    }

    private function handleLeadFlow($question, $lang)
    {
        $h = $lang === 'hi';
        if ($this->leadState === 'ask_name') {
            $this->leadName = ucwords(trim($question));
            $this->leadState = 'ask_phone';
            return $h
                ? "Shukriya {$this->leadName} ji! Ab apna phone number bata dijiye jispe call karna hai."
                : "Thanks {$this->leadName}! Now please share the phone number we should call you on.";
        }
        if ($this->leadState === 'ask_phone') {
            $phone = preg_replace('/\D/', '', $question);
            if (strlen($phone) < 8 || strlen($phone) > 15) {
                return $h
                    ? "Yeh phone number sahi nahi lag raha. Kripya 10 digit ka number likhein."
                    : "That doesn't look like a valid phone number. Please enter a valid number.";
            }
            $this->leadPhone = $phone;
            $this->leadState = 'ask_time';
            return $h
                ? "Achha! Kab call karein — aaj shaam, kal subah, ya koi specific time? (e.g. 'kal 3 baje')"
                : "Great! When should we call — today evening, tomorrow morning, or a specific time? (e.g. 'tomorrow 3 PM')";
        }
        if ($this->leadState === 'ask_time') {
            $this->leadTime = trim($question);
            $this->leadState = null;

            \App\Models\CallRequest::create([
                'name' => $this->leadName ?: 'AI Chat Lead',
                'phone' => $this->leadPhone,
                'email' => null,
                'message' => "Callback via AI chat. Preferred time: {$this->leadTime}. Session: {$this->sessionId}",
                'status' => 'new',
            ]);

            $name = $this->leadName ?: 'Ji';
            $phone = $this->leadPhone;
            $time = $this->leadTime;
            $this->leadName = $this->leadPhone = $this->leadTime = '';
            $this->aiMissCount = 0;
            return $h
                ? "Done {$name} ji! Humari team {$phone} pe call karegi — preferred time: {$time}. Aur kuch chahiye?"
                : "Done {$name}! Our team will call {$phone} — preferred time: {$time}. Anything else?";
        }
        $this->leadState = null;
        return null;
    }

    // ══════════════════════════════════════════════════════════════════
    // Intent detection — support / sales / billing
    // ══════════════════════════════════════════════════════════════════
    private function detectIntent($question)
    {
        $q = strtolower($question);
        if (preg_match('/price|cost|plan|buy|purchase|order|package|rate|charges|kitna|dam|paisa|discount|offer|demo|trial/i', $q)) return 'sales';
        if (preg_match('/bill|invoice|payment|refund|gst|due|overdue|renew|upgrade|downgrade/i', $q)) return 'billing';
        if (preg_match('/error|not working|down|slow|issue|problem|bug|crash|fix|broken|problem/i', $q)) return 'support';
        return 'general';
    }

    // ══════════════════════════════════════════════════════════════════
    // Lead scoring — hot / warm / cold
    // ══════════════════════════════════════════════════════════════════
    private function scoreLead($question)
    {
        $q = strtolower($question);
        $score = 0;

        // Hot signals
        if (preg_match('/urgent|asap|jaldi|immediately|abhi|right now|emergency|critical/i', $q)) $score += 3;
        if (preg_match('/budget|kitna lagega|how much|cost kya|price kya|rate batao/i', $q)) $score += 2;
        if (preg_match('/buy|purchase|order karna|lena hai|le lo|book karo/i', $q)) $score += 3;
        if (preg_match('/today|aaj|abhi|now/i', $q)) $score += 2;
        if (preg_match('/whatsapp|call|phone|contact/i', $q)) $score += 1;

        // Warm signals
        if (preg_match('/interested|tell me more|details|demo|compare|vs|difference/i', $q)) $score += 1;
        if (preg_match('/how|kya|kaise|kyu|why|what|which/i', $q)) $score += 0.5;

        // If already provided phone/email
        if (preg_match('/\d{10}/', $q) || str_contains($q, '@')) $score += 2;

        $this->leadScore = $score >= 5 ? 'hot' : ($score >= 2 ? 'warm' : 'cold');
        return $this->leadScore;
    }

    // ══════════════════════════════════════════════════════════════════
    // Abandoned cart — user had pending order
    // ══════════════════════════════════════════════════════════════════
    private function checkAbandonedCart($question)
    {
        if (auth()->check()) {
            $pending = \App\Models\Order::where('user_id', auth()->id())
                ->where('status', 'pending')
                ->latest()
                ->first();
            if ($pending) {
                $this->abandonedOrder = $pending;
                return true;
            }
        }
        return false;
    }

    private function abandonedCartReply($lang)
    {
        $order = $this->abandonedOrder ?? null;
        if (!$order) return null;
        $service = $order->service->title ?? 'Service';
        return $lang === 'hi'
            ? "Aapka ek order pending hai — {$service} (₹{$order->total}). Kya aap ise complete karna chahenge? Dashboard → Orders mein jaake pay kar sakte hain."
            : "You have a pending order — {$service} (₹{$order->total}). Would you like to complete it? Go to Dashboard → Orders to pay.";
    }

    // ══════════════════════════════════════════════════════════════════
    // Onboarding flow — new user setup guide
    // ══════════════════════════════════════════════════════════════════
    private function wantsOnboarding($question)
    {
        $q = strtolower($question);
        return str_contains($q, 'shuru') || str_contains($q, 'start') || str_contains($q, 'begin') || str_contains($q, 'kaise karein') || str_contains($q, 'new user') || str_contains($q, 'naye hain') || str_contains($q, 'nayi') || str_contains($q, 'pehli baar');
    }

    private function handleOnboarding($question, $lang)
    {
        $h = $lang === 'hi';
        if ($this->onboardingState === 'ask_experience') {
            $q = strtolower($question);
            $this->onboardingState = null;
            if (str_contains($q, 'naye') || str_contains($q, 'nayi') || str_contains($q, 'new') || str_contains($q, 'fresh') || str_contains($q, 'nahi')) {
                return $h
                    ? "Perfect! Pehle domain choose karein — humari site pe 'Domain' section mein jaayein. Phir hosting ya VPS plan select karein. Kya aapko hosting chahiye ya VPS?"
                    : "Perfect! First pick a domain in our Domain section, then choose a hosting or VPS plan. Do you need shared hosting or a VPS?";
            }
            return $h
                ? "Achha! Aapke paas already domain hai — great! Ab hosting ya VPS plan choose karein. Kya aapko basic hosting chahiye ya powerful VPS?"
                : "Nice! You already have a domain — great! Now pick a hosting or VPS plan. Do you need basic hosting or a powerful VPS?";
        }
        $this->onboardingState = null;
        return null;
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 2: Knowledge base (admin-managed articles)
    // ══════════════════════════════════════════════════════════════════
    private function searchKnowledgeBase($question)
    {
        $q = trim(strtolower($question));
        if (strlen($q) < 3) return null;

        $articles = \App\Models\KnowledgeArticle::where('is_active', true)->get();
        if ($articles->isEmpty()) return null;

        $best = null;
        $bestScore = 0;
        $qWords = preg_split('/\s+/', preg_replace('/[^\w\s]/', ' ', $q));

        foreach ($articles as $a) {
            $score = 0;
            $haystack = strtolower($a->title . ' ' . ($a->keywords ?? '') . ' ' . $a->content);

            foreach ($qWords as $w) {
                if (strlen($w) < 3) continue;
                if (str_contains($haystack, $w)) $score++;
            }
            // Bonus for title match
            if (str_contains(strtolower($a->title), $q)) $score += 5;
            // Bonus for keyword exact match
            if ($a->keywords) {
                foreach (explode(',', strtolower($a->keywords)) as $kw) {
                    if ($kw && str_contains($q, trim($kw))) $score += 3;
                }
            }

            if ($score > $bestScore) { $bestScore = $score; $best = $a; }
        }

        return $bestScore >= 2 ? $best : null;
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 3: Auto-escalation — suggest human after repeated misses
    // ══════════════════════════════════════════════════════════════════
    // ══════════════════════════════════════════════════════════════════
    // Feature 3: Sentiment detection — angry/frustrated client
    // ══════════════════════════════════════════════════════════════════
    private function isFrustrated($question)
    {
        $q = strtolower($question);
        $angry = [
            'bakwas', 'bekar', 'ghatiya', 'fraud', 'cheat', 'scam', 'dhoka', 'loot', 'chor',
            'waste of money', 'waste of time', 'pathetic', 'useless', 'worst', 'terrible',
            'bahut bura', 'bura service', 'ganda', 'nonsense', 'stupid', 'idiot', 'shit',
            'disappointed', 'very bad', 'not working', 'kaam nahi kar', 'kaam nahi',
            'refund karo', 'paisa wapas', 'money back', 'cancel karo', 'band karo',
            'complaint', 'shikayat', 'report kar', 'consumer court',
        ];
        foreach ($angry as $w) {
            if (str_contains($q, $w)) return true;
        }
        return false;
    }

    private function escalateHint($lang)
    {
        $this->aiMissCount++;
        if ($this->aiMissCount >= 2) {
            return $lang === 'hi'
                ? "\n\n💡 Lagta hai iska jawab mere paas abhi nahi hai. Kya aap humari team se baat karna chahenge? 'Team se baat karni hai' likhiye to main live agent connect kar dungi — ya 'ticket banao' likhiye."
                : "\n\n💡 It looks like I don't have that answer right now. Would you like to talk to our team? Type 'talk to team' to connect with a live agent — or 'create ticket' to raise one.";
        }
        return '';
    }

    private function localAiAnswer($question, $lang = 'en')
    {
        $q = strtolower($question);
        $h = $lang === 'hi';

        // Greeting
        if (str_contains($q, 'hello') || str_contains($q, 'hi ') || str_contains($q, 'hey') || str_contains($q, 'namaste') || str_contains($q, 'salam') || str_contains($q, 'kaise ho') || str_contains($q, 'kya haal')) {
            return $h
                ? 'Namaste! Main Believoo ki AI assistant hoon. Aapki kaise madad kar sakti hoon?'
                : 'Hello! I am Believoo\'s AI assistant. How can I help you today?';
        }

        // Language switch
        if (str_contains($q, 'hindi') || str_contains($q, 'hindi me') || str_contains($q, 'hindi mein') || str_contains($q, 'hindime') || str_contains($q, 'hindi mai')) {
            return $h
                ? 'Bilkul, main Hindi mein baat karungi. Aap apni samasya batayein.'
                : 'Sure, I will reply in Hindi. Please tell me your query.';
        }

        // Founder / owner / CEO — never make this up
        if (str_contains($q, 'owner') || str_contains($q, 'founder') || str_contains($q, 'co founder') || str_contains($q, 'cofounder') || str_contains($q, 'ceo') || str_contains($q, 'director') || str_contains($q, 'kon hai') || str_contains($q, 'kon h') || str_contains($q, 'kon hain') || str_contains($q, 'kaun hai') || str_contains($q, 'kaun h') || str_contains($q, 'kaun hain') || str_contains($q, 'malik') || str_contains($q, 'maalik')) {
            return $h
                ? 'Mere paas Believoo ke founders, owners ya management team ki details nahi hain. Iski jaankari ke liye kripya support ticket kholen ya support@believoo.com par email karein.'
                : 'I do not have access to Believoo founders, owners, or management details. Please open a support ticket or email support@believoo.com for this information.';
        }

        // Client-specific / my services / invoices / orders / dashboard (check before general services)
        if (str_contains($q, 'my service') || str_contains($q, 'my invoice') || str_contains($q, 'my order') || str_contains($q, 'my ticket') || str_contains($q, 'my account') || str_contains($q, 'my dashboard') || str_contains($q, 'mera service') || str_contains($q, 'meri service') || str_contains($q, 'mere service') || str_contains($q, 'mera') || str_contains($q, 'meri') || str_contains($q, 'mere') || str_contains($q, 'login') || str_contains($q, 'dashboard') || str_contains($q, 'sign in')) {
            return $h
                ? 'Aapki account details ke liye aapko client dashboard mein login karna hoga: https://believoo.com/client/dashboard. Agar login nahi ho raha toh "create a ticket" ya "ticket banao" likhein.'
                : 'For your account details, please log in to your client dashboard at https://believoo.com/client/dashboard. If you are not logged in, I cannot see your personal data. Type "create a ticket" if you need help.';
        }

        // What services does Believoo offer
        if (str_contains($q, 'what service') || str_contains($q, 'what do you offer') || str_contains($q, 'what do you do') || str_contains($q, 'service') || str_contains($q, 'offer') || str_contains($q, 'kya service') || str_contains($q, 'kya service hai') || str_contains($q, 'kya kaam') || str_contains($q, 'kya offer') || str_contains($q, 'kya karte ho')) {
            $services = cache()->remember('support_hub_services', 300, function () {
                return \App\Models\Service::where('is_active', 1)->pluck('title')->toArray();
            });
            $list = implode(', ', $services);
            return $h
                ? "Believoo yeh services offer karta hai: {$list}. Kisi bhi service ke baare mein aur jaankari ke liye pooch sakte hain."
                : "Believoo offers the following services: {$list}. You can ask about any of these for more details.";
        }

        // About Believoo
        if (str_contains($q, 'about believoo') || str_contains($q, 'what is believoo') || str_contains($q, 'who is believoo') || str_contains($q, 'tell me about') || str_contains($q, 'company') || str_contains($q, 'kya hai believoo') || str_contains($q, 'believoo kya hai')) {
            return $h
                ? 'Believoo ek IT services company hai jo web hosting, VPS, domains, website development, mobile apps, SEO, digital marketing, streaming, AI automation, cloud, DevOps, UI/UX, API development aur cybersecurity services provide karti hai.'
                : 'Believoo is an IT services company offering web hosting, VPS, domains, website development, mobile apps, SEO, digital marketing, streaming, AI automation, cloud, DevOps, UI/UX, API development, and cybersecurity services.';
        }

        // Domain
        if (str_contains($q, 'domain') || str_contains($q, 'domains')) {
            return $h
                ? 'Aap domain search, register aur manage yahan kar sakte hain: https://ghc.believoo.com/domain/. Believoo ke zariye domain, DNS aur hosting services available hain.'
                : 'You can search, register, and manage domains at https://ghc.believoo.com/domain/. Believoo offers domain, DNS, and hosting services.';
        }

        // Hosting / VPS
        if (str_contains($q, 'hosting') || str_contains($q, 'server') || str_contains($q, 'vps')) {
            return $h
                ? 'Believoo web hosting, VPS, cloud aur infrastructure services provide karta hai. Plans dekhne ke liye https://ghc.believoo.com visit karein ya quote ke liye ticket banaein.'
                : 'Believoo provides web hosting, VPS, cloud, and infrastructure services. Visit https://ghc.believoo.com to view plans or create a ticket for a quote.';
        }

        // Website / App development
        if (str_contains($q, 'website') || str_contains($q, 'web design') || str_contains($q, 'app development') || str_contains($q, 'mobile app') || str_contains($q, 'web application') || str_contains($q, 'android app') || str_contains($q, 'ios app')) {
            return $h
                ? 'Believoo custom websites, web apps, Android/iOS mobile apps, UI/UX design aur API integration banata hai. Apni requirement bataein ya ticket banaein.'
                : 'Believoo builds custom websites, web apps, Android/iOS mobile apps, UI/UX design, and API integration. Share your requirements or create a support ticket.';
        }

        // Pricing
        if (str_contains($q, 'price') || str_contains($q, 'pricing') || str_contains($q, 'cost') || str_contains($q, 'plan') || str_contains($q, 'kitna') || str_contains($q, 'paisa') || str_contains($q, 'paise') || str_contains($q, 'rate')) {
            return $h
                ? 'Har project aur service custom hoti hai, isliye exact price apni requirement ke hisaab se milti hai. Sahi quote ke liye apni details batayein ya support ticket banaein.'
                : 'Every project and service is custom, so exact pricing depends on your requirements. Please share your details or create a support ticket for an accurate quote.';
        }

        // SEO / Digital / Adsense
        if (str_contains($q, 'seo') || str_contains($q, 'adsense') || str_contains($q, 'ad management') || str_contains($q, 'digital marketing') || str_contains($q, 'google ads')) {
            return $h
                ? 'Believoo SEO, digital growth, AdSense approval, AdSense management, Play Store / App Store publishing aur digital marketing services provide karta hai.'
                : 'Believoo provides SEO, digital growth, AdSense approval, AdSense management, Play Store / App Store publishing, and digital marketing services.';
        }

        // AI / Automation / Cloud / Cybersecurity
        if (str_contains($q, 'ai') || str_contains($q, 'automation') || str_contains($q, 'cloud') || str_contains($q, 'devops') || str_contains($q, 'cybersecurity') || str_contains($q, 'hardening') || str_contains($q, 'api')) {
            return $h
                ? 'Believoo AI & automation solutions, cloud & DevOps services, API development/integration, cybersecurity aur hardening services provide karta hai.'
                : 'Believoo provides AI & automation solutions, cloud & DevOps services, API development & integration, cybersecurity, and hardening services.';
        }

        // Streaming
        if (str_contains($q, 'stream') || str_contains($q, 'rtmp') || str_contains($q, 'live') || str_contains($q, 'broadcast')) {
            return $h
                ? 'Believoo live streaming add-on aur streaming solutions provide karta hai. Server setup, RTMP, WebRTC, HLS support ke liye ticket banaein.'
                : 'Believoo provides live streaming add-ons and streaming solutions. Please create a ticket for RTMP, WebRTC, HLS, or server setup help.';
        }

        // Ticket / Support
        if (str_contains($q, 'ticket') || str_contains($q, 'support') || str_contains($q, 'help') || str_contains($q, 'madad') || str_contains($q, 'sahayata')) {
            return $h
                ? 'Aap isi widget ke Ticket tab mein ticket bana sakte hain, ya "ticket banao" / "create a ticket" likhein to main aapke liye ticket bana dunga. Humari team 24 ghante mein jawab degi.'
                : 'You can open a support ticket in this widget by switching to the Ticket tab, or say "create a ticket" and I will create one for you. Our team typically responds within 24 hours.';
        }

        // Invoice / Payment
        if (str_contains($q, 'invoice') || str_contains($q, 'payment') || str_contains($q, 'bill') || str_contains($q, 'pay')) {
            return $h
                ? 'Apne invoices aur payment dekhne ke liye client dashboard mein login karein: https://believoo.com/client/dashboard'
                : 'You can view invoices and make payments from your client dashboard after logging in: https://believoo.com/client/dashboard';
        }

        // Contact
        if (str_contains($q, 'contact') || str_contains($q, 'email') || str_contains($q, 'phone') || str_contains($q, 'call') || str_contains($q, 'mail')) {
            return $h
                ? 'Aap humein support@believoo.com par email kar sakte hain, isi widget ke Ticket tab mein ticket bana sakte hain, ya website ke contact page par message bhej sakte hain.'
                : 'You can reach us at support@believoo.com, open a ticket in this widget, or use the contact page on the website.';
        }

        // Thanks
        if (str_contains($q, 'thank') || str_contains($q, 'dhanyavad') || str_contains($q, 'shukriya') || str_contains($q, 'shukria')) {
            return $h
                ? 'Aapka swagat hai! Kuch aur chahiye to batayein.'
                : 'You\'re welcome! Let me know if you need anything else.';
        }

        // Competitor comparison
        if (str_contains($q, 'vs') || str_contains($q, 'compare') || str_contains($q, 'better') || str_contains($q, 'hostinger') || str_contains($q, 'godaddy') || str_contains($q, 'aws') || str_contains($q, 'digitalocean')) {
            return $h
                ? 'Believoo vs competitors — hamare VPS plans mein NVMe SSD, 99.9% uptime, DDoS protection, aur expert support hai. Pricing bhi competitive hai. Kya aap koi specific plan compare karna chahenge?'
                : 'Believoo vs competitors — our VPS plans include NVMe SSD, 99.9% uptime, DDoS protection, and expert support. Our pricing is very competitive. Would you like to compare a specific plan?';
        }

        // Cross-sell: if they asked about VPS, suggest related services
        if ($this->aiIntent === 'sales' && (str_contains($q, 'vps') || str_contains($q, 'hosting') || str_contains($q, 'server'))) {
            $suggestion = $h
                ? "\n\n💡 Tip: Aapko domain, SSL, ya managed services bhi chahiye honge — sab humari site pe available hain. Kuch aur poochna hai?"
                : "\n\n💡 Tip: You might also need a domain, SSL, or managed services — all available on our site. Anything else?";
            $this->crossSellSuggestion = $suggestion;
        }

        // Unknown — do not guess. Add an escalation hint after repeated misses.
        $base = $h
            ? 'Mujhe is sawal ka exact jawab nahi pata. Kripya support ticket kholen ya support@believoo.com par email karein. Humari team aapki madad karegi.'
            : 'I do not have exact information for that. Please open a support ticket or email support@believoo.com and our team will assist you.';
        return $base . $this->escalateHint($lang);
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 4: Smart suggestions — based on chat history
    // ══════════════════════════════════════════════════════════════════
    public function getSmartSuggestions()
    {
        $recent = AiMessage::where('session_id', $this->sessionId)
            ->where('type', 'user')
            ->latest()
            ->limit(10)
            ->pluck('message')
            ->map(fn($m) => strtolower($m));

        $suggestions = [];

        if ($recent->isEmpty()) {
            // Fresh session — generic + page-aware
            $suggestions = ['What services do you offer?', 'VPS plans kya hain?', 'Domain kaise kharidu?'];
        } else {
            $all = $recent->implode(' ');

            if (str_contains($all, 'order') || str_contains($all, 'order')) $suggestions[] = 'Order status check karo';
            if (str_contains($all, 'ticket')) $suggestions[] = 'Ticket status batao';
            if (str_contains($all, 'vps') || str_contains($all, 'hosting')) $suggestions[] = 'VPS pricing batao';
            if (str_contains($all, 'domain')) $suggestions[] = 'Domain price kya hai?';
            if (str_contains($all, 'payment') || str_contains($all, 'bill') || str_contains($all, 'invoice')) $suggestions[] = 'Payment kaise karu?';
            if (str_contains($all, 'refund') || str_contains($all, 'cancel')) $suggestions[] = 'Refund policy kya hai?';

            // Always helpful
            $suggestions[] = 'Team se baat karni hai';
            $suggestions[] = 'Ticket banao';
        }

        return array_slice(array_unique($suggestions), 0, 4);
    }

    private function addSystemMessage($text)
    {
        Message::create([
            'session_id' => $this->sessionId,
            'sender_name' => 'Believoo Support',
            'sender_email' => '',
            'message' => $text,
            'type' => 'admin',
            'is_read' => false,
        ]);
        $this->loadMessages();
    }

    // ── Offline message: no agents online → save as lead ───────────────
    public function sendOfflineMessage()
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'message' => 'required|min:5',
        ]);

        \App\Models\CallRequest::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => 'Offline chat message: ' . $this->message,
            'status' => 'new',
        ]);

        // Notify admin
        try {
            $admin = \App\Models\User::where('is_admin', true)->first() ?? \App\Models\User::first();
            if ($admin) {
                Notification::make()
                    ->title('Offline Chat Message')
                    ->body("{$this->name} ({$this->email}) left a message: " . mb_substr($this->message, 0, 100))
                    ->icon('heroicon-o-envelope')
                    ->sendToDatabase($admin);
            }
        } catch (\Exception $e) {}

        $this->isRegistered = true;
        $this->addSystemMessage('Thanks ' . $this->name . '! We have received your message and will reply to ' . $this->email . ' within 24 hours. You can also create a support ticket for faster help.');
    }

    public function aiFeedback($messageId, $rating)
    {
        if (!$messageId) return;

        AiFeedback::create([
            'ai_message_id' => $messageId,
            'rating' => $rating,
        ]);
    }

    public function humanHandoff($lang = 'en', $reason = null)
    {
        \App\Models\SupportAgent::pruneStaleOnline();
        $agent = \App\Models\SupportAgent::findFree();

        // If the client is frustrated, say so up-front so the agent knows
        $reasonNote = $reason === 'frustrated'
            ? ($lang === 'hi' ? "\n\n⚠️ Client thoda pareshan lag raha hai — kripya turant dhyan dein." : "\n\n⚠️ The client seems upset — please attend right away.")
            : '';

        if ($agent) {
            $displayName = $agent->display_name ?: $agent->name;

            \App\Models\ChatAssignment::create([
                'session_id' => $this->sessionId,
                'agent_id' => $agent->id,
                'status' => 'waiting',
                'assigned_at' => now(),
            ]);

            $agent->increment('active_chats');
            $agent->increment('total_chats');
            $agent->update(['last_assigned_at' => now()]);
            session()->put('assigned_agent_id', $agent->id);

            // Agent joins the live chat with an intro message
            $intro = $lang === 'hi'
                ? "Namaste! Main {$displayName} hoon, Believoo support team se. Main aapki baat AI se le rahi hoon — batayiye main kaise madad karoon?"
                : "Hello! I'm {$displayName} from the Believoo support team. I'm taking over from our AI — how can I help you?";

            Message::create([
                'session_id' => $this->sessionId,
                'sender_name' => $displayName,
                'sender_email' => $agent->email ?? '',
                'message' => $intro,
                'type' => 'admin',
                'admin_id' => $agent->user_id,
                'agent_id' => $agent->id,
                'is_handoff' => true,
                'is_read' => true,
            ]);

            $this->loadMessages();
            $this->activeTab = 'chat';

            // Notify the linked admin user
            if ($agent->user_id) {
                try {
                    Notification::make()
                        ->title('Live Chat Assigned')
                        ->body("You have been connected to a live chat ({$displayName}). Please reply immediately.")
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->sendToDatabase($agent->user)
                        ->broadcast($agent->user);
                } catch (\Exception $e) {
                    \Log::error('Agent notify failed: ' . $e->getMessage());
                }
            }

            return $lang === 'hi'
                ? "Badhiya! Main aapko humari team agent {$displayName} se connect kar rahi hoon. Woh abhi live chat mein aa gaye hain — 'Live Chat' tab mein baat jaari rakhiye."
                : "Great! I'm connecting you with our team agent {$displayName}. They have joined the 'Live Chat' tab — please continue the conversation there.";
        }

        // No agent free — offer ticket or continue with AI
        $this->aiLastAction = 'human_handoff_busy';
        return $lang === 'hi'
            ? 'Abhi humare Believoo team agents sabhi busy hain. Aap thoda wait kar sakte hain, ya "ticket banao" bolke main aapka ticket bana dungi — team jaldi reply karegi. Ya phir mujhe batayiye aapki problem, main abhi madad karne ki koshish karti hoon.'
            : 'Right now all our Believoo team agents are busy. You can wait, or say "create a ticket" and I will file one for you — the team will reply soon. Or just tell me your problem and I will try to help right away.';
    }

    public function rateAgent($rating, $feedback = null)
    {
        $assignment = \App\Models\ChatAssignment::where('session_id', $this->sessionId)
            ->orderBy('id', 'desc')->first();
        if (!$assignment) return;

        $rating = max(1, min(5, (int) $rating));
        $assignment->update([
            'client_rating' => $rating,
            'client_feedback' => $feedback,
        ]);

        $agent = $assignment->agent;
        if ($agent) {
            $count = $agent->rating_count + 1;
            $avg = $agent->avg_rating
                ? (($agent->avg_rating * $agent->rating_count) + $rating) / $count
                : $rating;
            $agent->update([
                'rating_count' => $count,
                'avg_rating' => round($avg, 2),
            ]);
        }

        if ($this->agentChat) {
            $this->agentChat['rated'] = true;
        }
    }

    public function askHuman()
    {
        $lang = $this->aiLang ?: 'en';
        $answer = $this->humanHandoff($lang);

        AiMessage::create([
            'session_id' => $this->sessionId,
            'type' => 'ai',
            'message' => $answer,
            'lang' => $lang,
        ]);

        $this->aiMessages[] = [
            'type' => 'ai',
            'message' => $answer,
            'created_at' => 'Just now',
            'lang' => $lang,
        ];

        $this->dispatch('scroll-chat-to-bottom');
        $this->dispatch('ai-reply', ['message' => $answer, 'lang' => $lang]);
    }

    public function clearAiChat()
    {
        AiMessage::where('session_id', $this->sessionId)->delete();
        $this->aiMessages = [];
        $this->aiTicketState = null;
        $this->aiLastAction = null;

        $welcome = 'Hello! I am Believoo AI. Ask me anything about domains, hosting, websites, apps, SEO, or support tickets. I can also help in Hindi.';
        $this->aiMessages[] = [
            'type' => 'ai',
            'message' => $welcome,
            'created_at' => 'Just now',
            'lang' => 'en',
            'id' => null,
        ];
    }

    public function getPageContext()
    {
        $url = request()->url();
        $context = [];

        if (str_contains($url, '/services/')) {
            $slug = basename($url);
            $service = \App\Models\Service::where('slug', $slug)->first();
            if ($service) {
                $context['current_page'] = 'service:' . $service->title;
                $context['service_slug'] = $slug;
            }
        }

        if (str_contains($url, '/blog')) {
            $context['current_page'] = 'blog';
        }

        if (str_contains($url, '/hosting') || str_contains($url, '/vps')) {
            $context['current_page'] = 'hosting';
        }

        if (str_contains($url, '/domain')) {
            $context['current_page'] = 'domain';
        }

        if (str_contains($url, '/pricing')) {
            $context['current_page'] = 'pricing';
        }

        return $context;
    }

    private function callOpenAi($question, $settings, $apiKey)
    {
        $model = $settings['ai_openai_model'] ?? 'gpt-3.5-turbo';
        $system = $settings['ai_system_prompt'] ?? 'You are a helpful support assistant for Believoo.';

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $question],
                ],
                'max_tokens' => 2000,
            ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        \Log::error('OpenAI error: ' . $response->body());
        return null;
    }

    // ══════════════════════════════════════════════════════════════════
    // Feature 2: Context memory — last N messages for the AI to see
    // ══════════════════════════════════════════════════════════════════
    private function getAiContext($limit = 8)
    {
        $recent = AiMessage::where('session_id', $this->sessionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();

        return $recent->map(function ($m) {
            $role = $m->type === 'user' ? 'Customer' : 'Assistant';
            $text = mb_substr($m->message, 0, 300);
            return "{$role}: {$text}";
        })->implode("\n");
    }

    private function callGemini($question, $settings, $apiKey, $extraSystem = null, $imagePath = null)
    {
        $model = $this->resolveGeminiModel($settings['ai_gemini_model'] ?? '');
        $system = $extraSystem ?: ($settings['ai_system_prompt'] ?? 'You are a helpful support assistant for Believoo.');

        // AI personality from admin settings
        $aiName = $settings['ai_name'] ?? 'Believoo AI';
        $aiTone = $settings['ai_tone'] ?? 'friendly';
        $system .= "\n\nYour name is {$aiName}. Be {$aiTone}. Respond in the customer's language.";

        // Add instruction to reply in user language/script and keep concise
        if (!str_contains(strtolower($system), 'language')) {
            $system .= ' Reply in the same language and script the customer is using. If the customer writes in Roman Hindi or Hinglish, reply in Roman Hindi (English letters), not Devanagari script. Be concise and clear. If the customer wants to open a support ticket, do not create it yourself; tell them to type "ticket banao" or "create a ticket" and the widget will handle it.';
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        // ── Context memory: last 8 messages so AI remembers the chat ──
        $history = $this->getAiContext();
        $contextText = $history ? "\n\nRecent conversation (for context):\n" . $history : '';

        $parts = [['text' => $system . $contextText . "\n\nQuestion: " . $question]];

        // Attach an image if the customer sent one (Gemini vision)
        if ($imagePath && preg_match('/\.(jpe?g|png|gif|webp)$/i', $imagePath)) {
            $full = storage_path('app/public/' . $imagePath);
            if (is_file($full) && filesize($full) <= 4 * 1024 * 1024) {
                $mime = mime_content_type($full) ?: 'image/jpeg';
                $parts[] = ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode(file_get_contents($full))]];
                $parts[0]['text'] .= ' The customer also attached an image — describe what you see and answer accordingly.';
            }
        }

        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => $parts],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 2000,
                    'temperature' => 0.7,
                ],
            ]);

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        \Log::error('Gemini error: ' . $response->body());
        return null;
    }

    private function resolveGeminiModel($model)
    {
        $legacy = ['gemini-pro', 'gemini-1.0-pro', 'gemini-1.0-pro-latest', 'gemini-1.5-pro', 'gemini-1.5-flash', 'gemini-2.5-flash', 'gemini-2.5-flash-lite', 'gemini-pro-latest', 'gemini-ultra', 'gemini-ultra-latest'];

        if (empty($model) || in_array($model, $legacy)) {
            return 'gemini-1.5-flash-latest';
        }

        return $model;
    }

    private function cleanTicketWithGemini($rawSubject, $rawMessage, $lang, $settings, $apiKey)
    {
        $model = $this->resolveGeminiModel($settings['ai_gemini_model'] ?? '');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);

        $langInstruction = $lang === 'hi' ? 'Hindi/Roman Hindi (Hinglish)' : 'English';

        $prompt = "You are a support assistant. The customer wrote a messy support request in {$langInstruction} with spelling mistakes and mixed words. Convert it into a clean, professional subject line and message body. Keep the original meaning. Do not add extra questions or marketing text. Do not use 'Dear' or sign-off. Output in this exact format:\n\nSUBJECT: <clean subject max 12 words>\nMESSAGE: <clean message max 4 sentences>\n\nCustomer wrote:\nSUBJECT: {$rawSubject}\nMESSAGE: {$rawMessage}";

        $response = \Illuminate\Support\Facades\Http::timeout(30)
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 500,
                    'temperature' => 0.3,
                ],
            ]);

        if (!$response->successful()) {
            \Log::error('Gemini ticket cleanup error: ' . $response->body());
            return ['subject' => $rawSubject, 'message' => $rawMessage];
        }

        $text = $response->json('candidates.0.content.parts.0.text') ?: '';

        preg_match('/SUBJECT:\s*(.+?)(?:\n|$)/i', $text, $subjectMatch);
        preg_match('/MESSAGE:\s*(.+?)(?:\n|$)/i', $text, $messageMatch);

        $subject = trim($subjectMatch[1] ?? $rawSubject);
        $message = trim($messageMatch[1] ?? $rawMessage);

        // If Gemini returned prefixes, remove them
        $subject = preg_replace('/^Subject:\s*/i', '', $subject);
        $message = preg_replace('/^Message:\s*/i', '', $message);

        return [
            'subject' => $subject ?: $rawSubject,
            'message' => $message ?: $rawMessage,
        ];
    }

    private function callClaude($question, $settings, $apiKey)
    {
        $model = $settings['ai_claude_model'] ?? 'claude-3-haiku-20240307';
        $system = $settings['ai_system_prompt'] ?? 'You are a helpful support assistant for Believoo.';

        $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 2000,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $question],
                ],
            ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }

        \Log::error('Claude error: ' . $response->body());
        return null;
    }

    private function callDivine($question, $settings, $apiKey)
    {
        $baseUrl = trim($settings['ai_divine_base_url'] ?? '');
        $model = $settings['ai_divine_model'] ?? 'gpt-3.5-turbo';
        $system = $settings['ai_system_prompt'] ?? 'You are a helpful support assistant for Believoo.';

        if (empty($baseUrl)) {
            \Log::error('Divine AI base URL not configured.');
            return null;
        }

        // Ensure the endpoint is /chat/completions
        $url = str_ends_with($baseUrl, '/chat/completions')
            ? $baseUrl
            : rtrim($baseUrl, '/') . '/chat/completions';

        $response = \Illuminate\Support\Facades\Http::withToken($apiKey)
            ->timeout(30)
            ->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $question],
                ],
                'max_tokens' => 2000,
            ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        \Log::error('Divine AI error: ' . $response->body());
        return null;
    }

    public function render()
    {
        return view('livewire.support-hub');
    }
}
