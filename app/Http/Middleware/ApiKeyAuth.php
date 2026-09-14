<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for API key
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        
        if ($apiKey) {
            $key = ApiKey::where('key', $apiKey)->first();
            
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
            
            // Attach API key user to request
            $request->setUserResolver(function () use ($key) {
                return $key->user;
            });
            
            $key->recordUsage();
        }

        return $next($request);
    }
}
