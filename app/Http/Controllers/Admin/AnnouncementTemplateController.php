<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementTemplateController extends Controller
{
    public function index()
    {
        $templates = AnnouncementTemplate::with('creator')->orderBy('name')->paginate(20);
        return view('admin.announcement_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.announcement_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'message_hi' => 'nullable|string',
            'type' => 'required|in:info,warning,important',
            'locale' => 'required|in:en,hi,both',
        ]);

        $data['created_by'] = Auth::id();

        AnnouncementTemplate::create($data);

        return redirect()->route('admin.announcement-templates.index')->with('success', 'Template saved.');
    }

    public function edit(AnnouncementTemplate $announcementTemplate)
    {
        return view('admin.announcement_templates.edit', compact('announcementTemplate'));
    }

    public function update(Request $request, AnnouncementTemplate $announcementTemplate)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'message_hi' => 'nullable|string',
            'type' => 'required|in:info,warning,important',
            'locale' => 'required|in:en,hi,both',
        ]);

        $announcementTemplate->update($data);

        return redirect()->route('admin.announcement-templates.index')->with('success', 'Template updated.');
    }

    public function destroy(AnnouncementTemplate $announcementTemplate)
    {
        $announcementTemplate->delete();
        return redirect()->route('admin.announcement-templates.index')->with('success', 'Template deleted.');
    }
}
