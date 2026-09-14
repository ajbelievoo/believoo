<?php

namespace App\Livewire;

use App\Models\Service;
use Livewire\Component;

class VpsHosting extends Component
{
    public function render()
    {
        // Get all VPS hosting services
        $vpsServices = Service::where('category', 'Hosting')
            ->where('is_active', true)
            ->where('slug', 'like', 'vps-%')
            ->orderBy('price')
            ->get();

        // If no services found, show default plans
        if ($vpsServices->isEmpty()) {
            $vpsServices = $this->getDefaultPlans();
        }

        return view('livewire.vps-hosting', [
            'vpsPlans' => $vpsServices,
        ])->layout('components.layouts.believoo');
    }

    private function getDefaultPlans()
    {
        return collect([
            (object)[
                'id' => 1,
                'title' => 'VPS-1',
                'slug' => 'vps-1',
                'description' => 'Entry level VPS',
                'price' => 14.99,
                'features' => [
                    ['feature' => '4 vCores'],
                    ['feature' => '8 GB RAM'],
                    ['feature' => '75 GB SSD'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '400 Mbps Bandwidth'],
                ],
            ],
            (object)[
                'id' => 2,
                'title' => 'VPS-2',
                'slug' => 'vps-2',
                'description' => 'Best for applications',
                'price' => 21.99,
                'features' => [
                    ['feature' => '6 vCores'],
                    ['feature' => '12 GB RAM'],
                    ['feature' => '100 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '1 Gbps Bandwidth'],
                ],
            ],
            (object)[
                'id' => 3,
                'title' => 'VPS-3',
                'slug' => 'vps-3',
                'description' => 'High performance VPS',
                'price' => 41.99,
                'features' => [
                    ['feature' => '8 vCores'],
                    ['feature' => '24 GB RAM'],
                    ['feature' => '200 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '1.5 Gbps Bandwidth'],
                ],
            ],
            (object)[
                'id' => 4,
                'title' => 'VPS-4',
                'slug' => 'vps-4',
                'description' => 'Powerful VPS',
                'price' => 71.99,
                'features' => [
                    ['feature' => '12 vCores'],
                    ['feature' => '48 GB RAM'],
                    ['feature' => '300 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '2 Gbps Bandwidth'],
                ],
            ],
            (object)[
                'id' => 5,
                'title' => 'VPS-5',
                'slug' => 'vps-5',
                'description' => 'Enterprise VPS',
                'price' => 101.99,
                'features' => [
                    ['feature' => '16 vCores'],
                    ['feature' => '64 GB RAM'],
                    ['feature' => '350 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '2.5 Gbps Bandwidth'],
                ],
            ],
            (object)[
                'id' => 6,
                'title' => 'VPS-6',
                'slug' => 'vps-6',
                'description' => 'Ultimate VPS',
                'price' => 131.99,
                'features' => [
                    ['feature' => '24 vCores'],
                    ['feature' => '96 GB RAM'],
                    ['feature' => '400 GB NVMe'],
                    ['feature' => 'Daily Backup (24h)'],
                    ['feature' => 'Unlimited Traffic'],
                    ['feature' => '3 Gbps Bandwidth'],
                ],
            ],
        ]);
    }
}
