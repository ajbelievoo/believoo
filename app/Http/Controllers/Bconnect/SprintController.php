<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Sprint;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $request, Project $project = null)
    {
        $companyId = $request->input('bconnect_company_id');

        if ($request->filled('project')) {
            $project = Project::where('id', $request->input('project'))->where('company_id', $companyId)->first();
        }

        $query = Sprint::with('project')->where('company_id', $companyId);

        if ($project) {
            if ($project->company_id !== $companyId) {
                abort(403);
            }
            $query->where('project_id', $project->id);
        }

        $sprints = $query->orderBy('start_date', 'desc')->paginate(25);
        $projects = Project::where('company_id', $companyId)->get();

        return view('bconnect.sprints', compact('sprints', 'project', 'projects'));
    }

    public function show(Request $request, Sprint $sprint)
    {
        if ($sprint->company_id !== $request->input('bconnect_company_id')) {
            abort(403);
        }

        $sprint->load('tickets.project', 'tickets.reporter.user', 'tickets.assignee.user');

        return view('bconnect.sprint', compact('sprint'));
    }

    public function store(Request $request)
    {
        $companyId = $request->input('bconnect_company_id');

        $data = $request->validate([
            'project_id' => 'required|exists:bconnect_projects,id',
            'name' => 'required|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $project = Project::find($data['project_id']);
        if ($project->company_id !== $companyId) {
            abort(403);
        }

        Sprint::create([
            'company_id' => $companyId,
            'project_id' => $data['project_id'],
            'name' => $data['name'],
            'goal' => $data['goal'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => 'active',
        ]);

        return back()->with('success', 'Sprint created.');
    }

    public function update(Request $request, Sprint $sprint)
    {
        if ($sprint->company_id !== $request->input('bconnect_company_id')) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'goal' => 'nullable|string|max:1000',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:active,completed,cancelled',
        ]);

        $sprint->update($data);

        return back()->with('success', 'Sprint updated.');
    }

    public function destroy(Request $request, Sprint $sprint)
    {
        if ($sprint->company_id !== $request->input('bconnect_company_id')) {
            abort(403);
        }

        $sprint->tickets()->update(['sprint_id' => null]);
        $sprint->delete();

        return back()->with('success', 'Sprint deleted.');
    }
}
