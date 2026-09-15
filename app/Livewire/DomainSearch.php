<?php

namespace App\Livewire;

use App\Models\OvhProduct;
use Livewire\Component;

class DomainSearch extends Component
{
    public string $search = '';

    public function render()
    {
        $query = OvhProduct::where('category', 'DOMAINS')
            ->where('is_active', true)
            ->whereNotNull('service_id')
            ->with('service');

        $term = trim($this->search);
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('plan_code', 'like', '%' . $term . '%')
                  ->orWhere('invoice_name', 'like', '%' . $term . '%');
            });
        }

        $domains = $query->orderBy('price_monthly')->limit(100)->get();

        return view('livewire.domain-search', [
            'domains' => $domains,
            'rate' => \App\Models\ExchangeRate::getUsdToInrRate(),
        ])->layout('components.layouts.believoo', [
            'title' => 'Register a Domain - Believoo',
            'description' => 'Search and register domains through OVH with instant provisioning.',
            'keywords' => 'domain, domain registration, OVH, .com, .in',
            'settings' => \App\Models\Setting::pluck('value', 'key'),
        ]);
    }
}
