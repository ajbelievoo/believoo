<?php

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminActions
{
    /**
     * Handle an incoming request.
     *
     * Logs GET/POST/PUT/PATCH/DELETE actions in the admin area.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if (!$user || !$user->is_admin) {
            return $response;
        }

        $method = strtoupper($request->method());
        $action = match ($method) {
            'POST' => 'admin_action_post',
            'PUT', 'PATCH' => 'admin_action_update',
            'DELETE' => 'admin_action_delete',
            default => 'admin_action_view',
        };

        // Avoid logging 2FA setup views and sensitive pages too aggressively
        if (in_array($request->route()?->getName(), ['two-factor.setup', 'two-factor.recovery', 'two-factor.challenge'])) {
            return $response;
        }

        AuditService::log(
            $action,
            null,
            null,
            [
                'method' => $method,
                'route' => $request->route()?->getName(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
            ],
            "Admin {$method} on " . $request->path(),
            null,
            $request
        );

        return $response;
    }
}
