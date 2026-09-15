<?php

namespace App\Http\Controllers;

use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function message(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:255',
        ]);

        $sessionId = ($data['session_id'] ?? null) ?: (session()->get('chat_session_id') ?: Str::random(40));
        session()->put('chat_session_id', $sessionId);

        $service = new ChatbotService();
        $result = $service->answer($data['message'], $sessionId, Auth::user(), 'public');

        return response()->json($result);
    }

    public function history(Request $request)
    {
        $sessionId = $request->input('session_id') ?: session()->get('chat_session_id');

        if (!$sessionId) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        $messages = \App\Models\AiMessage::where('session_id', $sessionId)
            ->orderBy('created_at')
            ->get(['type', 'message', 'citations', 'created_at']);

        return response()->json(['success' => true, 'messages' => $messages]);
    }

    public function feedback(Request $request)
    {
        $data = $request->validate([
            'ai_message_id' => 'required|exists:ai_messages,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);


        \App\Models\AiFeedback::create([
            'ai_message_id' => $data['ai_message_id'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }
}
