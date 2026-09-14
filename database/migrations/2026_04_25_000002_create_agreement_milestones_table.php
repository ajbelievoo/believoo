<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->string('phase_name');
            $table->text('description')->nullable();
            $table->decimal('payment_amount', 12, 2)->default(0);
            $table->integer('timeline_month')->default(1);
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'completed', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_milestones');
    }
};
