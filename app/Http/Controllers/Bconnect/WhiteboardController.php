<?php

namespace App\Http\Controllers\Bconnect;

use App\Events\BconnectWhiteboardUpdated;
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
        $data = $r->validate(['data' => 'required|json', 'stroke' => 'nullable|array']);
        $decoded = json_decode($data['data'], true);

        $board = Whiteboard::firstOrCreate(['project_id' => $project->id, 'company_id' => $project->company_id], ['data' => []]);

        // If a single stroke is provided, append it to existing strokes to reduce overwrite conflicts
        if (!empty($data['stroke'])) {
            $existing = $board->data ?: [];
            $existing[] = $data['stroke'];
            $board->update(['data' => $existing]);
            broadcast(new BconnectWhiteboardUpdated(
                $project->company_id,
                $project->id,
                $data['stroke'],
                $r->input('bconnect_member')->id,
                $r->input('bconnect_member')->user->name
            ))->toOthers();
            return response()->json(['success' => true]);
        }

        // Full canvas save (e.g. auto-save)
        $board->update(['data' => $decoded]);
        return response()->json(['success' => true]);
    }
}
