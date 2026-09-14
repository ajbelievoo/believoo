<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streaming_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('streaming_subscriptions', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('delivery_method')
                    ->comment('Subscription price paid');
            }
            if (!Schema::hasColumn('streaming_subscriptions', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('price');
            }
            if (!Schema::hasColumn('streaming_subscriptions', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
            if (!Schema::hasColumn('streaming_subscriptions', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('streaming_subscriptions', 'streaming_config')) {
                $table->json('streaming_config')->nullable()->after('activated_at')
                    ->comment('Cluster endpoints and plan limits JSON config');
            }
            // Expand status enum to include pending_setup
            if (Schema::hasColumn('streaming_subscriptions', 'status')) {
                $table->enum('status', ['active', 'suspended', 'cancelled', 'pending_setup'])->default('active')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('streaming_subscriptions', function (Blueprint $table) {
            $table->dropColumnIfExists('price');
            $table->dropColumnIfExists('starts_at');
            $table->dropColumnIfExists('ends_at');
            $table->dropColumnIfExists('activated_at');
            $table->dropColumnIfExists('streaming_config');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active')->change();
        });
    }
};
