<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streaming_api_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('streaming_api_keys', 'streaming_subscription_id')) {
                $table->foreignId('streaming_subscription_id')->nullable()->after('order_id')
                    ->constrained('streaming_subscriptions')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('streaming_api_keys', function (Blueprint $table) {
            if (Schema::hasColumn('streaming_api_keys', 'streaming_subscription_id')) {
                $table->dropConstrainedForeignId('streaming_subscription_id');
            }
        });
    }
};
