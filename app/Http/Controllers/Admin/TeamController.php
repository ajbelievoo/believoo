<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::orderBy('order', 'asc')->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.teams.index', compact('teams'));
    }

    public function create()
    {
        return view('admin.teams.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'facebook' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teams', 'public');
        } else {
            unset($validated['photo']);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        Team::create($validated);
        return redirect()->route('admin.teams.index')->with('success', 'Team member created');
    }

    public function show(Team $team)
    {
        return view('admin.teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        return view('admin.teams.edit', compact('team'));
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            'facebook' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('teams', 'public');
            if ($team->photo && Storage::disk('public')->exists($team->photo)) {
                Storage::disk('public')->delete($team->photo);
            }
        } else {
            unset($validated['photo']);
        }

        $validated['is_active'] = $request->boolean('is_active', $team->is_active);

        $team->update($validated);
        return redirect()->route('admin.teams.index')->with('success', 'Team member updated');
    }

    public function destroy(Team $team)
    {
        if ($team->photo && Storage::disk('public')->exists($team->photo)) {
            Storage::disk('public')->delete($team->photo);
        }
        $team->delete();
        return redirect()->route('admin.teams.index')->with('success', 'Team member deleted');
    }
}
