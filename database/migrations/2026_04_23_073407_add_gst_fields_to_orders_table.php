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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal', 12, 2)->nullable()->after('billing_months');
            $table->decimal('gst_amount', 12, 2)->nullable()->after('subtotal');
            $table->decimal('gst_rate', 5, 4)->nullable()->after('gst_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'gst_amount', 'gst_rate']);
        });
    }
};
