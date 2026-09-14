<?php
// Feature 5: AI Analytics admin controller
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use App\Models\AiFeedback;
use App\Models\ChatAssignment;
use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class AiAnalyticsController extends Controller
{
    public function index()
    {
        $today = today();
        $week = now()->subDays(7);

        $stats = [
            'total_chats'      => AiMessage::where('type', 'user')->count(),
            'today_chats'      => AiMessage::where('type', 'user')->whereDate('created_at', $today)->count(),
            'week_chats'       => AiMessage::where('type', 'user')->where('created_at', '>=', $week)->count(),
            'ai_replies'       => AiMessage::where('type', 'ai')->count(),
            'feedback'         => AiFeedback::count(),
            'positive_feedback'=> AiFeedback::where('rating', 'positive')->count(),
            'negative_feedback'=> AiFeedback::where('rating', 'negative')->count(),
            'tickets_created'  => Ticket::where('created_at', '>=', $week)->count(),
            'human_handoffs'   => ChatAssignment::where('assigned_at', '>=', $week)->count(),
            'kb_articles'      => KnowledgeArticle::where('is_active', true)->count(),
            'kb_used'          => KnowledgeArticle::sum('used_count'),
        ];

        // Top topics — most asked words
        $topics = AiMessage::where('type', 'user')
            ->where('created_at', '>=', $week)
            ->pluck('message')
            ->flatMap(fn($m) => preg_split('/\s+/', strtolower($m)))
            ->filter(fn($w) => strlen($w) >= 4)
            ->countBy()
            ->sortDesc()
            ->take(15);

        // Language distribution
        $langs = AiMessage::where('type', 'user')
            ->where('created_at', '>=', $week)
            ->select('lang', DB::raw('count(*) as cnt'))
            ->groupBy('lang')
            ->orderByDesc('cnt')
            ->get();

        return view('admin.ai-analytics.index', compact('stats', 'topics', 'langs'));
    }
}
