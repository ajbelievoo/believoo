<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Ticket;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    protected array $columns = [
        'open' => 'To Do',
        'in_progress' => 'In Progress',
        'testing' => 'Testing',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    public function index(Request $request, Project $project = null)
    {
        $companyId = $request->input('bconnect_company_id');
        $projects = Project::where('company_id', $companyId)->get();
        $sprints = \App\Models\Bconnect\Sprint::where('company_id', $companyId)->orderBy('start_date', 'desc')->get();
        $members = \App\Models\Bconnect\Member::with('user')->where('company_id', $companyId)->where('is_active', true)->get();

        if ($request->filled('project')) {
            $project = Project::where('id', $request->input('project'))->where('company_id', $companyId)->first();
        }

        $query = Ticket::with('project', 'reporter.user', 'assignee.user', 'sprint')
            ->where('company_id', $companyId)
            ->kanban();

        if ($project) {
            if ($project->company_id !== $companyId) {
                abort(403);
            }
            $query->where('project_id', $project->id);
        }

        if ($request->filled('assignee_id')) {
            if ($request->assignee_id === 'unassigned') {
                $query->whereNull('assignee_id');
            } else {
                $query->where('assignee_id', $request->assignee_id);
            }
        }

        if ($request->filled('sprint_id')) {
            $query->where('sprint_id', $request->sprint_id);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->get()->groupBy('status');

        return view('bconnect.kanban', compact('project', 'projects', 'sprints', 'members', 'tickets'));
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        if ($ticket->company_id !== $request->input('bconnect_company_id')) {
            abort(403);
        }

        $data = $request->validate([
            'status' => 'required|in:open,in_progress,testing,resolved,closed',
            'position' => 'nullable|integer|min:0',
        ]);

        $ticket->update([
            'status' => $data['status'],
            'position' => $data['position'] ?? 0,
            'resolved_at' => $data['status'] === 'resolved' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'ticket' => [
                'id' => $ticket->id,
                'status' => $ticket->status,
                'position' => $ticket->position,
            ],
        ]);
    }

    public function reorder(Request $request, Project $project = null)
    {
        $companyId = $request->input('bconnect_company_id');

        if ($project && $project->company_id !== $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'tickets' => 'required|array',
            'tickets.*.id' => 'required|exists:bconnect_tickets,id',
            'tickets.*.status' => 'required|in:open,in_progress,testing,resolved,closed',
            'tickets.*.position' => 'required|integer|min:0',
        ]);

        foreach ($data['tickets'] as $item) {
            $ticket = Ticket::where('id', $item['id'])->where('company_id', $companyId)->first();
            if ($ticket) {
                $ticket->update([
                    'status' => $item['status'],
                    'position' => $item['position'],
                    'resolved_at' => $item['status'] === 'resolved' ? now() : null,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }
}
