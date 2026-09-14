<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AgentAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('support_agent_id')) {
            $base = $request->getHost() === 'agent.believoo.com' ? '' : '/agent';
            return redirect($base . '/login');
        }
        return $next($request);
    }
}
