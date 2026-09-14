<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vm_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proxmox_vm_id')->constrained('proxmox_vms')->onDelete('cascade');
            
            // Source and Target
            $table->string('source_node');
            $table->string('target_node');
            $table->integer('vmid');
            
            // Migration Status
            $table->string('status')->default('pending'); // pending, migrating, completed, failed
            $table->integer('progress_percent')->default(0);
            $table->string('status_message')->nullable();
            
            // Proxmox Task Tracking
            $table->string('proxmox_upid')->nullable(); // Unique Process ID for tracking
            $table->string('task_status')->nullable(); // running, stopped, OK, ERROR
            
            // Migration Details
            $table->boolean('is_live_migration')->default(true);
            $table->bigInteger('total_bytes_transferred')->nullable();
            $table->bigInteger('bytes_remaining')->nullable();
            $table->string('migration_type')->nullable(); // online, offline
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Error Handling
            $table->text('error_log')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes for fast queries
            $table->index(['user_id', 'status']);
            $table->index(['proxmox_vm_id', 'status']);
            $table->index('proxmox_upid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vm_migrations');
    }
};
