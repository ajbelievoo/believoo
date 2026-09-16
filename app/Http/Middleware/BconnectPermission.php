<?php

namespace App\Http\Middleware;

use App\Services\BconnectPermissionService;
use Closure;
use Illuminate\Http\Request;

class BconnectPermission
{
    public function handle(Request $request, Closure $next, $permissions)
    {
        $member = $request->input('bconnect_member');
        if (!$member) {
            abort(403, 'Workspace member not resolved.');
        }

        $list = preg_split('/[,|]/', $permissions, -1, PREG_SPLIT_NO_EMPTY);
        $list = array_map('trim', $list);

        if (!BconnectPermissionService::any($member, $list)) {
            abort(403, 'Access denied. Required permission: ' . implode(' or ', $list));
        }

        return $next($request);
    }
}
