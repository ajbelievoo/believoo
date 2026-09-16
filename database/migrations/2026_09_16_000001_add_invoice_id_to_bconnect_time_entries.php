<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_time_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('bconnect_time_entries', 'invoice_id')) {
                $table->foreignId('invoice_id')->nullable()->after('billed_amount')->constrained('bconnect_invoices')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_time_entries', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }
};
