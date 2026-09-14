<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streaming_usage_logs', function (Blueprint $table) {
            // Add spec-required columns
            $table->foreignId('streaming_project_id')->nullable()->after('streaming_api_key_id')->constrained('streaming_projects')->onDelete('cascade');
            $table->decimal('bandwidth_used_gb', 10, 4)->default(0)->after('bandwidth_used_mb');
            $table->unsignedInteger('peak_concurrent_viewers')->default(0)->after('viewer_count');
            $table->unsignedInteger('active_stream_minutes')->default(0)->after('duration_minutes');
            $table->timestamp('recorded_at')->nullable()->after('ended_at');
            
            $table->index('streaming_project_id', 'idx_sul_project_id');
            $table->index('recorded_at', 'idx_sul_recorded_at');
        });
    }

    public function down(): void
    {
        Schema::table('streaming_usage_logs', function (Blueprint $table) {
            $table->dropForeign(['streaming_project_id']);
            $table->dropIndex('idx_sul_project_id');
            $table->dropIndex('idx_sul_recorded_at');
            $table->dropColumn(['streaming_project_id', 'bandwidth_used_gb', 'peak_concurrent_viewers', 'active_stream_minutes', 'recorded_at']);
        });
    }
};
