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
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->decimal('base_price_usd', 10, 2)->nullable()->after('price_monthly');
            $table->decimal('sale_price_usd', 10, 2)->nullable()->after('base_price_usd');
        });

        // Convert existing INR prices to USD base
        $this->migrateExistingPrices();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vps_plans', function (Blueprint $table) {
            $table->dropColumn(['base_price_usd', 'sale_price_usd']);
        });
    }

    /**
     * Migrate existing INR prices to USD base
     */
    private function migrateExistingPrices(): void
    {
        // Default conversion rate: 1 USD = 83 INR (approximate)
        $defaultRate = 83;

        \DB::table('vps_plans')->update([
            'base_price_usd' => \DB::raw("ROUND(price_monthly / {$defaultRate}, 2)"),
        ]);
    }
};
