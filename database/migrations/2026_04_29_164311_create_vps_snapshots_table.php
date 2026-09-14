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
        Schema::create('vps_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proxmox_vm_id')->constrained('proxmox_vms')->onDelete('cascade');
            
            // Snapshot Details
            $table->string('name'); // User-defined name
            $table->text('description')->nullable();
            $table->string('proxmox_snapshot_id'); // Proxmox internal snapshot ID
            $table->enum('type', ['manual', 'auto', 'pre_migration', 'pre_update'])->default('manual');
            
            // Storage Info
            $table->bigInteger('size_bytes')->default(0);
            $table->string('storage_location')->default('local');
            
            // Status
            $table->enum('status', ['creating', 'completed', 'failed', 'restoring', 'deleted'])->default('creating');
            $table->timestamp('snapshotted_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For retention policy
            
            // Rollback tracking
            $table->timestamp('restored_at')->nullable();
            $table->json('restore_log')->nullable();
            
            $table->timestamps();
            
            $table->index(['proxmox_vm_id', 'status']);
            $table->index(['expires_at', 'status']); // For cleanup jobs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vps_snapshots');
    }
};
