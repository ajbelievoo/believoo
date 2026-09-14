<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::with(['user', 'agreement'])->latest()->paginate(20);
        return view('admin.projects.index', compact('projects'));
    }

    public function show(Project $project)
    {
        $project->load(['user', 'agreement', 'tasks', 'assets']);
        return view('admin.projects.show', compact('project'));
    }

    public function updateStatus(Request $request, Project $project)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,on_hold,cancelled',
        ]);

        $project->update($validated);
        return redirect()->back()->with('success', 'Project status updated');
    }
}
