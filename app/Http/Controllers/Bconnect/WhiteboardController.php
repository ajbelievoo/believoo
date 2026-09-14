<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Project;
use App\Models\Bconnect\Whiteboard;
use Illuminate\Http\Request;

class WhiteboardController extends Controller {
    public function show(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        $board = Whiteboard::firstOrCreate(['project_id' => $project->id, 'company_id' => $project->company_id], ['data' => []]);
        return view('bconnect.whiteboard', compact('project', 'board'));
    }

    public function update(Request $r, Project $project) {
        if ($project->company_id != $r->input('bconnect_company_id')) abort(403);
        $r->validate(['data' => 'required|json']);
        $decoded = json_decode($r->data, true);
        Whiteboard::updateOrCreate(['project_id' => $project->id, 'company_id' => $project->company_id], ['data' => $decoded]);
        return response()->json(['success' => true]);
    }
}
