<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;

class BlockIp
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        if (BlockedIp::where('ip_address', $ip)->exists()) {
            abort(403, 'Your IP has been blocked.');
        }
        return $next($request);
    }
}
