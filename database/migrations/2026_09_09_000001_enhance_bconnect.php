<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bconnect_companies', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_companies', 'description')) $t->text('description')->nullable();
            if (!Schema::hasColumn('bconnect_companies', 'branding')) $t->json('branding')->nullable();
            if (!Schema::hasColumn('bconnect_companies', 'plan_expires_at')) $t->timestamp('plan_expires_at')->nullable();
        });

        Schema::table('bconnect_members', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_members', 'permissions')) $t->json('permissions')->nullable();
        });

        Schema::table('bconnect_tickets', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_tickets', 'ai_tags')) $t->json('ai_tags')->nullable();
            if (!Schema::hasColumn('bconnect_tickets', 'ai_suggested_priority')) $t->string('ai_suggested_priority')->nullable();
        });
    }

    public function down(): void {
        // not reversible for safety
    }
};
