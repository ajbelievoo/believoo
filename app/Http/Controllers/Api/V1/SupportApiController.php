<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportApiController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * List user's tickets
     * GET /api/v1/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->orWhere('email', $request->user()->email)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($ticket) => [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'subject' => $ticket->subject,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'category' => $ticket->category,
                'created_at' => $ticket->created_at,
                'updated_at' => $ticket->updated_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $tickets,
        ]);
    }

    /**
     * Create new ticket
     * POST /api/v1/tickets
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240', // 10MB max
        ]);

        $ticket = Ticket::create([
            'user_id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'category' => $validated['category'] ?? 'general',
            'attachments' => $this->handleAttachments($request),
        ]);

        // Add initial message
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        // Send notifications
        if (in_array($ticket->priority, ['high', 'urgent'])) {
            $this->alertService->sendTicketAlert($ticket);
        }

        $request->user()->notify(new \App\Notifications\TicketCreatedNotification($ticket));

        return response()->json([
            'success' => true,
            'message' => 'Ticket created successfully',
            'data' => [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'subject' => $ticket->subject,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
            ],
        ], 201);
    }

    /**
     * Get ticket details with messages
     * GET /api/v1/tickets/{ticketId}
     */
    public function show(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::where('ticket_id', $ticketId)
            ->where(function($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhere('email', $request->user()->email);
            })
            ->with('messages.sender')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $ticket->id,
                'ticket_id' => $ticket->ticket_id,
                'subject' => $ticket->subject,
                'priority' => $ticket->priority,
                'status' => $ticket->status,
                'category' => $ticket->category,
                'created_at' => $ticket->created_at,
                'messages' => $ticket->messages->map(fn($msg) => [
                    'id' => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_name' => $msg->sender?->name ?? 'System',
                    'message' => $msg->message,
                    'created_at' => $msg->created_at,
                ]),
            ],
        ]);
    }

    /**
     * Add message to ticket
     * POST /api/v1/tickets/{ticketId}/messages
     */
    public function addMessage(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::where('ticket_id', $ticketId)
            ->where(function($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhere('email', $request->user()->email);
            })
            ->firstOrFail();

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        // Notify admins
        $admins = \App\Models\User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\AdminTicketReplyNotification($ticket, $message));
        }

        return response()->json([
            'success' => true,
            'message' => 'Message added',
            'data' => [
                'id' => $message->id,
                'message' => $message->message,
                'created_at' => $message->created_at,
            ],
        ]);
    }

    /**
     * Close ticket
     * POST /api/v1/tickets/{ticketId}/close
     */
    public function close(Request $request, string $ticketId): JsonResponse
    {
        $ticket = Ticket::where('ticket_id', $ticketId)
            ->where(function($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhere('email', $request->user()->email);
            })
            ->firstOrFail();

        $ticket->update(['status' => 'closed']);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed',
        ]);
    }

    /**
     * Get priority levels reference
     * GET /api/v1/tickets/priorities
     */
    public function priorities(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'low' => [
                    'label' => 'Low',
                    'description' => 'General inquiries, non-urgent issues',
                    'response_time' => '24-48 hours',
                ],
                'medium' => [
                    'label' => 'Medium',
                    'description' => 'Issues affecting functionality',
                    'response_time' => '12-24 hours',
                ],
                'high' => [
                    'label' => 'High',
                    'description' => 'Urgent issues affecting business',
                    'response_time' => '2-4 hours',
                    'alerts' => true,
                ],
                'urgent' => [
                    'label' => 'Urgent',
                    'description' => 'Critical system down',
                    'response_time' => '1 hour',
                    'alerts' => true,
                ],
            ],
        ]);
    }

    /**
     * Handle file attachments
     */
    private function handleAttachments(Request $request): ?array
    {
        if (!$request->hasFile('attachments')) {
            return null;
        }

        $paths = [];
        foreach ($request->file('attachments') as $file) {
            $paths[] = $file->store('ticket-attachments', 'private');
        }

        return $paths;
    }
}
