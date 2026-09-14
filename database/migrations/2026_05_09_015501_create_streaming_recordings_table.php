<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->constrained('streaming_api_keys')->onDelete('cascade');
            $table->string('channel_name');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->decimal('file_size_mb', 10, 2)->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->enum('status', ['recording', 'completed', 'processing', 'failed'])->default('recording');
            $table->boolean('is_public')->default(false);
            $table->string('download_url')->nullable();
            $table->string('playback_url')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('retention_days')->default(30);
            $table->timestamps();

            $table->index(['api_key_id', 'status']);
            $table->index('channel_name');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_recordings');
    }
};
