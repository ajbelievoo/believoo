<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stream_recordings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('hosting_id')->constrained('user_hostings')->onDelete('cascade');
            $table->foreignId('api_key_id')->nullable()->constrained('streaming_api_keys')->onDelete('set null');
            $table->string('recording_name');
            $table->string('file_path');
            $table->string('file_url');
            $table->decimal('file_size_mb', 10, 2)->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['recording', 'completed', 'failed', 'deleted'])->default('recording');
            $table->timestamp('auto_delete_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('auto_delete_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_recordings');
    }
};
