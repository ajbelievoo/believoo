<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use App\Models\Order;
use App\Models\CallRequest;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class ConversionController extends Controller
{
    public function index()
    {
        $week = now()->subDays(7);

        $funnel = [
            'visitors' => AiMessage::where('type', 'user')->where('created_at', '>=', $week)->distinct('session_id')->count('session_id'),
            'chatted' => AiMessage::where('type', 'user')->where('created_at', '>=', $week)->distinct('session_id')->count('session_id'),
            'leads' => CallRequest::where('created_at', '>=', $week)->count(),
            'tickets' => Ticket::where('created_at', '>=', $week)->count(),
            'orders' => Order::where('created_at', '>=', $week)->count(),
            'paid' => Order::where('created_at', '>=', $week)->where('status', 'paid')->count(),
        ];

        // Peak hours — which hour of day has most chats
        $peakHours = AiMessage::where('type', 'user')
            ->where('created_at', '>=', $week)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as cnt'))
            ->groupBy('hour')
            ->orderByDesc('cnt')
            ->get();

        // Daily breakdown
        $daily = AiMessage::where('type', 'user')
            ->where('created_at', '>=', $week)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as cnt'))
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // CSAT — average rating from chat assignments
        $csat = ChatAssignment::whereNotNull('client_rating')->avg('client_rating');

        return view('admin.conversions.index', compact('funnel', 'peakHours', 'daily', 'csat'));
    }
}
