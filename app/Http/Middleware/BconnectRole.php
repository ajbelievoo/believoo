<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BconnectRole
{
    protected $role;

    public function handle(Request $request, Closure $next, $role)
    {
        $userRole = $request->input('bconnect_role');
        $allowed = explode('|', $role);

        // Super admins and global admins bypass role checks.
        if ($userRole === 'super_admin' || $userRole === 'admin') {
            return $next($request);
        }

        if (!in_array($userRole, $allowed)) {
            abort(403, 'Access denied. Required role: ' . $role);
        }

        return $next($request);
    }
}
