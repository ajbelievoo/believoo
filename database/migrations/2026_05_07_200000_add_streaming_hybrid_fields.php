<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if delivery_method index exists for streaming_plans
        if (!Schema::hasIndex('streaming_plans', ['delivery_method', 'is_active'])) {
            Schema::table('streaming_plans', function (Blueprint $table) {
                $table->index(['delivery_method', 'is_active']);
            });
        }

        // Add vps_hosting_id to streaming_subscriptions for VPS linkage
        Schema::table('streaming_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('streaming_subscriptions', 'vps_hosting_id')) {
                $table->foreignId('vps_hosting_id')->nullable()->after('order_id')
                    ->constrained('hostings')->nullOnDelete();
            }
            if (!Schema::hasColumn('streaming_subscriptions', 'delivery_method')) {
                $table->string('delivery_method')->default('cloud_hosted')->after('vps_hosting_id')
                    ->comment('vps_embedded or cloud_hosted');
            }
            if (!Schema::hasIndex('streaming_subscriptions', ['vps_hosting_id', 'status'])) {
                $table->index(['vps_hosting_id', 'status']);
            }
            if (!Schema::hasIndex('streaming_subscriptions', ['delivery_method', 'status'])) {
                $table->index(['delivery_method', 'status']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('streaming_plans', function (Blueprint $table) {
            $table->dropIndex(['delivery_method', 'is_active']);
            $table->dropColumn('delivery_method');
        });

        Schema::table('streaming_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['vps_hosting_id', 'status']);
            $table->dropIndex(['delivery_method', 'status']);
            $table->dropConstrainedForeignId('vps_hosting_id');
            $table->dropColumn('delivery_method');
        });
    }
};
