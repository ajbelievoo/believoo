<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('agreement_milestones')->onDelete('cascade');
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('payment_method', ['easypaisa', 'jazzcash', 'bank_transfer', 'other']);
            $table->string('transaction_id');
            $table->string('screenshot_path')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending_verification', 'verified', 'rejected'])->default('pending_verification');
            $table->text('admin_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
    }
};
