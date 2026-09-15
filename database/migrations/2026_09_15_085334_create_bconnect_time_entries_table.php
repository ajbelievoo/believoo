<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bconnect_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('bconnect_companies')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('bconnect_members')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('bconnect_projects')->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('bconnect_tickets')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_billable')->default(false);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('billed_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'member_id']);
            $table->index(['ticket_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_time_entries');
    }
};
