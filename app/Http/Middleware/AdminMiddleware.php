<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Check if user is admin (is_admin column or role check)
        $user = auth()->user();
        $isFirstUser = \App\Models\User::orderBy('id')->first()?->id === $user->id;
        $isAdmin = $user->is_admin ?? false;
        if (!$isAdmin && !$isFirstUser) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
