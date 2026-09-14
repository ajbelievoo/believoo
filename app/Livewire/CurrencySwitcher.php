<?php

namespace App\Livewire;

use App\Services\CurrencyService;
use Livewire\Component;

class CurrencySwitcher extends Component
{
    public string $currentCurrency = 'USD';
    public array $availableCurrencies = [];

    protected CurrencyService $currencyService;

    public function boot(CurrencyService $currencyService): void
    {
        $this->currencyService = $currencyService;
    }

    public function mount(): void
    {
        $this->currentCurrency = $this->currencyService->getUserCurrency();
        $this->availableCurrencies = $this->currencyService->getAvailableCurrencies();
    }

    /**
     * Switch currency
     */
    public function switchCurrency(string $currency): void
    {
        if (!$this->currencyService->isSupported($currency)) {
            $this->dispatch('currency-error', message: 'Invalid currency selected');
            return;
        }

        $this->currencyService->setUserCurrency($currency);
        $this->currentCurrency = $currency;

        // Dispatch event to refresh components
        $this->dispatch('currency-changed', currency: $currency);

        // Show success message
        $symbol = $this->availableCurrencies[$currency]['symbol'] ?? '$';
        $this->dispatch('notify', 
            type: 'success', 
            message: "Currency switched to {$currency} ({$symbol})"
        );
    }

    /**
     * Get display data for current currency
     */
    public function getCurrencyDataProperty(): array
    {
        return [
            'code' => $this->currentCurrency,
            'symbol' => $this->availableCurrencies[$this->currentCurrency]['symbol'] ?? '$',
            'flag' => $this->availableCurrencies[$this->currentCurrency]['flag'] ?? '🌐',
            'name' => $this->availableCurrencies[$this->currentCurrency]['name'] ?? 'Unknown',
        ];
    }

    public function render()
    {
        return view('livewire.currency-switcher');
    }
}
