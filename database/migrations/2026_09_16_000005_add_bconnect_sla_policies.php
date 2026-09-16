<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bconnect_sla_policies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies')->cascadeOnDelete();
            $t->string('name');
            $t->string('applies_to')->default('all'); // all, project, priority
            $t->json('conditions')->nullable(); // e.g. {"priority":"high"}
            $t->integer('response_time_seconds')->default(3600);
            $t->integer('resolution_time_seconds')->default(86400);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::table('bconnect_tickets', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_tickets', 'sla_policy_id')) {
                $t->foreignId('sla_policy_id')->nullable()->after('company_id')->constrained('bconnect_sla_policies')->nullOnDelete();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'first_response_at')) {
                $t->timestamp('first_response_at')->nullable()->after('resolved_at');
            }
            if (!Schema::hasColumn('bconnect_tickets', 'sla_breached')) {
                $t->boolean('sla_breached')->default(false)->after('first_response_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_tickets', function (Blueprint $t) {
            $t->dropColumn(['sla_policy_id', 'first_response_at', 'sla_breached']);
        });
        Schema::dropIfExists('bconnect_sla_policies');
    }
};
