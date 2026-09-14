<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Bconnect\AuditLog;

class BconnectAudit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->input('bconnect_company_id') && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            AuditLog::create([
                'company_id' => $request->input('bconnect_company_id'),
                'member_id' => optional($request->input('bconnect_member'))->id ?: 0,
                'action' => $request->route()?->getName() ?: 'unknown',
                'ip_address' => $request->ip(),
                'details' => json_encode(['url' => $request->fullUrl(), 'method' => $request->method()]),
            ]);
        }

        return $response;
    }
}
