<?php

namespace App\Http\Controllers;

use App\Models\UserTeam;
use App\Models\TeamUser;
use App\Models\User;
use App\Models\TeamInvitation;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $ownedTeams = $user->ownedTeams()->with('users')->get();
        $joinedTeams = $user->teams()->with('owner')->get();
        
        return view('teams.index', compact('ownedTeams', 'joinedTeams'));
    }

    public function create()
    {
        return view('teams.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'invite_emails' => 'array',
            'invite_emails.*' => 'email|exists:users,email',
            'invite_roles' => 'array',
            'invite_roles.*' => ['required', Rule::in(array_keys(TeamUser::ROLES))],
        ]);

        $team = UserTeam::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'owner_id' => Auth::id(),
        ]);

        // Add owner as team member
        TeamUser::create([
            'user_team_id' => $team->id,
            'user_id' => Auth::id(),
            'role' => 'owner',
        ]);

        // Send invitations if provided
        $invitationsSent = 0;
        if (!empty($validated['invite_emails'])) {
            foreach ($validated['invite_emails'] as $index => $email) {
                if (empty($email)) continue;
                
                $role = $validated['invite_roles'][$index] ?? 'member';
                
                // Check if user is already a team member
                $user = User::where('email', $email)->first();
                if ($user && $team->hasUser($user)) {
                    continue; // Skip if already a member
                }

                // Check if there's already a pending invitation
                $existingInvitation = TeamInvitation::where('user_team_id', $team->id)
                    ->where('email', $email)
                    ->where('status', 'pending')
                    ->where('expires_at', '>', now())
                    ->first();
                    
                if ($existingInvitation) {
                    continue; // Skip if invitation already sent
                }

                // Create invitation
                $invitation = TeamInvitation::create([
                    'user_team_id' => $team->id,
                    'invited_by' => Auth::id(),
                    'email' => $email,
                    'role' => $role,
                    'permissions' => [], // Default permissions for invited members
                ]);

                // Send email notification
                try {
                    Notification::route('mail', $email)->notify(new TeamInvitationNotification($invitation));
                    $invitationsSent++;
                } catch (\Exception $e) {
                    \Log::error('Failed to send team invitation email: ' . $e->getMessage());
                }
            }
        }

        $message = 'Team created successfully!';
        if ($invitationsSent > 0) {
            $message .= " {$invitationsSent} invitation(s) sent.";
        }

        return redirect()->route('teams.show', $team)
            ->with('success', $message);
    }

    public function show(UserTeam $team)
    {
        $this->authorizeTeamAccess($team);
        
        $team->load(['owner', 'users']);
        $userRole = $team->getUserRole(Auth::user());
        $userPermissions = $team->getUserPermissions(Auth::user());
        
        return view('teams.show', compact('team', 'userRole', 'userPermissions'));
    }

    public function edit(UserTeam $team)
    {
        $this->authorizeTeamManagement($team);
        
        return view('teams.edit', compact('team'));
    }

    public function update(Request $request, UserTeam $team)
    {
        $this->authorizeTeamManagement($team);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $team->update($validated);

        return redirect()->route('teams.show', $team)
            ->with('success', 'Team updated successfully!');
    }

    public function destroy(UserTeam $team)
    {
        $this->authorizeTeamOwnership($team);
        
        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', 'Team deleted successfully!');
    }

    public function members(UserTeam $team)
    {
        $this->authorizeTeamAccess($team);
        
        $team->load('users');
        $userRole = $team->getUserRole(Auth::user());
        
        return view('teams.members', compact('team', 'userRole'));
    }

    public function addMember(Request $request, UserTeam $team)
    {
        $this->authorizeTeamManagement($team);
        
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', Rule::in(array_keys(TeamUser::ROLES))],
            'permissions' => 'array',
            'permissions.*' => Rule::in(array_keys(TeamUser::PERMISSIONS)),
        ]);

        $user = User::where('email', $validated['email'])->first();
        
        if ($team->hasUser($user)) {
            return back()->withErrors(['email' => 'User is already a member of this team.']);
        }

        TeamUser::create([
            'user_team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        return back()->with('success', 'Member added successfully!');
    }

    public function removeMember(UserTeam $team, User $user)
    {
        $this->authorizeTeamManagement($team);
        
        if ($team->owner_id === $user->id) {
            return back()->withErrors(['error' => 'Cannot remove the team owner.']);
        }

        $teamUser = TeamUser::where('user_team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
            
        if ($teamUser) {
            $teamUser->delete();
        }

        return back()->with('success', 'Member removed successfully!');
    }

    public function updateMemberRole(Request $request, UserTeam $team, User $user)
    {
        $this->authorizeTeamManagement($team);
        
        $validated = $request->validate([
            'role' => ['required', Rule::in(array_keys(TeamUser::ROLES))],
            'permissions' => 'array',
            'permissions.*' => Rule::in(array_keys(TeamUser::PERMISSIONS)),
        ]);

        if ($team->owner_id === $user->id && $validated['role'] !== 'owner') {
            return back()->withErrors(['error' => 'Cannot change the owner role.']);
        }

        $teamUser = TeamUser::where('user_team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();
            
        if ($teamUser) {
            $teamUser->update([
                'role' => $validated['role'],
                'permissions' => $validated['permissions'] ?? [],
            ]);
        }

        return back()->with('success', 'Member role updated successfully!');
    }

    private function authorizeTeamAccess(UserTeam $team)
    {
        $user = Auth::user();
        
        if (!$team->hasUser($user) && $team->owner_id !== $user->id) {
            abort(403, 'You do not have access to this team.');
        }
    }

    private function authorizeTeamManagement(UserTeam $team)
    {
        $user = Auth::user();
        
        if (!$team->isTeamOwner($user) && !$team->hasUser($user)) {
            abort(403, 'You do not have access to this team.');
        }
        
        if (!$team->isTeamOwner($user) && !$user->hasTeamPermission($team, 'manage_team')) {
            abort(403, 'You do not have permission to manage this team.');
        }
    }

    private function authorizeTeamOwnership(UserTeam $team)
    {
        if (!$team->isTeamOwner(Auth::user())) {
            abort(403, 'Only the team owner can perform this action.');
        }
    }
}
