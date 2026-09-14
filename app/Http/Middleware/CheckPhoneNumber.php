<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPhoneNumber
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip for admin users and if already on the complete-profile page
        if ($user && !$user->isAdmin() && empty($user->phone)) {
            $excludedRoutes = [
                'profile.complete',
                'profile.complete.store',
                'logout',
                'verification.notice',
                'verification.verify',
                'verification.send',
            ];

            if (!in_array($request->route()->getName(), $excludedRoutes)) {
                return redirect()->route('profile.complete');
            }
        }

        return $next($request);
    }
}
