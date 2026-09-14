<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserTeam;
use Illuminate\Support\Facades\Auth;

class TeamPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'Authentication required.');
        }

        // Get team from route parameter
        $team = $request->route('team');
        
        if (!$team) {
            abort(404, 'Team not found.');
        }

        // Ensure we have a UserTeam model instance
        if (!$team instanceof UserTeam) {
            $team = UserTeam::findOrFail($team);
        }

        // Check if user has access to the team
        if (!$team->hasUser($user) && $team->owner_id !== $user->id) {
            abort(403, 'You do not have access to this team.');
        }

        // Check specific permission
        if (!$user->hasTeamPermission($team, $permission)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        // Add team to request for easy access in controllers
        $request->merge(['current_team' => $team]);

        return $next($request);
    }
}
