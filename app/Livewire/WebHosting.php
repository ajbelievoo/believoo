<?php

namespace App\Livewire;

use App\Models\Service;
use Livewire\Component;

class WebHosting extends Component
{
    public $services = [];
    
    public function mount()
    {
        // Get web hosting services from database
        $this->services = Service::where('category', 'hosting')
            ->where('title', 'like', '%Web%')
            ->where('is_active', true)
            ->get();
        
        // If no services found, create them from defaults
        if ($this->services->isEmpty()) {
            $this->createDefaultServices();
            $this->services = Service::where('category', 'hosting')
                ->where('title', 'like', '%Web%')
                ->where('is_active', true)
                ->get();
        }
    }
    
    private function createDefaultServices()
    {
        $defaultPlans = [
            [
                'title' => 'Web Hosting - Starter',
                'slug' => 'web-hosting-starter',
                'description' => 'Perfect for personal websites',
                'price' => 3,
                'pricing_tiers' => [
                    ['name' => 'Starter', 'price' => 3, 'features' => ['1 Website', '10 GB SSD Storage', 'Unmetered Bandwidth', 'Free SSL Certificate', 'cPanel Control Panel', '24/7 Support']]
                ],
                'billing_cycles' => [
                    ['months' => 1, 'label' => 'Monthly', 'discount_percent' => 0, 'recommended' => false],
                    ['months' => 3, 'label' => '3 Months', 'discount_percent' => 5, 'recommended' => false],
                    ['months' => 6, 'label' => '6 Months', 'discount_percent' => 10, 'recommended' => true],
                    ['months' => 12, 'label' => 'Yearly', 'discount_percent' => 20, 'recommended' => true],
                ],
                'features' => [
                    ['feature' => '1 Website'],
                    ['feature' => '10 GB SSD Storage'],
                    ['feature' => 'Unmetered Bandwidth'],
                    ['feature' => 'Free SSL Certificate'],
                    ['feature' => 'cPanel Control Panel'],
                    ['feature' => '24/7 Support'],
                ],
            ],
            [
                'title' => 'Web Hosting - Business',
                'slug' => 'web-hosting-business',
                'description' => 'Best for growing businesses',
                'price' => 6,
                'pricing_tiers' => [
                    ['name' => 'Business', 'price' => 6, 'features' => ['10 Websites', '50 GB SSD Storage', 'Unmetered Bandwidth', 'Free SSL Certificate', 'cPanel Control Panel', 'Free Daily Backup']]
                ],
                'billing_cycles' => [
                    ['months' => 1, 'label' => 'Monthly', 'discount_percent' => 0, 'recommended' => false],
                    ['months' => 3, 'label' => '3 Months', 'discount_percent' => 5, 'recommended' => false],
                    ['months' => 6, 'label' => '6 Months', 'discount_percent' => 15, 'recommended' => true],
                    ['months' => 12, 'label' => 'Yearly', 'discount_percent' => 25, 'recommended' => true],
                ],
                'features' => [
                    ['feature' => '10 Websites'],
                    ['feature' => '50 GB SSD Storage'],
                    ['feature' => 'Unmetered Bandwidth'],
                    ['feature' => 'Free SSL Certificate'],
                    ['feature' => 'cPanel Control Panel'],
                    ['feature' => 'Free Daily Backup'],
                ],
            ],
            [
                'title' => 'Web Hosting - Pro',
                'slug' => 'web-hosting-pro',
                'description' => 'For high-traffic websites',
                'price' => 12,
                'pricing_tiers' => [
                    ['name' => 'Pro', 'price' => 12, 'features' => ['Unlimited Websites', '100 GB NVMe Storage', 'Unmetered Bandwidth', 'Free SSL + CDN', 'cPanel + Softaculous', 'Priority Support']]
                ],
                'billing_cycles' => [
                    ['months' => 1, 'label' => 'Monthly', 'discount_percent' => 0, 'recommended' => false],
                    ['months' => 3, 'label' => '3 Months', 'discount_percent' => 10, 'recommended' => false],
                    ['months' => 6, 'label' => '6 Months', 'discount_percent' => 20, 'recommended' => true],
                    ['months' => 12, 'label' => 'Yearly', 'discount_percent' => 30, 'recommended' => true],
                ],
                'features' => [
                    ['feature' => 'Unlimited Websites'],
                    ['feature' => '100 GB NVMe Storage'],
                    ['feature' => 'Unmetered Bandwidth'],
                    ['feature' => 'Free SSL + CDN'],
                    ['feature' => 'cPanel + Softaculous'],
                    ['feature' => 'Priority Support'],
                ],
            ],
        ];
        
        foreach ($defaultPlans as $plan) {
            Service::create(array_merge($plan, [
                'category' => 'hosting',
                'icon' => 'fas fa-globe',
                'is_active' => true,
            ]));
        }
    }
    
    private function getDefaultPlans()
    {
        return collect([
            [
                'id' => 1,
                'title' => 'Web Hosting - Starter',
                'slug' => 'web-hosting-starter',
                'description' => 'Perfect for personal websites',
                'price' => 3,
                'pricing_tiers' => [
                    ['name' => 'Starter', 'price' => 3, 'features' => ['1 Website', '10 GB SSD Storage', 'Unmetered Bandwidth', 'Free SSL Certificate', 'cPanel Control Panel', '24/7 Support']]
                ],
                'features' => [
                    ['feature' => '1 Website'],
                    ['feature' => '10 GB SSD Storage'],
                    ['feature' => 'Unmetered Bandwidth'],
                    ['feature' => 'Free SSL Certificate'],
                    ['feature' => 'cPanel Control Panel'],
                    ['feature' => '24/7 Support'],
                ],
                'is_active' => true,
            ],
            [
                'id' => 2,
                'title' => 'Web Hosting - Business',
                'slug' => 'web-hosting-business',
                'description' => 'Best for growing businesses',
                'price' => 6,
                'pricing_tiers' => [
                    ['name' => 'Business', 'price' => 6, 'features' => ['10 Websites', '50 GB SSD Storage', 'Unmetered Bandwidth', 'Free SSL Certificate', 'cPanel Control Panel', 'Free Daily Backup']]
                ],
                'features' => [
                    ['feature' => '10 Websites'],
                    ['feature' => '50 GB SSD Storage'],
                    ['feature' => 'Unmetered Bandwidth'],
                    ['feature' => 'Free SSL Certificate'],
                    ['feature' => 'cPanel Control Panel'],
                    ['feature' => 'Free Daily Backup'],
                ],
                'is_active' => true,
            ],
            [
                'id' => 3,
                'title' => 'Web Hosting - Pro',
                'slug' => 'web-hosting-pro',
                'description' => 'For high-traffic websites',
                'price' => 12,
                'pricing_tiers' => [
                    ['name' => 'Pro', 'price' => 12, 'features' => ['Unlimited Websites', '100 GB NVMe Storage', 'Unmetered Bandwidth', 'Free SSL + CDN', 'cPanel + Softaculous', 'Priority Support']]
                ],
                'features' => [
                    ['feature' => 'Unlimited Websites'],
                    ['feature' => '100 GB NVMe Storage'],
                    ['feature' => 'Unmetered Bandwidth'],
                    ['feature' => 'Free SSL + CDN'],
                    ['feature' => 'cPanel + Softaculous'],
                    ['feature' => 'Priority Support'],
                ],
                'is_active' => true,
            ],
        ]);
    }
    
    public function render()
    {
        return view('livewire.web-hosting')->layout('components.layouts.believoo');
    }
}
