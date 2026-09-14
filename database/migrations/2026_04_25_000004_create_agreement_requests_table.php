<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreement_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->string('request_number')->unique();
            $table->string('project_name');
            $table->text('project_description');
            $table->text('requirements')->nullable();
            $table->text('budget_range')->nullable();
            $table->string('timeline_expectation')->nullable();
            $table->string('preferred_technology')->nullable();
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'converted'])->default('pending');
            $table->foreignId('agreement_id')->nullable()->constrained('agreements')->onDelete('set null');
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreement_requests');
    }
};
