<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('streaming_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            
            // API Credentials (Agora-style)
            $table->string('app_id', 64)->unique(); // Unique App ID for the project
            $table->string('app_certificate', 128); // App Certificate for token generation
            $table->string('rest_api_key', 64)->unique(); // REST API Key for server-side operations
            $table->string('rest_api_secret', 128); // REST API Secret
            
            // Stream Configuration
            $table->string('rtmp_ingest_url')->nullable();
            $table->string('webrtc_ingest_url')->nullable();
            $table->string('hls_playback_url')->nullable();
            $table->string('dash_playback_url')->nullable();
            $table->string('web_rtc_url')->nullable();
            
            // Status and Limits
            $table->enum('status', ['active', 'suspended', 'expired', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            
            // Usage tracking (current month)
            $table->integer('current_viewers')->default(0);
            $table->decimal('bandwidth_used_gb', 10, 2)->default(0);
            $table->decimal('storage_used_gb', 10, 2)->default(0);
            $table->integer('total_stream_minutes')->default(0);
            
            // Security
            $table->json('allowed_domains')->nullable(); // CORS allowed domains
            $table->json('allowed_ips')->nullable(); // IP whitelist
            $table->boolean('regeneration_locked')->default(false); // Prevent regeneration if true
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['app_id', 'app_certificate']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_api_keys');
    }
};
