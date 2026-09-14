<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * External / BelieVoo-DNS-only domains are not registered through a provider,
 * so registrar-specific fields must be optional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('domain_provider_id')->nullable()->change();
            $table->date('registration_date')->nullable()->change();
            $table->date('expiry_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_domains', function (Blueprint $table) {
            $table->unsignedBigInteger('domain_provider_id')->nullable(false)->change();
            $table->date('registration_date')->nullable(false)->change();
            $table->date('expiry_date')->nullable(false)->change();
        });
    }
};
