<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class UserTickets extends Component
{
    public $tickets = [];
    public $showCreate = false;
    public $subject;
    public $message;
    public $priority = 'medium';

    public function mount()
    {
        $this->loadTickets();
    }

    public function loadTickets()
    {
        $this->tickets = Ticket::where('email', Auth::user()->email)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createTicket()
    {
        $this->validate([
            'subject' => 'required|min:5',
            'message' => 'required|min:10',
        ]);

        $ticket = Ticket::create([
            'name' => Auth::user()->name,
            'email' => Auth::user()->email,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'sender_type' => 'user',
            'sender_name' => Auth::user()->name,
            'message' => $this->message,
        ]);

        $this->reset(['subject', 'message', 'priority', 'showCreate']);
        $this->loadTickets();
        session()->flash('ticket_success', 'Support ticket created successfully!');
    }

    public function reopenTicket($id)
    {
        $ticket = Ticket::where('id', $id)->where('email', Auth::user()->email)->firstOrFail();
        
        if (in_array($ticket->status, ['resolved', 'closed'])) {
            $ticket->update(['status' => 'open']);
            $this->loadTickets();
            session()->flash('ticket_success', 'Ticket #'.$id.' has been reopened.');
        }
    }

    public function render()
    {
        return view('livewire.user-tickets');
    }
}
