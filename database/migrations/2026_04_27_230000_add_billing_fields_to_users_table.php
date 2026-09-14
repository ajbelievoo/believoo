<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('next_due_date')->nullable()->after('vps_ids');
            $table->decimal('plan_price', 10, 2)->nullable()->after('next_due_date');
            $table->enum('billing_status', ['active', 'suspended', 'terminated'])->default('active')->after('plan_price');
            $table->date('last_payment_date')->nullable()->after('billing_status');
            $table->string('current_plan_name')->nullable()->after('last_payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'next_due_date',
                'plan_price',
                'billing_status',
                'last_payment_date',
                'current_plan_name',
            ]);
        });
    }
};
