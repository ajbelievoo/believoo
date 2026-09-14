<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportAgent;
use App\Models\ChatAssignment;
use Illuminate\Http\Request;

class SupportAgentController extends Controller
{
    public function index()
    {
        $agents = SupportAgent::latest()->paginate(20);
        return view('admin.support-agents.index', compact('agents'));
    }

    public function create()
    {
        return view('admin.support-agents.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|min:2|max:255',
            'display_name' => 'nullable|max:255',
            'email' => 'required|email|unique:support_agents,email',
            'password' => 'required|min:6',
            'phone' => 'nullable|max:20',
            'max_chats' => 'required|integer|min:1|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_online'] = false;

        SupportAgent::create($validated);

        return redirect()->route('admin.support-agents.index')
            ->with('success', 'Support agent created. They can log in at ' . url('/agent/login'));
    }

    public function edit(SupportAgent $supportAgent)
    {
        return view('admin.support-agents.edit', compact('supportAgent'));
    }

    public function update(Request $request, SupportAgent $supportAgent)
    {
        $validated = $request->validate([
            'name' => 'required|min:2|max:255',
            'display_name' => 'nullable|max:255',
            'email' => 'required|email|unique:support_agents,email,' . $supportAgent->id,
            'password' => 'nullable|min:6',
            'phone' => 'nullable|max:20',
            'max_chats' => 'required|integer|min:1|max:20',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $supportAgent->update($validated);

        return redirect()->route('admin.support-agents.index')
            ->with('success', 'Support agent updated');
    }

    public function destroy(SupportAgent $supportAgent)
    {
        $supportAgent->delete();
        return redirect()->route('admin.support-agents.index')->with('success', 'Agent removed');
    }

    public function performance(SupportAgent $supportAgent)
    {
        $assignments = ChatAssignment::where('agent_id', $supportAgent->id)
            ->latest()->paginate(30);
        return view('admin.support-agents.performance', compact('supportAgent', 'assignments'));
    }

    public function toggleOnline(SupportAgent $supportAgent)
    {
        $supportAgent->update(['is_online' => !$supportAgent->is_online, 'last_seen_at' => now()]);
        return back()->with('success', 'Agent ' . ($supportAgent->is_online ? 'set online' : 'set offline'));
    }
}
