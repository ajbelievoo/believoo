<?php

namespace App\Livewire;

use App\Models\Bconnect\Ticket as BconnectTicket;
use App\Models\Ghc\SupportTicket as GhcTicket;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class SupportTickets extends Component
{
    use WithFileUploads;

    public $tickets = [];
    public $showCreate = false;
    public $subject;
    public $message;
    public $priority = 'medium';
    public $category = 'Believoo';
    public $attachment;

    protected $rules = [
        'subject' => 'required|min:5',
        'message' => 'required|min:10',
        'priority' => 'required|in:low,medium,high,urgent',
        'category' => 'required|in:Believoo,GHC,Bmydesk,Webmail,Other',
        'attachment' => 'nullable|file|max:5120',
    ];

    public function mount()
    {
        $this->tickets = collect();
        $this->loadTickets();
    }

    public function loadTickets()
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        $email = $user->email;

        $items = collect();

        // Believoo tickets
        try {
            Ticket::where('email', $email)
                ->orderBy('created_at', 'desc')
                ->get()
                ->each(function ($t) use ($items) {
                    $items->push([
                        'id' => $t->id,
                        'ticket_id' => $t->ticket_id,
                        'subject' => $t->subject,
                        'message' => $t->messages->first()->message ?? '',
                        'status' => $t->status,
                        'platform' => $t->category ?? 'Believoo',
                        'created_at' => $t->created_at,
                        'source' => 'Believoo',
                        'url' => null,
                    ]);
                });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Believoo tickets load failed: ' . $e->getMessage());
        }

        // Bmydesk tickets
        try {
            BconnectTicket::whereHas('reporter', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with('reporter')
            ->orderBy('created_at', 'desc')
            ->get()
            ->each(function ($t) use ($items) {
                $items->push([
                    'id' => $t->id,
                    'ticket_id' => 'BCT-' . $t->id,
                    'subject' => $t->title,
                    'message' => $t->description,
                    'status' => $t->status,
                    'platform' => 'Bmydesk',
                    'created_at' => $t->created_at,
                    'source' => 'Bmydesk',
                    'url' => 'https://bc.believoo.com/dashboard/tickets',
                ]);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Bmydesk tickets load failed: ' . $e->getMessage());
        }

        // GHC tickets
        try {
            GhcTicket::on('ghc')
                ->where('email', $email)
                ->with('replies')
                ->orderBy('created_at', 'desc')
                ->get()
                ->each(function ($t) use ($items) {
                    $items->push([
                        'id' => $t->id,
                        'ticket_id' => 'GHC-' . strtoupper(substr($t->id, 0, 8)),
                        'subject' => $t->subject,
                        'message' => $t->message,
                        'status' => strtolower($t->status),
                        'platform' => 'GHC',
                        'created_at' => $t->created_at,
                        'source' => 'GHC',
                        'url' => 'https://ghc.believoo.com/dashboard/support',
                    ]);
                });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('GHC tickets load failed: ' . $e->getMessage());
        }

        $this->tickets = $items->sortByDesc('created_at')->values();
    }

    public function createTicket()
    {
        $this->validate();

        $user = Auth::user();

        $mailData = [
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'message' => $this->message,
            'platform' => $this->category,
            'url' => null,
        ];

        // Believoo / Webmail / Other -> create central ticket
        if (in_array($this->category, ['Believoo', 'Webmail', 'Other'])) {
            $ticket = $this->createBelievooTicket($user);

            try {
                \Illuminate\Support\Facades\Mail::to($this->getSupportEmail())
                    ->send(new \App\Mail\SupportTicketCreated($ticket));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Support ticket email failed: ' . $e->getMessage());
            }
        }

        // GHC -> create ticket in GHC SQLite DB
        if ($this->category === 'GHC') {
            try {
                $ghcUser = \App\Models\Ghc\User::on('ghc')->where('email', $user->email)->first();
                $ticketId = \Illuminate\Support\Str::uuid();
                $ghcTicket = GhcTicket::on('ghc')->create([
                    'id' => $ticketId,
                    'user_id' => $ghcUser?->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'category' => 'General',
                    'subject' => $this->subject,
                    'status' => 'OPEN',
                ]);
                $ghcTicket->replies()->create([
                    'id' => \Illuminate\Support\Str::uuid(),
                    'ticket_id' => $ticketId,
                    'sender' => 'user',
                    'message' => $this->message,
                ]);

                $mailData['url'] = 'https://ghc.believoo.com/dashboard/support';
                \Illuminate\Support\Facades\Mail::to($this->getSupportEmail())
                    ->send(new \App\Mail\SupportTicketExternal($mailData));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('GHC ticket creation failed: ' . $e->getMessage());
            }
        }

        // Bmydesk -> create ticket in bconnect_tickets (or Believoo as fallback)
        if ($this->category === 'Bmydesk') {
            try {
                $member = \App\Models\Bconnect\Member::where('user_id', $user->id)->first();
                if ($member) {
                    $project = \App\Models\Bconnect\Project::where('company_id', $member->company_id)->first();
                    $bct = BconnectTicket::create([
                        'title' => $this->subject,
                        'description' => $this->message,
                        'status' => 'open',
                        'priority' => $this->priority,
                        'reporter_id' => $member->id,
                        'company_id' => $member->company_id,
                        'project_id' => $project?->id ?? $member->company_id,
                    ]);

                    $mailData['url'] = 'https://bc.believoo.com/dashboard/tickets';
                    \Illuminate\Support\Facades\Mail::to($this->getSupportEmail())
                        ->send(new \App\Mail\SupportTicketExternal($mailData));
                } else {
                    $ticket = $this->createBelievooTicket($user);
                    $mailData['platform'] = 'Bmydesk (via Believoo)';
                    \Illuminate\Support\Facades\Mail::to($this->getSupportEmail())
                        ->send(new \App\Mail\SupportTicketCreated($ticket));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bmydesk ticket creation failed: ' . $e->getMessage());
            }
        }

        $this->reset(['subject', 'message', 'priority', 'category', 'attachment', 'showCreate']);
        $this->loadTickets();
        session()->flash('ticket_success', 'Support ticket created successfully!');
    }

    protected function createBelievooTicket($user)
    {
        $ticket = Ticket::create([
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'category' => $this->category,
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'sender_type' => 'user',
            'sender_name' => $user->name,
            'message' => $this->message,
        ]);

        if ($this->attachment) {
            $path = $this->attachment->store('ticket-attachments/' . $ticket->id, 'public');
            $ticket->update(['attachments' => $path]);
        }

        return $ticket;
    }

    public function reopenTicket($id)
    {
        if (!Auth::check()) {
            return;
        }
        $ticket = Ticket::where('id', $id)->where('email', Auth::user()->email)->firstOrFail();

        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
            $this->loadTickets();
            session()->flash('ticket_success', 'Ticket #' . $id . ' has been reopened.');
        }
    }

    protected function getSupportEmail(): string
    {
        return \App\Models\Setting::where('key', 'support_email')->value('value') ?? 'support@believoo.com';
    }

    public function render()
    {
        return view('livewire.support-tickets')->layout('components.layouts.believoo', ['title' => 'Support Tickets']);
    }
}
