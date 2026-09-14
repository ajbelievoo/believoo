<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->string('provider_name')->nullable()->after('hosting_type')->index();
            $table->string('provider_order_id')->nullable()->after('provider_name')->index();
            $table->string('provider_service_id')->nullable()->after('provider_order_id')->index();
            $table->json('provider_metadata')->nullable()->after('provider_service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->dropColumn(['provider_name', 'provider_order_id', 'provider_service_id', 'provider_metadata']);
        });
    }
};
