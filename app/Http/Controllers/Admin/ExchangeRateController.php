<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRate;
use App\Services\CurrencyService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function index()
    {
        $exchangeRate = ExchangeRate::where('base_currency', 'USD')
            ->where('target_currency', 'INR')
            ->first();
        
        // If no rate exists, create one with default
        if (!$exchangeRate) {
            $exchangeRate = ExchangeRate::create([
                'base_currency' => 'USD',
                'target_currency' => 'INR',
                'rate' => 83.00,
                'source' => 'manual',
                'fetched_at' => now(),
            ]);
        }
        
        return view('admin.exchange-rates.index', compact('exchangeRate'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'rate' => 'required|numeric|min:1|max:500',
        ]);

        $exchangeRate = ExchangeRate::where('base_currency', 'USD')
            ->where('target_currency', 'INR')
            ->first();

        if ($exchangeRate) {
            $exchangeRate->update([
                'rate' => $validated['rate'],
                'source' => 'manual',
                'fetched_at' => now(),
            ]);
        } else {
            ExchangeRate::create([
                'base_currency' => 'USD',
                'target_currency' => 'INR',
                'rate' => $validated['rate'],
                'source' => 'manual',
                'fetched_at' => now(),
            ]);
        }

        // Clear any cached rates
        cache()->forget('exchange_rate_usd_to_inr');

        return redirect()->back()
            ->with('success', 'Exchange rate updated successfully! 1 USD = ₹' . number_format($validated['rate'], 2) . ' INR');
    }

    public function fetchLiveRate()
    {
        try {
            $service = new CurrencyService();
            $success = $service->fetchLatestRates();

            if ($success) {
                $rate = ExchangeRate::getUsdToInrRate();
                return redirect()->back()
                    ->with('success', 'Live exchange rate fetched successfully! 1 USD = ₹' . number_format($rate, 2) . ' INR');
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to fetch live exchange rate. Please try again or update manually.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error fetching exchange rate: ' . $e->getMessage());
        }
    }
}
