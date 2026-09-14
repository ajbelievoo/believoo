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
        Schema::create('audio_mixer_sessions', function (Blueprint $table) {
            $table->id();
            
            // Mixer identification
            $table->string('mixer_id', 64)->unique();
            $table->string('stream_id', 255)->index();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('streaming_api_key_id')->nullable()->constrained('streaming_api_keys')->onDelete('set null');
            
            // Status
            $table->enum('status', ['initialized', 'running', 'paused', 'stopped', 'error'])->default('initialized');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->integer('pid')->nullable(); // FFmpeg process ID
            
            // Audio sources
            $table->string('voice_input_url', 512)->nullable();
            $table->string('music_input_url', 512)->nullable();
            $table->string('mixed_output_url', 512)->nullable();
            
            // Volume settings (0.0 to 2.0)
            $table->decimal('voice_volume', 3, 2)->default(1.0);
            $table->decimal('music_volume', 3, 2)->default(0.3);
            
            // Audio quality settings
            $table->integer('bitrate')->default(192); // kbps
            $table->integer('sample_rate')->default(48000);
            $table->integer('channels')->default(2);
            
            // Metadata
            $table->json('config')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('duration_seconds')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_mixer_sessions');
    }
};
