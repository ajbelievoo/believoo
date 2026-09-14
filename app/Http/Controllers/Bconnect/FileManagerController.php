<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\BconnectFile;
use App\Models\Bconnect\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileManagerController extends Controller {
    public function index(Request $r) {
        $files = BconnectFile::where('company_id', $r->input('bconnect_company_id'))
            ->with('member.user', 'project')
            ->when($r->project_id, fn($q) => $q->where('project_id', $r->project_id))
            ->latest()->paginate(30);
        $projects = Project::where('company_id', $r->input('bconnect_company_id'))->get();
        return view('bconnect.files', compact('files', 'projects'));
    }

    public function store(Request $r) {
        $r->validate(['file' => 'required|file|max:51200', 'project_id' => 'nullable|exists:bconnect_projects,id']);
        $file = $r->file('file');
        $companyId = $r->input('bconnect_company_id');
        $path = $file->store("bconnect/{$companyId}/files", 'public');
        BconnectFile::create([
            'company_id' => $companyId,
            'member_id' => $r->input('bconnect_member')->id,
            'project_id' => $r->project_id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        return back()->with('success', 'File uploaded');
    }

    public function destroy(Request $r, BconnectFile $file) {
        if ($file->company_id != $r->input('bconnect_company_id')) abort(403);
        Storage::disk('public')->delete($file->path);
        $file->delete();
        return back()->with('success', 'File deleted');
    }
}
