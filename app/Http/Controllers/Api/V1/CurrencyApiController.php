<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyApiController extends Controller
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get available currencies
     * GET /api/v1/currencies
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'currencies' => $this->currencyService->getAvailableCurrencies(),
                'current' => $this->currencyService->getUserCurrency(),
            ],
        ]);
    }

    /**
     * Get current exchange rates
     * GET /api/v1/currencies/rates
     */
    public function rates(): JsonResponse
    {
        $rates = \App\Models\ExchangeRate::where('base_currency', 'USD')
            ->latest('fetched_at')
            ->get()
            ->map(fn($rate) => [
                'currency' => $rate->target_currency,
                'rate' => $rate->rate,
                'fetched_at' => $rate->fetched_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'base' => 'USD',
                'rates' => $rates,
            ],
        ]);
    }

    /**
     * Convert price
     * POST /api/v1/currencies/convert
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
        ]);

        $result = match ($validated['from']) {
            'USD' => $this->currencyService->convertFromUsd($validated['amount'], $validated['to']),
            default => \App\Models\ExchangeRate::convertFromUsd(
                \App\Models\ExchangeRate::convertToUsd($validated['amount'], $validated['from']),
                $validated['to']
            ),
        };

        return response()->json([
            'success' => true,
            'data' => [
                'original_amount' => $validated['amount'],
                'original_currency' => $validated['from'],
                'converted_amount' => $result,
                'target_currency' => $validated['to'],
                'formatted' => $this->currencyService->formatPrice($result, $validated['to']),
            ],
        ]);
    }

    /**
     * Set user currency preference
     * POST /api/v1/currencies/set
     */
    public function setCurrency(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'currency' => 'required|string|size:3|in:USD,INR',
        ]);

        try {
            $this->currencyService->setUserCurrency($validated['currency']);

            return response()->json([
                'success' => true,
                'message' => 'Currency preference updated',
                'data' => [
                    'currency' => $validated['currency'],
                    'symbol' => CurrencyService::SUPPORTED_CURRENCIES[$validated['currency']]['symbol'],
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get payment gateway for currency
     * GET /api/v1/currencies/payment-gateway
     */
    public function paymentGateway(Request $request): JsonResponse
    {
        $currency = $request->input('currency', $this->currencyService->getUserCurrency());
        
        $gateway = $this->currencyService->getPaymentGateway($currency);
        $availableGateways = $this->currencyService->getSupportedGateways()[$currency] ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => $currency,
                'recommended_gateway' => $gateway,
                'available_gateways' => $availableGateways,
            ],
        ]);
    }
}
