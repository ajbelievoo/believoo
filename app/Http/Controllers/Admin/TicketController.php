<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('user')->latest()->paginate(20);
        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'messages', 'assignedTo']);
        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'message' => $validated['message'],
            'is_admin' => true,
        ]);

        return redirect()->back()->with('success', 'Reply sent');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $ticket->update($validated);
        return redirect()->back()->with('success', 'Ticket status updated');
    }
}
