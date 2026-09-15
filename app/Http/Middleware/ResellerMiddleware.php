<?php

namespace App\Http\Middleware;

use App\Models\Reseller;
use Closure;
use Illuminate\Http\Request;

class ResellerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();

        if ($host && $host !== parse_url(config('app.url'), PHP_URL_HOST)) {
            $reseller = Reseller::where('custom_domain', $host)
                ->where('is_active', true)
                ->first();

            if ($reseller) {
                app()->instance('current_reseller', $reseller);
                view()->share('reseller', $reseller);
            }
        }

        return $next($request);
    }
}
