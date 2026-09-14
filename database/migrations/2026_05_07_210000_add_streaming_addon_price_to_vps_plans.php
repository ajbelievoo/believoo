<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vps_plans', 'streaming_addon_price')) {
            Schema::table('vps_plans', function (Blueprint $table) {
                $table->decimal('streaming_addon_price', 10, 2)->nullable()->after('setup_fee')
                    ->comment('Price for streaming addon on this VPS tier (USD)');
            });
        }
    }

    public function down(): void
    {
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->dropColumn('streaming_addon_price');
        });
    }
};
