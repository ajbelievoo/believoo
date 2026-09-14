<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('referrals');

        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('referral_code')->nullable()->index();
            $table->string('referred_email')->nullable();
            $table->string('referred_name')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
