<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL with IF NOT EXISTS to safely add columns
        $columns = [
            "ALTER TABLE streaming_plans ADD COLUMN IF NOT EXISTS bandwidth_limit_gb INT UNSIGNED NOT NULL DEFAULT 1000 AFTER bandwidth_gb",
            "ALTER TABLE streaming_plans ADD COLUMN IF NOT EXISTS max_concurrent_viewers INT UNSIGNED NOT NULL DEFAULT 100 AFTER max_viewers",
            "ALTER TABLE streaming_plans ADD COLUMN IF NOT EXISTS storage_limit_gb INT UNSIGNED NOT NULL DEFAULT 50 AFTER storage_gb",
            "ALTER TABLE streaming_plans ADD COLUMN IF NOT EXISTS price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price",
            "ALTER TABLE streaming_plans ADD COLUMN IF NOT EXISTS max_projects INT UNSIGNED NOT NULL DEFAULT 5 AFTER stream_count",
        ];

        foreach ($columns as $sql) {
            try {
                DB::statement($sql);
            } catch (\Exception $e) {
                // Column may already exist — safe to ignore
            }
        }

        // Sync values from existing columns
        DB::statement("UPDATE streaming_plans SET bandwidth_limit_gb = bandwidth_gb WHERE bandwidth_limit_gb = 1000");
        DB::statement("UPDATE streaming_plans SET max_concurrent_viewers = max_viewers WHERE max_concurrent_viewers = 100");
        DB::statement("UPDATE streaming_plans SET storage_limit_gb = storage_gb WHERE storage_limit_gb = 50");
        DB::statement("UPDATE streaming_plans SET price_monthly = price WHERE price_monthly = 0");
    }

    public function down(): void
    {
        Schema::table('streaming_plans', function (Blueprint $table) {
            $table->dropColumn(['bandwidth_limit_gb', 'max_concurrent_viewers', 'storage_limit_gb', 'price_monthly', 'max_projects']);
        });
    }
};
