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
        Schema::table('users', function (Blueprint $table) {
            // WHMCS Client ID for linking with billing
            $table->string('whmcs_client_id')->nullable()->after('id')->index();
            // Store VPS IDs as JSON array
            $table->json('vps_ids')->nullable()->after('whmcs_client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whmcs_client_id', 'vps_ids']);
        });
    }
};
