<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('bconnect_invoices', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('bconnect_invoices', 'is_subscription')) {
                $table->boolean('is_subscription')->default(false)->after('description');
            }
        });

        Schema::table('bconnect_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('bconnect_meetings', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable()->after('title');
            }
            if (!Schema::hasColumn('bconnect_meetings', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->nullable()->after('ended_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_invoices', function (Blueprint $table) {
            $table->dropColumn(['due_at', 'is_subscription']);
        });

        Schema::table('bconnect_meetings', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'duration_minutes']);
        });
    }
};
