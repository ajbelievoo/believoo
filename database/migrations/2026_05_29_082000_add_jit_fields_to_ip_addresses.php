<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->string('source')->default('pool')->after('status')->comment('pool | jit');
            $table->string('provider_name')->nullable()->after('source');
            $table->string('provider_ref')->nullable()->after('provider_name');
            $table->decimal('cost', 10, 2)->nullable()->after('provider_ref');
        });
    }

    public function down(): void
    {
        Schema::table('ip_addresses', function (Blueprint $table) {
            $table->dropColumn(['source', 'provider_name', 'provider_ref', 'cost']);
        });
    }
};
