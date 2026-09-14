<?php

namespace App\Livewire;

use App\Models\Service;
use App\Models\StreamingPlan;
use Livewire\Component;

class ServiceIndex extends Component
{
    public function render()
    {
        try {
            $streamingPlans = StreamingPlan::standalone()->active()->orderBy('sort_order')->get();
        } catch (\Exception $e) {
            $streamingPlans = collect();
        }

        return view('livewire.service-index', [
            'services' => Service::where('is_active', true)->whereNotIn('slug', [
                'managed-vps-cloud',
                'vps-1', 'vps-2', 'vps-3', 'vps-4', 'vps-5', 'vps-6',
                'web-hosting-starter', 'web-hosting-business', 'web-hosting-pro',
                'streaming-addon',
            ])->get(),
            'streamingPlans' => $streamingPlans,
        ])->layout('components.layouts.believoo', [
            'title' => 'IT Services & Cloud Solutions in India - Believoo',
            'description' => 'Explore Believoo services: VPS hosting, web hosting, live streaming, domain registration, server management, and custom software development in India.',
            'keywords' => 'IT services, cloud solutions, VPS hosting, web hosting, live streaming, domain, software development, India',
            'settings' => \App\Models\Setting::pluck('value', 'key')
        ]);
    }
}
