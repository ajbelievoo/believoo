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
        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('image')->comment('FontAwesome icon class');
            $table->string('status')->default('completed')->after('icon');
            $table->string('gradient_from')->nullable()->after('status');
            $table->string('gradient_to')->nullable()->after('gradient_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['icon', 'status', 'gradient_from', 'gradient_to']);
        });
    }
};
