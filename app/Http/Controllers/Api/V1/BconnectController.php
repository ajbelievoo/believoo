<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Company;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use App\Models\Bconnect\TimeEntry;
use Illuminate\Http\Request;

class BconnectController extends Controller
{
    protected function memberForRequest(Request $request): ?Member
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        $companyId = $request->input('company_id');

        if ($companyId) {
            return Member::where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->first();
        }

        return Member::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
    }

    public function companies(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->memberships()
                ->with('company')
                ->where('is_active', true)
                ->get()
                ->pluck('company'),
        ]);
    }

    public function projects(Request $request)
    {
        $member = $this->memberForRequest($request);

        if (!$member) {
            return response()->json(['success' => false, 'error' => 'No active workspace'], 403);
        }

        $projects = Project::where('company_id', $member->company_id)
            ->when($member->role === 'client', fn ($q) => $q->where('client_id', $member->id))
            ->get();

        return response()->json(['success' => true, 'data' => $projects]);
    }

    public function tickets(Request $request)
    {
        $member = $this->memberForRequest($request);

        if (!$member) {
            return response()->json(['success' => false, 'error' => 'No active workspace'], 403);
        }

        $query = Ticket::with('project', 'reporter.user', 'assignee.user')
            ->where('company_id', $member->company_id)
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($member->role === 'client', function ($q) use ($member) {
                $projectIds = Project::where('client_id', $member->id)->pluck('id');
                $q->whereIn('project_id', $projectIds);
            })
            ->kanban()
            ->get();

        return response()->json(['success' => true, 'data' => $query]);
    }

    public function showTicket(Request $request, Ticket $ticket)
    {
        $member = $this->memberForRequest($request);

        if (!$member || $ticket->company_id !== $member->company_id) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ticket->load('project', 'reporter.user', 'assignee.user', 'comments.member.user', 'timeEntries'),
        ]);
    }

    public function storeTicket(Request $request)
    {
        $member = $this->memberForRequest($request);

        if (!$member) {
            return response()->json(['success' => false, 'error' => 'No active workspace'], 403);
        }

        $data = $request->validate([
            'project_id' => 'required|exists:bconnect_projects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:bug,feature,task,question,support',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'nullable|in:open,in_progress,testing,resolved,closed',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $project = Project::find($data['project_id']);
        if ($project->company_id !== $member->company_id) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        $ticket = Ticket::create([
            'company_id' => $member->company_id,
            'project_id' => $data['project_id'],
            'reporter_id' => $member->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'type' => $data['type'],
            'status' => $data['status'] ?? 'open',
            'priority' => $data['priority'],
            'start_date' => $data['start_date'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'estimated_hours' => $data['estimated_hours'] ?? null,
            'position' => 0,
        ]);

        return response()->json(['success' => true, 'data' => $ticket], 201);
    }

    public function updateTicket(Request $request, Ticket $ticket)
    {
        $member = $this->memberForRequest($request);

        if (!$member || $ticket->company_id !== $member->company_id) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:open,in_progress,testing,resolved,closed',
            'priority' => 'sometimes|in:low,medium,high,critical',
            'assignee_id' => 'nullable|exists:bconnect_members,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'position' => 'sometimes|integer|min:0',
        ]);

        if (isset($data['status']) && $data['status'] === 'resolved') {
            $data['resolved_at'] = now();
        }

        $ticket->update($data);

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    public function timeEntries(Request $request)
    {
        $member = $this->memberForRequest($request);

        if (!$member) {
            return response()->json(['success' => false, 'error' => 'No active workspace'], 403);
        }

        $query = TimeEntry::with('project', 'ticket')
            ->where('company_id', $member->company_id)
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->ticket_id, fn ($q) => $q->where('ticket_id', $request->ticket_id))
            ->latest()
            ->get();

        return response()->json(['success' => true, 'data' => $query]);
    }

    public function storeTimeEntry(Request $request)
    {
        $member = $this->memberForRequest($request);

        if (!$member) {
            return response()->json(['success' => false, 'error' => 'No active workspace'], 403);
        }

        $data = $request->validate([
            'project_id' => 'required|exists:bconnect_projects,id',
            'ticket_id' => 'nullable|exists:bconnect_tickets,id',
            'description' => 'nullable|string|max:500',
            'started_at' => 'required|date',
            'ended_at' => 'required|date|after:started_at',
            'is_billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
        ]);

        $project = Project::find($data['project_id']);
        if ($project->company_id !== $member->company_id) {
            return response()->json(['success' => false, 'error' => 'Not found'], 404);
        }

        if (!empty($data['ticket_id'])) {
            $ticket = Ticket::find($data['ticket_id']);
            if (!$ticket || $ticket->company_id !== $member->company_id) {
                return response()->json(['success' => false, 'error' => 'Not found'], 404);
            }
        }

        $entry = TimeEntry::create([
            'company_id' => $member->company_id,
            'member_id' => $member->id,
            'project_id' => $data['project_id'],
            'ticket_id' => $data['ticket_id'] ?? null,
            'description' => $data['description'] ?? null,
            'started_at' => $data['started_at'],
            'ended_at' => $data['ended_at'],
            'is_billable' => $data['is_billable'] ?? false,
            'hourly_rate' => $data['hourly_rate'] ?? null,
        ]);

        return response()->json(['success' => true, 'data' => $entry], 201);
    }
}
