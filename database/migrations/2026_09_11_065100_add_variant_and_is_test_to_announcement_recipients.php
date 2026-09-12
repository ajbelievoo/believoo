<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcement_recipients', function (Blueprint $table) {
            $table->string('variant', 10)->nullable()->after('product');
            $table->boolean('is_test')->default(false)->after('variant');
        });
    }

    public function down(): void
    {
        Schema::table('announcement_recipients', function (Blueprint $table) {
            $table->dropColumn(['variant', 'is_test']);
        });
    }
};
