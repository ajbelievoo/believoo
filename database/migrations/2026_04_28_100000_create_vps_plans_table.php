<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vps_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // VPS-1, VPS-2, etc.
            $table->string('slug')->unique();
            $table->string('category')->default('vps_2026'); // vps_2026, n8n, plesk, cpanel, wordpress
            $table->string('display_name'); // Full name with specs
            $table->text('description')->nullable();
            
            // Resources
            $table->integer('cpu_cores'); // vCores
            $table->integer('memory_gb'); // RAM in GB
            $table->integer('disk_gb'); // Storage in GB
            $table->string('disk_type')->default('SSD'); // SSD, NVMe
            $table->string('bandwidth'); // e.g., "1 Gbps public"
            $table->boolean('unlimited_traffic')->default(true);
            $table->boolean('daily_backup')->default(true);
            
            // Pricing (in INR)
            $table->decimal('price_monthly', 10, 2); // Customer price (2.5x OVH)
            $table->decimal('cost_price', 10, 2)->nullable(); // OVH actual cost
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->boolean('installation_free')->default(true);
            
            // Features
            $table->json('features')->nullable(); // Array of features
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sold_out')->default(false);
            $table->boolean('is_recommended')->default(false);
            $table->integer('sort_order')->default(0);
            
            // OVH mapping
            $table->string('ovh_plan_code')->nullable();
            $table->json('ovh_config')->nullable();
            
            $table->timestamps();
            
            $table->index(['category', 'is_active', 'sort_order']);
            $table->index('slug');
        });

        // Insert OVH-style VPS plans with 2.5x pricing
        $plans = [
            // VPS 2026 - Regular VPS
            [
                'name' => 'VPS-1',
                'slug' => 'vps-1',
                'category' => 'vps_2026',
                'display_name' => 'VPS-1 - Entry Level',
                'description' => 'Perfect for small websites and development projects',
                'cpu_cores' => 4,
                'memory_gb' => 8,
                'disk_gb' => 75,
                'disk_type' => 'SSD',
                'bandwidth' => '400 Mbps public',
                'unlimited_traffic' => true,
                'daily_backup' => true,
                'price_monthly' => 1400.00, // OVH ₹560 × 2.5
                'cost_price' => 560.00,
                'setup_fee' => 0,
                'installation_free' => true,
                'features' => json_encode(['4 vCores', '8 GB RAM', '75 GB SSD', 'Daily backup', 'Unlimited traffic', '400 Mbps bandwidth']),
                'is_active' => true,
                'is_sold_out' => false,
                'is_recommended' => false,
                'sort_order' => 1,
                'ovh_plan_code' => null,
                'ovh_config' => null,
            ],
            [
                'name' => 'VPS-2',
                'slug' => 'vps-2',
                'category' => 'vps_2026',
                'display_name' => 'VPS-2 - Standard',
                'description' => 'Ideal for growing businesses and applications',
                'cpu_cores' => 6,
                'memory_gb' => 12,
                'disk_gb' => 100,
                'disk_type' => 'NVMe',
                'bandwidth' => '1 Gbps public',
                'unlimited_traffic' => true,
                'daily_backup' => true,
                'price_monthly' => 2153.00, // OVH ₹861 × 2.5
                'cost_price' => 861.00,
                'setup_fee' => 0,
                'installation_free' => true,
                'features' => json_encode(['6 vCores', '12 GB RAM', '100 GB NVMe', 'Daily backup', 'Unlimited traffic', '1 Gbps bandwidth']),
                'is_active' => true,
                'is_sold_out' => false,
                'is_recommended' => false,
                'sort_order' => 2,
                'ovh_plan_code' => null,
                'ovh_config' => null,
            ],
            [
                'name' => 'VPS-3',
                'slug' => 'vps-3',
                'category' => 'vps_2026',
                'display_name' => 'VPS-3 - Recommended',
                'description' => 'Best value for performance and resources',
                'cpu_cores' => 8,
                'memory_gb' => 24,
                'disk_gb' => 200,
                'disk_type' => 'NVMe',
                'bandwidth' => '1.5 Gbps public',
                'unlimited_traffic' => true,
                'daily_backup' => true,
                'price_monthly' => 4308.00, // OVH ₹1,723 × 2.5
                'cost_price' => 1723.00,
                'setup_fee' => 0,
                'installation_free' => true,
                'features' => json_encode(['8 vCores', '24 GB RAM', '200 GB NVMe', 'Daily backup', 'Unlimited traffic', '1.5 Gbps bandwidth']),
                'is_active' => true,
                'is_sold_out' => false,
                'is_recommended' => true,
                'sort_order' => 3,
                'ovh_plan_code' => null,
                'ovh_config' => null,
            ],
            [
                'name' => 'VPS-4',
                'slug' => 'vps-4',
                'category' => 'vps_2026',
                'display_name' => 'VPS-4 - Advanced',
                'description' => 'High performance for demanding applications',
                'cpu_cores' => 16,
                'memory_gb' => 48,
                'disk_gb' => 300,
                'disk_type' => 'NVMe',
                'bandwidth' => '2 Gbps public',
                'unlimited_traffic' => true,
                'daily_backup' => true,
                'price_monthly' => 7970.00, // OVH ₹3,188 × 2.5
                'cost_price' => 3188.00,
                'setup_fee' => 0,
                'installation_free' => true,
                'features' => json_encode(['16 vCores', '48 GB RAM', '300 GB NVMe', 'Daily backup', 'Unlimited traffic', '2 Gbps bandwidth']),
                'is_active' => true,
                'is_sold_out' => true, // Sold out like OVH
                'is_recommended' => false,
                'sort_order' => 4,
                'ovh_plan_code' => null,
                'ovh_config' => null,
            ],
            [
                'name' => 'VPS-5',
                'slug' => 'vps-5',
                'category' => 'vps_2026',
                'display_name' => 'VPS-5 - Enterprise',
                'description' => 'Maximum power for enterprise workloads',
                'cpu_cores' => 24,
                'memory_gb' => 64,
                'disk_gb' => 350,
                'disk_type' => 'NVMe',
                'bandwidth' => '2.5 Gbps public',
                'unlimited_traffic' => true,
                'daily_backup' => true,
                'price_monthly' => 11850.00, // OVH ₹4,740 × 2.5
                'cost_price' => 4740.00,
                'setup_fee' => 0,
                'installation_free' => true,
                'features' => json_encode(['24 vCores', '64 GB RAM', '350 GB NVMe', 'Daily backup', 'Unlimited traffic', '2.5 Gbps bandwidth']),
                'is_active' => true,
                'is_sold_out' => true,
                'is_recommended' => false,
                'sort_order' => 5,
                'ovh_plan_code' => null,
                'ovh_config' => null,
            ],
            [
                'name' => 'VPS-6',
                'slug' => 'vps-6',
                'category' => 'vps_2026',
                'display_name' => 'VPS-6 - Ultimate',
                'description' => 'Ultimate performance for critical systems',
                'cpu_cores' => 32,
                'memory_gb' => 96,
                'disk_gb' => 400,
                'disk_type' => 'NVMe',
                'bandwidth' => '3 Gbps public',
                'unlimited_traffic' => true,
                'daily_backup' => true,
                'price_monthly' => 15730.00, // OVH ₹6,292 × 2.5
                'cost_price' => 6292.00,
                'setup_fee' => 0,
                'installation_free' => true,
                'features' => json_encode(['32 vCores', '96 GB RAM', '400 GB NVMe', 'Daily backup', 'Unlimited traffic', '3 Gbps bandwidth']),
                'is_active' => true,
                'is_sold_out' => true,
                'is_recommended' => false,
                'sort_order' => 6,
                'ovh_plan_code' => null,
                'ovh_config' => null,
            ],
        ];

        DB::table('vps_plans')->insert($plans);
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_plans');
    }
};
