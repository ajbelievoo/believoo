<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streaming_plans', function (Blueprint $table) {
            // Add spec-required columns (existing table has bandwidth_gb, max_viewers, storage_gb)
            // These are additive aliases used by the new spec models
            if (!Schema::hasColumn('streaming_plans', 'bandwidth_limit_gb')) {
                $table->unsignedInteger('bandwidth_limit_gb')->default(1000)->after('bandwidth_gb');
            }
            if (!Schema::hasColumn('streaming_plans', 'max_concurrent_viewers')) {
                $table->unsignedInteger('max_concurrent_viewers')->default(100)->after('max_viewers');
            }
            if (!Schema::hasColumn('streaming_plans', 'storage_limit_gb')) {
                $table->unsignedInteger('storage_limit_gb')->default(50)->after('storage_gb');
            }
            if (!Schema::hasColumn('streaming_plans', 'price_monthly')) {
                $table->decimal('price_monthly', 10, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('streaming_plans', 'max_projects')) {
                $table->unsignedInteger('max_projects')->default(5)->after('stream_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('streaming_plans', function (Blueprint $table) {
            $table->dropColumn(['bandwidth_limit_gb', 'max_concurrent_viewers', 'storage_limit_gb', 'price_monthly', 'max_projects']);
        });
    }
};
