<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_rtmp_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('streaming_api_keys')->onDelete('cascade');
            $table->string('name');
            $table->string('stream_key')->unique();
            $table->string('server_ip');
            $table->enum('source_type', ['obs', 'vmix', 'hardware', 'mobile', 'custom'])->default('obs');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->integer('current_viewers')->default(0);
            $table->integer('bitrate_kbps')->nullable();
            $table->string('resolution')->nullable();
            $table->integer('fps')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamps();

            $table->index(['api_key_id', 'is_active']);
            $table->index('stream_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_rtmp_sources');
    }
};
