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
            $table->string('preferred_currency', 3)->default('INR')->after('phone');
            $table->string('country_code', 2)->nullable()->after('preferred_currency');
        });

        // Auto-detect currency based on existing users' context
        // For now, keep existing users as INR (Indian market default)
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['preferred_currency', 'country_code']);
        });
    }
};
