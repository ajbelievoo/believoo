<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Sprint;
use App\Models\Bconnect\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $r)
    {
        $companyId = $r->input('bconnect_company_id');
        $month = $r->filled('month') ? Carbon::parse($r->month) : now();
        $start = $month->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $projectId = $r->project_id;
        $filterProject = $projectId ? fn ($q) => $q->where('project_id', $projectId) : fn ($q) => $q;

        $meetings = Meeting::where('company_id', $companyId)
            ->whereNotNull('scheduled_at')
            ->whereBetween('scheduled_at', [$start, $end])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->with('project', 'creator.user')
            ->get()
            ->map(fn ($m) => [
                'type' => 'meeting',
                'date' => $m->scheduled_at->format('Y-m-d'),
                'title' => $m->title,
                'time' => $m->scheduled_at->format('H:i'),
                'url' => route('bconnect.meeting.room', $m->room_id),
                'project' => $m->project?->name,
            ]);

        $tickets = Ticket::where('company_id', $companyId)
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$start, $end])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->with('project')
            ->get()
            ->map(fn ($t) => [
                'type' => 'ticket',
                'date' => $t->due_date->format('Y-m-d'),
                'title' => 'Due: ' . $t->title,
                'url' => route('bconnect.tickets.show', $t->id),
                'project' => $t->project?->name,
            ]);

        $sprints = Sprint::where('company_id', $companyId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end])
                  ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->with('project')
            ->get()
            ->map(fn ($s) => [
                'type' => 'sprint',
                'date' => $s->start_date->format('Y-m-d'),
                'title' => 'Sprint: ' . $s->name,
                'url' => route('bconnect.sprints.show', $s->id),
                'project' => $s->project?->name,
            ]);

        $events = $meetings->merge($tickets)->merge($sprints)
            ->groupBy('date')
            ->sortKeys();

        $projects = Project::where('company_id', $companyId)->pluck('name', 'id');

        return view('bconnect.calendar', compact('month', 'start', 'end', 'events', 'projects', 'projectId'));
    }
}
