<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'referred_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('referred_by')->nullable()->after('referral_code')->constrained('users')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('referrals')) {
            Schema::create('referrals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('referred_id')->constrained('users')->cascadeOnDelete();
                $table->string('code_used')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status')->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('credited_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');

        if (Schema::hasColumn('users', 'referred_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('referred_by');
            });
        }
    }
};
