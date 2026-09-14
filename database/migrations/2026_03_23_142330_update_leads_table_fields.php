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
        Schema::table('leads', function (Blueprint $table) {
            $table->renameColumn('name', 'full_name');
            $table->string('company')->nullable()->after('phone');
            $table->renameColumn('service_type', 'project_type');
            $table->string('budget')->nullable()->after('project_type');
            $table->text('requirements')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->renameColumn('full_name', 'name');
            $table->dropColumn(['company', 'budget']);
            $table->renameColumn('project_type', 'service_type');
        });
    }
};
