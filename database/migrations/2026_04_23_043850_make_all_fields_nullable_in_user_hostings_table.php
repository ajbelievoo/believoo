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
            $table->string('billing_cycle')->nullable()->change();
            $table->string('primary_domain')->nullable()->change();
            $table->string('server_ip')->nullable()->change();
            $table->text('admin_notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->string('billing_cycle')->nullable(false)->change();
            $table->string('primary_domain')->nullable(false)->change();
            $table->string('server_ip')->nullable(false)->change();
            $table->text('admin_notes')->nullable(false)->change();
        });
    }
};
