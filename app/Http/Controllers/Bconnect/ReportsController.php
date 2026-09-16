<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function index(Request $r)
    {
        $companyId = $r->input('bconnect_company_id');
        $start = $r->filled('start') ? Carbon::parse($r->start)->startOfDay() : now()->copy()->subDays(30)->startOfDay();
        $end = $r->filled('end') ? Carbon::parse($r->end)->endOfDay() : now()->copy()->endOfDay();

        $tickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $resolvedTickets = $tickets->whereIn('status', ['resolved', 'closed']);
        $avgResolutionSeconds = $resolvedTickets->isNotEmpty()
            ? $resolvedTickets->avg(fn ($t) => $t->resolved_at ? $t->created_at->diffInSeconds($t->resolved_at) : 0)
            : 0;

        $projectStats = Project::where('company_id', $companyId)
            ->withCount(['tickets as total_tickets_count', 'tickets as resolved_tickets_count' => fn ($q) => $q->whereIn('status', ['resolved', 'closed'])])
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'total' => $p->total_tickets_count,
                'resolved' => $p->resolved_tickets_count,
                'open' => $p->total_tickets_count - $p->resolved_tickets_count,
            ]);

        $memberStats = TimeEntry::where('company_id', $companyId)
            ->whereBetween('started_at', [$start, $end])
            ->whereNotNull('ended_at')
            ->with('member.user')
            ->get()
            ->groupBy('member_id')
            ->map(fn ($entries) => [
                'name' => $entries->first()->member?->user?->name ?? '—',
                'hours' => round($entries->sum('duration_seconds') / 3600, 2),
                'billable_hours' => round($entries->where('is_billable', true)->sum('duration_seconds') / 3600, 2),
                'billed_amount' => $entries->where('is_billable', true)->sum('billed_amount'),
            ])
            ->sortByDesc('hours')
            ->values();

        $dailyTickets = Ticket::where('company_id', $companyId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $revenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $outstanding = Invoice::where('company_id', $companyId)
            ->where('status', 'pending')
            ->sum('amount');

        $totalLoggedHours = round(TimeEntry::where('company_id', $companyId)
            ->whereBetween('started_at', [$start, $end])
            ->sum('duration_seconds') / 3600, 2);

        return view('bconnect.reports', compact(
            'start', 'end', 'tickets', 'resolvedTickets', 'avgResolutionSeconds',
            'projectStats', 'memberStats', 'dailyTickets', 'revenue', 'outstanding', 'totalLoggedHours'
        ));
    }
}
