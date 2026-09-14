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
        Schema::create('proxmox_vms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('vmid')->unique(); // Proxmox VM ID
            $table->string('name');
            $table->string('node')->default('ns548195'); // Proxmox node name
            $table->integer('cpu_cores');
            $table->integer('memory_mb'); // RAM in MB
            $table->integer('disk_gb'); // Disk in GB
            $table->string('storage')->default('local-lvm');
            $table->string('iso')->nullable(); // ISO filename
            $table->string('iso_storage')->default('local');
            $table->string('bridge')->default('vmbr0');
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('status')->default('stopped'); // running, stopped, suspended
            $table->timestamp('created_at_proxmox')->nullable(); // When VM was created in Proxmox
            $table->timestamp('started_at')->nullable(); // When VM was last started
            $table->timestamp('stopped_at')->nullable(); // When VM was last stopped
            $table->text('notes')->nullable();
            $table->json('config_snapshot')->nullable(); // Snapshot of VM config at creation
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['vmid', 'node']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proxmox_vms');
    }
};
