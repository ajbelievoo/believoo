<?php

namespace Database\Seeders;

use App\Models\ProxmoxNode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DatacenterNodeSeeder extends Seeder
{
    /**
     * Seed the datacenter nodes.
     * Singapore = Active (User's current server)
     * India, EU, US = Coming Soon
     */
    public function run(): void
    {
        $nodes = [
            // ACTIVE - User's current Singapore server
            [
                'name' => 'sg1-proxmox',
                'display_name' => 'Singapore DC-1',
                'hostname' => 'sg1.believoo.com',
                'port' => 8006,
                'country_code' => 'SG',
                'city' => 'Singapore',
                'region' => 'Asia',
                'latitude' => 1.3521,
                'longitude' => 103.8198,
                'status' => 'active',
                'is_default' => true,
                'max_vms' => 50,
                'current_vms' => 0,
                'total_cpu_cores' => 32,
                'total_memory_bytes' => 64 * 1024 * 1024 * 1024, // 64GB
                'total_disk_bytes' => 2000 * 1024 * 1024 * 1024, // 2TB
                'total_bandwidth_bytes' => 10 * 1024 * 1024 * 1024, // 10Gbps
                'flag_emoji' => '🇸🇬',
                'latency_hint' => '<5ms from Singapore',
                'provider_name' => 'OVH',
            ],
            // COMING SOON - India
            [
                'name' => 'in1-proxmox',
                'display_name' => 'Mumbai DC-1',
                'hostname' => 'in1.believoo.com',
                'port' => 8006,
                'country_code' => 'IN',
                'city' => 'Mumbai',
                'region' => 'Asia',
                'latitude' => 19.0760,
                'longitude' => 72.8777,
                'status' => 'coming_soon',
                'is_default' => false,
                'max_vms' => 100,
                'current_vms' => 0,
                'total_cpu_cores' => 64,
                'total_memory_bytes' => 128 * 1024 * 1024 * 1024, // 128GB
                'total_disk_bytes' => 4000 * 1024 * 1024 * 1024, // 4TB
                'total_bandwidth_bytes' => 10 * 1024 * 1024 * 1024,
                'flag_emoji' => '🇮🇳',
                'latency_hint' => '~15ms from Mumbai',
                'provider_name' => 'AWS',
            ],
            // COMING SOON - Europe
            [
                'name' => 'de1-proxmox',
                'display_name' => 'Frankfurt DC-1',
                'hostname' => 'de1.believoo.com',
                'port' => 8006,
                'country_code' => 'DE',
                'city' => 'Frankfurt',
                'region' => 'Europe',
                'latitude' => 50.1109,
                'longitude' => 8.6821,
                'status' => 'coming_soon',
                'is_default' => false,
                'max_vms' => 100,
                'current_vms' => 0,
                'total_cpu_cores' => 64,
                'total_memory_bytes' => 128 * 1024 * 1024 * 1024,
                'total_disk_bytes' => 4000 * 1024 * 1024 * 1024,
                'total_bandwidth_bytes' => 10 * 1024 * 1024 * 1024,
                'flag_emoji' => '🇩🇪',
                'latency_hint' => '~25ms from Berlin',
                'provider_name' => 'Hetzner',
            ],
            // COMING SOON - US
            [
                'name' => 'us1-proxmox',
                'display_name' => 'New York DC-1',
                'hostname' => 'us1.believoo.com',
                'port' => 8006,
                'country_code' => 'US',
                'city' => 'New York',
                'region' => 'North America',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'status' => 'coming_soon',
                'is_default' => false,
                'max_vms' => 100,
                'current_vms' => 0,
                'total_cpu_cores' => 64,
                'total_memory_bytes' => 128 * 1024 * 1024 * 1024,
                'total_disk_bytes' => 4000 * 1024 * 1024 * 1024,
                'total_bandwidth_bytes' => 10 * 1024 * 1024 * 1024,
                'flag_emoji' => '🇺🇸',
                'latency_hint' => '~20ms from NYC',
                'provider_name' => 'DigitalOcean',
            ],
        ];

        foreach ($nodes as $nodeData) {
            ProxmoxNode::updateOrCreate(
                ['name' => $nodeData['name']],
                $nodeData
            );
        }

        Log::info('Datacenter nodes seeded successfully', [
            'active' => ProxmoxNode::where('status', 'active')->count(),
            'coming_soon' => ProxmoxNode::where('status', 'coming_soon')->count(),
        ]);
    }
}
