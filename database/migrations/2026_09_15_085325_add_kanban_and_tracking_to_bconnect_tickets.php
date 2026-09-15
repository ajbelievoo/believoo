<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_tickets', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('status');
            $table->foreignId('sprint_id')->nullable()->after('project_id');
            $table->foreignId('parent_id')->nullable()->after('sprint_id');
            $table->date('start_date')->nullable()->after('description');
            $table->date('due_date')->nullable()->after('start_date');
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_tickets', function (Blueprint $table) {
            $table->dropColumn(['position', 'sprint_id', 'parent_id', 'start_date', 'due_date', 'estimated_hours']);
        });
    }
};
