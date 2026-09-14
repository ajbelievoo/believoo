<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BconnectRole
{
    protected $role;

    public function handle(Request $request, Closure $next, $role)
    {
        if (!in_array($request->input('bconnect_role'), explode('|', $role))) {
            abort(403, 'Access denied. Required role: ' . $role);
        }
        return $next($request);
    }
}
