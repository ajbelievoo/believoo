<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('referred_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('referral_code')->unique();
            $table->string('referred_email');
            $table->string('referred_name')->nullable();
            $table->enum('status', ['pending', 'registered', 'converted', 'rewarded'])->default('pending');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('reward_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        // Add referral code to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->unique()->after('email');
            $table->decimal('referral_discount_balance', 10, 2)->default(0)->after('referral_code');
            $table->integer('total_referrals')->default(0)->after('referral_discount_balance');
            $table->integer('successful_referrals')->default(0)->after('total_referrals');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'referral_discount_balance', 'total_referrals', 'successful_referrals']);
        });
    }
};
