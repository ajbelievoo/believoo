<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            return $next($request);
        }

        // Skip 2FA setup routes
        if ($request->routeIs('two-factor.*')) {
            return $next($request);
        }

        if (!$user->twoFactorEnabled()) {
            $force2fa = Setting::where('key', 'force_2fa')->value('value') === '1';
            if ($force2fa) {
                return redirect()->route('two-factor.setup');
            }
        }

        return $next($request);
    }
}
