<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            
            // Streaming-specific limits
            $table->integer('max_viewers')->default(100); // Concurrent viewers
            $table->integer('max_bitrate')->default(5000); // Max bitrate in kbps
            $table->integer('max_resolution')->default(1080); // Max resolution (720, 1080, 1440, 2160)
            $table->integer('bandwidth_gb')->default(1000); // Bandwidth limit in GB per month
            $table->integer('storage_gb')->default(50); // Recording storage in GB
            $table->integer('stream_count')->default(1); // Number of concurrent streams
            
            // Features
            $table->boolean('rtmp_support')->default(true);
            $table->boolean('webrtc_support')->default(true);
            $table->boolean('hls_support')->default(true);
            $table->boolean('dash_support')->default(false);
            $table->boolean('recording_enabled')->default(true);
            $table->boolean('transcoding_enabled')->default(false);
            $table->boolean('adaptive_bitrate')->default(false);
            $table->boolean('low_latency')->default(false);
            
            // Plan metadata
            $table->json('features')->nullable(); // Array of feature strings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_addon')->default(false); // Can be added to VPS
            $table->decimal('addon_price', 10, 2)->default(0); // Price when added as VPS addon
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            $table->index(['is_active', 'is_addon']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_plans');
    }
};
