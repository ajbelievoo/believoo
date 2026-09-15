<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_sprints', function (Blueprint $table) {
            $table->foreignId('company_id')->after('id')->constrained('bconnect_companies')->cascadeOnDelete();
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_sprints', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'status']);
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
