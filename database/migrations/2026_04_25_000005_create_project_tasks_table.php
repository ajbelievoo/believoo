<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->foreignId('milestone_id')->nullable()->constrained('agreement_milestones')->onDelete('set null');
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'on_hold'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->integer('progress_percentage')->default(0);
            $table->string('assigned_to')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
