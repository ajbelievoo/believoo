<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller {
    public function index(Request $r) {
        $query = Project::where('company_id', $r->input('bconnect_company_id'));
        if ($r->input('bconnect_role') === 'client') {
            $query->where('client_id', $r->input('bconnect_member')->id);
        }
        $projects = $query->paginate(20);
        return view('bconnect.projects.index', compact('projects'));
    }
    public function create() { return view('bconnect.projects.create'); }
    public function store(Request $r) {
        $companyId = $r->input('bconnect_company_id');
        $data = $r->validate([
            'name' => 'required',
            'description' => 'nullable',
            'client_id' => [
                'nullable',
                Rule::exists('bconnect_members', 'id')->where('company_id', $companyId),
            ],
        ]);
        Project::create($data + ['company_id' => $companyId, 'status' => 'active']);
        return redirect()->route('bconnect.projects.index')->with('success', 'Project created');
    }
    public function show(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        if ($r->input('bconnect_role') === 'client' && $project->client_id != $r->input('bconnect_member')->id) abort(403);
        return view('bconnect.projects.show', compact('project'));
    }
    public function edit(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        return view('bconnect.projects.edit', compact('project'));
    }
    public function update(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        $data = $r->validate([
            'name' => 'required',
            'description' => 'nullable',
            'status' => 'required|in:active,archived,cancelled',
            'client_id' => ['nullable', Rule::exists('bconnect_members', 'id')->where('company_id', $project->company_id)],
        ]);
        $project->update($data);
        return redirect()->route('bconnect.projects.index')->with('success', 'Project updated');
    }
    public function destroy(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        $project->delete();
        return back()->with('success', 'Deleted');
    }
}
