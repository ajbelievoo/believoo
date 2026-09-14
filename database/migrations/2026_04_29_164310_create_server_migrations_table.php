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
        Schema::create('server_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proxmox_vm_id')->nullable()->constrained('proxmox_vms')->onDelete('set null');
            
            // Source Server Details
            $table->string('source_ip');
            $table->integer('source_ssh_port')->default(22);
            $table->text('source_password_encrypted'); // AES-256 encrypted
            $table->string('source_root_user')->default('root');
            
            // Migration Status
            $table->enum('status', [
                'pending', 'connecting', 'scanning', 'migrating_files', 
                'migrating_databases', 'verifying', 'completed', 'failed'
            ])->default('pending');
            $table->integer('progress_percent')->default(0);
            $table->text('status_message')->nullable();
            $table->text('error_log')->nullable();
            
            // Migration Details
            $table->json('discovered_websites')->nullable(); // /var/www folders
            $table->json('discovered_databases')->nullable(); // MySQL databases
            $table->json('migration_log')->nullable(); // Step-by-step log
            $table->bigInteger('total_bytes_transferred')->default(0);
            $table->integer('files_transferred')->default(0);
            $table->integer('databases_transferred')->default(0);
            
            // Estimated completion
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('estimated_duration_seconds')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'progress_percent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_migrations');
    }
};
