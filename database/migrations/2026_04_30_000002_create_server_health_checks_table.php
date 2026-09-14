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
        Schema::create('server_health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proxmox_vm_id')->nullable()->constrained('proxmox_vms')->onDelete('cascade');
            $table->string('check_type'); // vm_status, node_resources, api_health, network
            
            // Status & Metrics
            $table->enum('status', ['healthy', 'warning', 'critical', 'unknown'])->default('unknown');
            $table->decimal('metric_value', 10, 2)->nullable(); // CPU %, RAM %, etc.
            $table->string('metric_unit')->nullable(); // %, MB, GB, ms
            
            // Details
            $table->text('message')->nullable();
            $table->json('details')->nullable(); // Full health data snapshot
            
            // Alert Status
            $table->boolean('alert_sent')->default(false);
            $table->timestamp('alert_sent_at')->nullable();
            $table->string('alert_channels')->nullable(); // telegram,email
            
            $table->timestamps();
            
            $table->index(['proxmox_vm_id', 'check_type', 'created_at']);
            $table->index(['status', 'alert_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_health_checks');
    }
};
