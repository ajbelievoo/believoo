<?php

namespace App\Livewire;

use App\Models\VpsPlan;
use App\Models\Service;
use Livewire\Component;

class HostingLanding extends Component
{
    public $vpsPlans = [];
    public $webHostingPlans = [];

    public function mount()
    {
        // Get VPS plans for display
        $this->vpsPlans = VpsPlan::where('is_active', true)
            ->orderBy('base_price_usd')
            ->take(3)
            ->get();

        // Get web hosting plans
        $this->webHostingPlans = Service::where('category', 'hosting')
            ->where('title', 'like', '%Web%')
            ->where('is_active', true)
            ->take(3)
            ->get();
    }

    public function render()
    {
        return view('livewire.hosting-landing')->layout('components.layouts.believoo');
    }
}
