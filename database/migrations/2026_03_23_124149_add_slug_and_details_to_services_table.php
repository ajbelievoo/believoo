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
        Schema::table('services', function (Blueprint $table) {
            $table->string('slug')->unique()->after('title')->nullable();
            $table->longText('content')->nullable()->after('description');
            $table->json('pricing_tiers')->nullable()->after('price_label');
            $table->json('features')->nullable()->after('pricing_tiers');
            $table->string('category')->nullable()->after('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['slug', 'content', 'pricing_tiers', 'features', 'category']);
        });
    }
};
