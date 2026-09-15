<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFeedback;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function index(Request $request)
    {
        $query = AiMessage::selectRaw('session_id, MAX(created_at) as last_message_at, COUNT(*) as total_messages, SUM(type = "user") as user_messages')
            ->groupBy('session_id')
            ->orderByDesc('last_message_at');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $sessionIds = AiMessage::where('message', 'like', "%{$q}%")
                ->pluck('session_id')
                ->unique()
                ->toArray();
            $query->whereIn('session_id', $sessionIds);
        }

        $sessions = $query->paginate(25);

        $feedback = AiFeedback::with('aiMessage')
            ->latest()
            ->paginate(25, ['*'], 'feedback_page');

        $stats = [
            'total' => AiMessage::count(),
            'today' => AiMessage::whereDate('created_at', today())->count(),
            'sessions' => AiMessage::distinct('session_id')->count(),
            'avg_rating' => round(AiFeedback::avg('rating') ?? 0, 2),
        ];

        return view('admin.ai-messages.index', compact('sessions', 'feedback', 'stats'));
    }

    public function show(Request $request, string $sessionId)
    {
        $messages = AiMessage::with('user')
            ->where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get();

        $user = $messages->first()?->user;

        return view('admin.ai-messages.show', compact('messages', 'sessionId', 'user'));
    }

    public function destroy(string $sessionId)
    {
        AiMessage::where('session_id', $sessionId)->delete();

        return back()->with('success', 'Conversation deleted.');
    }

    public function feedback(AiFeedback $feedback)
    {
        return view('admin.ai-messages.feedback', compact('feedback'));
    }
}
