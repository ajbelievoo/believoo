<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use App\Models\UserTeam;
use App\Models\TeamUser;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class TeamInvitationController extends Controller
{
    public function __construct()
    {
        // No auth middleware needed for accepting invitations
    }

    public function store(Request $request, UserTeam $team)
    {
        $this->authorizeTeamManagement($team);
        
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', Rule::in(array_keys(TeamUser::ROLES))],
            'permissions' => 'array',
            'permissions.*' => Rule::in(array_keys(TeamUser::PERMISSIONS)),
        ]);

        $user = User::where('email', $validated['email'])->first();
        
        // Check if user is already a team member
        if ($team->hasUser($user)) {
            return back()->withErrors(['email' => 'User is already a member of this team.']);
        }

        // Check if there's already a pending invitation
        $existingInvitation = TeamInvitation::where('user_team_id', $team->id)
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
            
        if ($existingInvitation) {
            return back()->withErrors(['email' => 'An invitation has already been sent to this email.']);
        }

        // Create invitation
        $invitation = TeamInvitation::create([
            'user_team_id' => $team->id,
            'invited_by' => Auth::id(),
            'email' => $validated['email'],
            'role' => $validated['role'],
            'permissions' => $validated['permissions'] ?? [],
        ]);

        // Send email notification
        try {
            Notification::route('mail', $validated['email'])->notify(new TeamInvitationNotification($invitation));
        } catch (\Exception $e) {
            \Log::error('Failed to send team invitation email: ' . $e->getMessage());
        }

        return back()->with('success', 'Invitation sent successfully!');
    }

    public function accept($token)
    {
        $invitation = TeamInvitation::where('token', $token)
            ->with(['team', 'inviter'])
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return view('teams.invitation-expired', compact('invitation'));
        }

        if ($invitation->status !== 'pending') {
            return view('teams.invitation-already-processed', compact('invitation'));
        }

        // Find or create user
        $user = User::where('email', $invitation->email)->first();
        
        if (!$user) {
            // User doesn't exist, redirect to register with pre-filled email
            return redirect()->route('register', ['email' => $invitation->email, 'invitation' => $token]);
        }

        // Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('login', ['invitation' => $token]);
        }

        // Check if logged-in user matches invitation email
        if (Auth::user()->email !== $invitation->email) {
            Auth::logout();
            return redirect()->route('login', ['invitation' => $token])
                ->with('error', 'Please login with the email address that received the invitation.');
        }

        // Accept invitation and add user to team
        $invitation->accept();

        TeamUser::create([
            'user_team_id' => $invitation->user_team_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
            'permissions' => $invitation->permissions,
        ]);

        return redirect()->route('teams.show', $invitation->team)
            ->with('success', 'You have successfully joined the team!');
    }

    public function decline($token)
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isExpired()) {
            return view('teams.invitation-expired', compact('invitation'));
        }

        if ($invitation->status !== 'pending') {
            return view('teams.invitation-already-processed', compact('invitation'));
        }

        $invitation->decline();

        return view('teams.invitation-declined', compact('invitation'));
    }

    public function index(UserTeam $team)
    {
        $this->authorizeTeamManagement($team);
        
        $invitations = $team->invitations()->with('inviter')->latest()->get();
        
        return view('teams.invitations', compact('team', 'invitations'));
    }

    public function destroy(UserTeam $team, TeamInvitation $invitation)
    {
        $this->authorizeTeamManagement($team);

        if ($invitation->user_team_id !== $team->id) {
            abort(403);
        }

        $invitation->delete();

        return back()->with('success', 'Invitation revoked successfully!');
    }

    public function resend(UserTeam $team, TeamInvitation $invitation)
    {
        $this->authorizeTeamManagement($team);

        if ($invitation->user_team_id !== $team->id) {
            abort(403);
        }

        if ($invitation->status !== 'pending') {
            return back()->withErrors(['error' => 'This invitation has already been processed.']);
        }

        if ($invitation->isExpired()) {
            // Regenerate token and extend expiry
            $invitation->token = \Illuminate\Support\Str::random(60);
            $invitation->expires_at = now()->addDays(7);
            $invitation->save();
        }

        // Resend email notification
        try {
            Notification::route('mail', $invitation->email)->notify(new TeamInvitationNotification($invitation));
            return back()->with('success', 'Invitation resent successfully!');
        } catch (\Exception $e) {
            \Log::error('Failed to resend team invitation email: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to send invitation email. Please check your email settings.']);
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
}
