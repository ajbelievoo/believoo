<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('streaming_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('streaming_plan_id')->constrained('streaming_plans');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->enum('status', ['active', 'suspended', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index('user_id', 'idx_ss_user_id');
            $table->index('status', 'idx_ss_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaming_subscriptions');
    }
};
