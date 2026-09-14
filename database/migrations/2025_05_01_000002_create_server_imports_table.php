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
        Schema::create('server_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_hosting_id')->constrained('user_hostings')->onDelete('cascade');
            
            // Source server details
            $table->string('source_ip');
            $table->string('source_root_password')->nullable(); // Encrypted
            $table->integer('source_port')->default(22);
            $table->string('source_hostname')->nullable();
            $table->string('source_os')->nullable();
            
            // Migration status
            $table->string('status')->default('pending'); // pending, connecting, syncing, verifying, config_updating, completed, failed
            $table->integer('progress_percent')->default(0);
            $table->text('status_message')->nullable();
            
            // Rsync details
            $table->bigInteger('total_bytes')->nullable();
            $table->bigInteger('transferred_bytes')->default(0);
            $table->string('current_file')->nullable();
            $table->integer('file_count')->default(0);
            $table->integer('files_transferred')->default(0);
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            
            // Error logging
            $table->text('error_log')->nullable();
            $table->text('output_log')->nullable();
            
            // Post-migration config
            $table->boolean('fstab_updated')->default(false);
            $table->boolean('network_configured')->default(false);
            $table->boolean('aapanel_migrated')->default(false);
            
            // Metadata
            $table->json('metadata')->nullable(); // Store additional info like aaPanel details
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['user_hosting_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_imports');
    }
};
