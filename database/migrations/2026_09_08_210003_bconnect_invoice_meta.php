<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('bconnect_invoices', function (Blueprint $t) {
            $t->json('metadata')->nullable()->after('status');
        });
    }
    public function down(): void {
        Schema::table('bconnect_invoices', function (Blueprint $t) {
            $t->dropColumn('metadata');
        });
    }
};
