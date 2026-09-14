<?php

namespace App\Http\Middleware;

use App\Services\CurrencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CurrencyMiddleware
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if currency is specified in query parameter
        if ($request->has('currency')) {
            $currencyParam = strtoupper($request->get('currency'));
            if ($this->currencyService->isSupported($currencyParam)) {
                $this->currencyService->setUserCurrency($currencyParam);
            }
        }

        // Detect and set currency for the request
        $currency = $this->currencyService->getUserCurrency();
        
        // Share currency with all views
        view()->share('currentCurrency', $currency);
        view()->share('currencySymbol', CurrencyService::SUPPORTED_CURRENCIES[$currency]['symbol'] ?? '$');
        view()->share('availableCurrencies', $this->currencyService->getAvailableCurrencies());

        return $next($request);
    }
}
