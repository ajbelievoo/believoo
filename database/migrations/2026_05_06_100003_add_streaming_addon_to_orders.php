<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add streaming addon fields to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_streaming_addon')->default(false)->after('metadata');
            $table->foreignId('streaming_plan_id')->nullable()->after('has_streaming_addon')->constrained()->onDelete('set null');
            $table->decimal('streaming_addon_price', 10, 2)->default(0)->after('streaming_plan_id');
        });
        
        // Add streaming addon to user_hostings table (for VPS + streaming combos)
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->boolean('has_streaming_addon')->default(false)->after('virtualizor_vps_id');
            $table->foreignId('streaming_api_key_id')->nullable()->after('has_streaming_addon')->constrained('streaming_api_keys')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['streaming_plan_id']);
            $table->dropColumn(['has_streaming_addon', 'streaming_plan_id', 'streaming_addon_price']);
        });
        
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->dropForeign(['streaming_api_key_id']);
            $table->dropColumn(['has_streaming_addon', 'streaming_api_key_id']);
        });
    }
};
