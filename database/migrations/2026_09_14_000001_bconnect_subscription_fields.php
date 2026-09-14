<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bconnect_companies', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_companies', 'subscription_status')) {
                $t->string('subscription_status')->default('free')->after('plan');
            }
            if (!Schema::hasColumn('bconnect_companies', 'trial_ends_at')) {
                $t->timestamp('trial_ends_at')->nullable()->after('plan_expires_at');
            }
            if (!Schema::hasColumn('bconnect_companies', 'grace_period_until')) {
                $t->timestamp('grace_period_until')->nullable()->after('trial_ends_at');
            }
            if (!Schema::hasColumn('bconnect_companies', 'next_invoice_at')) {
                $t->timestamp('next_invoice_at')->nullable()->after('grace_period_until');
            }
        });
    }

    public function down(): void {
        Schema::table('bconnect_companies', function (Blueprint $t) {
            $t->dropColumnIfExists('subscription_status');
            $t->dropColumnIfExists('trial_ends_at');
            $t->dropColumnIfExists('grace_period_until');
            $t->dropColumnIfExists('next_invoice_at');
        });
    }
};
