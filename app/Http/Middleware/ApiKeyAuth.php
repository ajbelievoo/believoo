<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next, string ...$scopeParts): Response
    {
        $scope = implode(':', $scopeParts);

        // Reuse already resolved API key if this middleware is stacked
        // (e.g. outer auth group + inner scoped route).
        $key = $request->attributes->get('api_key');
        $alreadyResolved = $key instanceof ApiKey;

        if (!$alreadyResolved) {
            $token = $this->extractToken($request);

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'error' => 'API key required',
                ], 401);
            }

            $key = ApiKey::findByToken($token);

            if (!$key) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid API key',
                ], 401);
            }

            if (!$key->isValid()) {
                return response()->json([
                    'success' => false,
                    'error' => 'API key expired or inactive',
                ], 401);
            }

            if (!$key->isIpAllowed($request->ip())) {
                return response()->json([
                    'success' => false,
                    'error' => 'IP address not allowed',
                ], 403);
            }

            $limiterKey = 'api-key:' . $key->id;
            if (RateLimiter::tooManyAttempts($limiterKey, $key->rate_limit)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Rate limit exceeded. Try again later.',
                ], 429);
            }

            RateLimiter::hit($limiterKey, 60);

            $user = $key->user;

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'error' => 'API key has no associated user',
                ], 401);
            }

            // Make this the active auth guard for the request.
            Auth::shouldUse('api');
            Auth::guard('api')->setUser($user);

            $request->attributes->set('api_key', $key);
            $key->recordUsage();
        }

        if ($scope && !$key->hasScope($scope)) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient scope: ' . $scope,
            ], 403);
        }

        return $next($request);
    }

    protected function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        if ($request->hasHeader('X-API-Key')) {
            return $request->header('X-API-Key');
        }

        return $request->input('api_key');
    }
}
