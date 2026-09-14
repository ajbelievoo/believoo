<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('streaming_api_key_id')->constrained('streaming_api_keys')->onDelete('cascade');
            
            // Stream identification
            $table->string('stream_id', 64)->nullable(); // Unique stream session ID
            $table->string('channel_name', 128)->nullable();
            $table->string('stream_type', 20)->default('rtmp'); // rtmp, webrtc, srt
            
            // Usage metrics
            $table->integer('viewer_count')->default(0); // Peak viewers during session
            $table->decimal('bandwidth_used_mb', 12, 2)->default(0);
            $table->integer('duration_minutes')->default(0);
            $table->decimal('avg_bitrate_kbps', 8, 2)->nullable();
            $table->string('resolution', 20)->nullable(); // e.g., "1920x1080"
            $table->string('codec', 20)->nullable(); // h264, h265, vp8, vp9
            
            // Geolocation data (anonymized)
            $table->string('country_code', 2)->nullable();
            $table->string('region', 50)->nullable();
            
            // Timestamps
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 50)->nullable(); // user_ended, timeout, error, limit_reached
            
            // Metadata
            $table->json('metadata')->nullable(); // Additional flexible data
            $table->string('user_agent_hash', 64)->nullable(); // Anonymized viewer hash
            
            $table->timestamps();
            
            // Indexes for analytics queries
            $table->index(['user_id', 'started_at']);
            $table->index(['streaming_api_key_id', 'started_at']);
            $table->index(['stream_id']);
            $table->index(['started_at', 'ended_at']);
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_usage_logs');
    }
};
