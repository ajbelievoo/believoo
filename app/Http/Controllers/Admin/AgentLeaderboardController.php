<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportAgent;
use App\Models\ChatAssignment;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class AgentLeaderboardController extends Controller
{
    public function index()
    {
        $week = now()->subDays(7);

        $agents = SupportAgent::withCount([
            'assignments as total_chats' => fn($q) => $q->where('assigned_at', '>=', $week),
            'assignments as closed_chats' => fn($q) => $q->where('status', 'closed')->where('assigned_at', '>=', $week),
        ])->get()->map(function ($agent) use ($week) {
            $agent->avg_rating = ChatAssignment::where('agent_id', $agent->id)
                ->where('assigned_at', '>=', $week)
                ->whereNotNull('client_rating')
                ->avg('client_rating');
            $agent->avg_first_reply = ChatAssignment::where('agent_id', $agent->id)
                ->where('assigned_at', '>=', $week)
                ->whereNotNull('first_reply_seconds')
                ->avg('first_reply_seconds');
            $agent->satisfaction = $agent->avg_rating ? round($agent->avg_rating * 20) : null; // 5-star → %
            return $agent;
        })->sortByDesc('total_chats')->values();

        return view('admin.agent-leaderboard.index', compact('agents'));
    }
}
