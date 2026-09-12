<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementAnalyticsController extends Controller
{
    public function index()
    {
        $total = Announcement::count();
        $sent = Announcement::whereNotNull('sent_at')->count();
        $scheduled = Announcement::whereNull('sent_at')->whereNotNull('scheduled_at')->count();
        $totalRecipients = AnnouncementRecipient::count();
        $opens = AnnouncementRecipient::whereNotNull('opened_at')->count();
        $clicks = AnnouncementRecipient::whereNotNull('clicked_at')->count();
        $abTests = Announcement::where('ab_enabled', true)->count();
        $recent = Announcement::withCount(['recipients', 'recipients as opened_count' => fn($q) => $q->whereNotNull('opened_at'), 'recipients as clicked_count' => fn($q) => $q->whereNotNull('clicked_at')])
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->take(20)
            ->get();

        $activeAbTests = Announcement::where('ab_enabled', true)
            ->whereIn('ab_status', ['testing', 'winner'])
            ->withCount([
                'recipients as a_sent' => fn ($q) => $q->where('variant', 'A')->where('is_test', true)->whereNotNull('sent_at'),
                'recipients as b_sent' => fn ($q) => $q->where('variant', 'B')->where('is_test', true)->whereNotNull('sent_at'),
                'recipients as a_opens' => fn ($q) => $q->where('variant', 'A')->where('is_test', true)->whereNotNull('opened_at'),
                'recipients as b_opens' => fn ($q) => $q->where('variant', 'B')->where('is_test', true)->whereNotNull('opened_at'),
                'recipients as a_clicks' => fn ($q) => $q->where('variant', 'A')->where('is_test', true)->whereNotNull('clicked_at'),
                'recipients as b_clicks' => fn ($q) => $q->where('variant', 'B')->where('is_test', true)->whereNotNull('clicked_at'),
                'recipients as reserve_count' => fn ($q) => $q->where('is_test', false)->whereNull('sent_at'),
            ])
            ->latest('ab_test_started_at')
            ->take(10)
            ->get();

        return view('admin.announcements.analytics', compact('total', 'sent', 'scheduled', 'totalRecipients', 'opens', 'clicks', 'abTests', 'recent', 'activeAbTests'));
    }

    public function data(Request $request): JsonResponse
    {
        $days = min(90, max(7, (int) $request->input('days', 30)));
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();

        $dates = collect(range(0, $days - 1))->map(fn ($i) => $start->copy()->addDays($i)->format('Y-m-d'));

        $opensPerDay = AnnouncementRecipient::select(DB::raw('DATE(opened_at) as d'), DB::raw('COUNT(*) as c'))
            ->whereNotNull('opened_at')
            ->whereBetween('opened_at', [$start, $end])
            ->groupBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $clicksPerDay = AnnouncementRecipient::select(DB::raw('DATE(clicked_at) as d'), DB::raw('COUNT(*) as c'))
            ->whereNotNull('clicked_at')
            ->whereBetween('clicked_at', [$start, $end])
            ->groupBy('d')
            ->pluck('c', 'd')
            ->toArray();

        $timeSeries = [
            'labels' => $dates->values(),
            'opens' => $dates->map(fn ($d) => $opensPerDay[$d] ?? 0)->values(),
            'clicks' => $dates->map(fn ($d) => $clicksPerDay[$d] ?? 0)->values(),
        ];

        $byProduct = AnnouncementRecipient::select(
            'product',
            DB::raw('SUM(opened_at IS NOT NULL) as opens'),
            DB::raw('SUM(clicked_at IS NOT NULL) as clicks'),
            DB::raw('COUNT(*) as total')
        )
            ->whereNotNull('product')
            ->groupBy('product')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'product' => ucfirst($r->product),
                'opens' => (int) $r->opens,
                'clicks' => (int) $r->clicks,
                'total' => (int) $r->total,
            ])
            ->toArray();

        $byType = Announcement::select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $abTests = Announcement::where('ab_enabled', true)
            ->withCount([
                'recipients as a_sent' => fn ($q) => $q->where('variant', 'A')->where('is_test', true)->whereNotNull('sent_at'),
                'recipients as b_sent' => fn ($q) => $q->where('variant', 'B')->where('is_test', true)->whereNotNull('sent_at'),
                'recipients as a_opens' => fn ($q) => $q->where('variant', 'A')->where('is_test', true)->whereNotNull('opened_at'),
                'recipients as b_opens' => fn ($q) => $q->where('variant', 'B')->where('is_test', true)->whereNotNull('opened_at'),
                'recipients as a_clicks' => fn ($q) => $q->where('variant', 'A')->where('is_test', true)->whereNotNull('clicked_at'),
                'recipients as b_clicks' => fn ($q) => $q->where('variant', 'B')->where('is_test', true)->whereNotNull('clicked_at'),
                'recipients as reserve_count' => fn ($q) => $q->where('is_test', false)->whereNull('sent_at'),
            ])
            ->latest('sent_at')
            ->take(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'status' => $a->ab_status,
                'winner' => $a->ab_winner,
                'a' => [
                    'sent' => $a->a_sent,
                    'opens' => $a->a_opens,
                    'clicks' => $a->a_clicks,
                    'open_rate' => $a->a_sent > 0 ? round(($a->a_opens / $a->a_sent) * 100, 1) : 0,
                    'click_rate' => $a->a_sent > 0 ? round(($a->a_clicks / $a->a_sent) * 100, 1) : 0,
                ],
                'b' => [
                    'sent' => $a->b_sent,
                    'opens' => $a->b_opens,
                    'clicks' => $a->b_clicks,
                    'open_rate' => $a->b_sent > 0 ? round(($a->b_opens / $a->b_sent) * 100, 1) : 0,
                    'click_rate' => $a->b_sent > 0 ? round(($a->b_clicks / $a->b_sent) * 100, 1) : 0,
                ],
                'reserve' => $a->reserve_count,
            ])
            ->toArray();

        $topCampaigns = Announcement::withCount(['recipients', 'recipients as opened_count' => fn ($q) => $q->whereNotNull('opened_at'), 'recipients as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at')])
            ->whereNotNull('sent_at')
            ->having('recipients_count', '>', 0)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'sent' => $a->recipients_count,
                'opens' => $a->opened_count,
                'clicks' => $a->clicked_count,
                'open_rate' => $a->recipients_count > 0 ? round(($a->opened_count / $a->recipients_count) * 100, 1) : 0,
                'click_rate' => $a->recipients_count > 0 ? round(($a->clicked_count / $a->recipients_count) * 100, 1) : 0,
            ])
            ->sortByDesc('open_rate')
            ->take(10)
            ->values()
            ->toArray();

        return response()->json([
            'timeSeries' => $timeSeries,
            'byProduct' => $byProduct,
            'byType' => $byType,
            'abTests' => $abTests,
            'topCampaigns' => $topCampaigns,
        ]);
    }
}
