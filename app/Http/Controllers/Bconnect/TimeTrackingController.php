<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\TimeEntry;
use Illuminate\Http\Request;

class TimeTrackingController extends Controller
{
    public function index(Request $request, Project $project = null)
    {
        $companyId = $request->input('bconnect_company_id');
        $memberId = $request->input('bconnect_member')->id;

        if ($request->filled('project')) {
            $project = Project::where('id', $request->input('project'))->where('company_id', $companyId)->first();
        }

        $query = TimeEntry::with('ticket', 'project')
            ->where('company_id', $companyId);

        if ($project) {
            if ($project->company_id !== $companyId) {
                abort(403);
            }
            $query->where('project_id', $project->id);
        }

        $entries = $query->where('member_id', $memberId)->latest()->paginate(25);

        $activeEntry = TimeEntry::where('company_id', $companyId)
            ->where('member_id', $memberId)
            ->whereNull('ended_at')
            ->first();

        $projects = Project::where('company_id', $companyId)->get();

        return view('bconnect.time-tracking', compact('entries', 'activeEntry', 'project', 'projects'));
    }

    public function start(Request $request)
    {
        $companyId = $request->input('bconnect_company_id');
        $member = $request->input('bconnect_member');

        // Stop any active timer
        $this->stopActive($companyId, $member->id);

        $data = $request->validate([
            'ticket_id' => 'nullable|exists:bconnect_tickets,id',
            'project_id' => 'required|exists:bconnect_projects,id',
            'description' => 'nullable|string|max:500',
            'is_billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $project = Project::find($data['project_id']);
        if ($project->company_id !== $companyId) {
            abort(403);
        }

        $ticket = null;
        if (!empty($data['ticket_id'])) {
            $ticket = Ticket::find($data['ticket_id']);
            if ($ticket->company_id !== $companyId) {
                abort(403);
            }
        }

        $entry = TimeEntry::create([
            'company_id' => $companyId,
            'member_id' => $member->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket?->id,
            'description' => $data['description'] ?? null,
            'started_at' => now(),
            'is_billable' => $data['is_billable'] ?? false,
            'hourly_rate' => $data['hourly_rate'] ?? null,
        ]);

        return back()->with('success', 'Timer started.');
    }

    public function stop(Request $request, TimeEntry $entry)
    {
        $member = $request->input('bconnect_member');

        if ($entry->member_id !== $member->id) {
            abort(403);
        }

        $entry->update(['ended_at' => now()]);

        return back()->with('success', 'Timer stopped.');
    }

    public function store(Request $request)
    {
        $companyId = $request->input('bconnect_company_id');
        $member = $request->input('bconnect_member');

        $data = $request->validate([
            'ticket_id' => 'nullable|exists:bconnect_tickets,id',
            'project_id' => 'required|exists:bconnect_projects,id',
            'description' => 'nullable|string|max:500',
            'started_at' => 'required|date',
            'ended_at' => 'required|date|after:started_at',
            'is_billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $project = Project::find($data['project_id']);
        if ($project->company_id !== $companyId) {
            abort(403);
        }

        $ticket = null;
        if (!empty($data['ticket_id'])) {
            $ticket = Ticket::find($data['ticket_id']);
            if ($ticket->company_id !== $companyId) {
                abort(403);
            }
        }

        TimeEntry::create([
            'company_id' => $companyId,
            'member_id' => $member->id,
            'project_id' => $project->id,
            'ticket_id' => $ticket?->id,
            'description' => $data['description'] ?? null,
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'],
            'is_billable' => $data['is_billable'] ?? false,
            'hourly_rate' => $data['hourly_rate'] ?? null,
        ]);

        return back()->with('success', 'Time entry added.');
    }

    protected function stopActive(int $companyId, int $memberId): void
    {
        $active = TimeEntry::where('company_id', $companyId)
            ->where('member_id', $memberId)
            ->whereNull('ended_at')
            ->first();

        if ($active) {
            $active->update(['ended_at' => now()]);
        }
    }
}
