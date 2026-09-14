<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proxmox_nodes', function (Blueprint $table) {
            $table->id();
            
            // Node Identification
            $table->string('name')->unique(); // e.g., "sg1-proxmox", "in1-proxmox", "us1-proxmox"
            $table->string('display_name'); // e.g., "Singapore Node 1", "India Node 1"
            $table->string('hostname'); // FQDN or IP for API connection
            $table->integer('port')->default(8006);
            
            // Datacenter Location
            $table->string('country_code', 2); // SG, IN, US, etc.
            $table->string('city'); // Singapore, Mumbai, New York
            $table->string('region')->nullable(); // Asia, North America, Europe
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status & Capacity
            $table->enum('status', ['active', 'maintenance', 'offline', 'coming_soon'])->default('coming_soon');
            $table->boolean('is_default')->default(false); // Default node for new VMs
            $table->integer('max_vms')->default(100);
            $table->integer('current_vms')->default(0);
            
            // Hardware Specs (for capacity planning)
            $table->integer('total_cpu_cores')->default(0);
            $table->bigInteger('total_memory_bytes')->default(0);
            $table->bigInteger('total_disk_bytes')->default(0);
            $table->bigInteger('total_bandwidth_bytes')->default(0);
            
            // Resource Usage (auto-updated)
            $table->integer('used_cpu_percent')->default(0);
            $table->integer('used_memory_percent')->default(0);
            $table->integer('used_disk_percent')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            
            // API Credentials (encrypted)
            $table->text('api_token_encrypted')->nullable();
            $table->text('root_password_encrypted')->nullable();
            
            // Network Configuration
            $table->string('network_gateway')->nullable();
            $table->string('network_subnet')->nullable();
            $table->json('ip_pools')->nullable(); // Available IP ranges
            
            // Provider Info (if using OVH, Hetzner, etc.)
            $table->string('provider_name')->nullable(); // ovh, hetzner, aws, custom
            $table->string('provider_server_id')->nullable();
            $table->json('provider_metadata')->nullable();
            
            // Display Settings
            $table->string('flag_emoji', 10)->nullable(); // 🇸🇬, 🇮🇳, 🇺🇸
            $table->string('latency_hint')->nullable(); // "15ms from Mumbai"
            
            $table->timestamps();
            
            $table->index(['status', 'country_code']);
            $table->index(['is_default', 'status']);
            $table->index(['country_code', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxmox_nodes');
    }
};
