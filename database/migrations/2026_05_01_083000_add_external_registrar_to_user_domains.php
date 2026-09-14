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
        Schema::table('user_domains', function (Blueprint $table) {
            $table->string('external_registrar')->nullable()->after('use_believoo_dns')
                ->comment('For domains purchased from external registrars like GoDaddy, Namecheap, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_domains', function (Blueprint $table) {
            $table->dropColumn('external_registrar');
        });
    }
};
