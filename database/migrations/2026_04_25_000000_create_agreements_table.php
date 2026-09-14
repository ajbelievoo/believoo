<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->id();
            $table->string('agreement_number')->unique();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('service_provider_name')->default('Believoo');
            $table->string('lead_developer')->nullable();
            $table->string('client_name');
            $table->string('project_name');
            $table->text('project_overview')->nullable();
            $table->text('technical_specs')->nullable();
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->integer('timeline_months')->default(4);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('upfront_amount', 12, 2)->default(0);
            $table->text('payment_terms')->nullable();
            $table->text('deliverables')->nullable();
            $table->text('support_terms')->nullable();
            $table->enum('status', ['draft', 'sent', 'viewed', 'signed', 'cancelled'])->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->text('admin_signature_data')->nullable();
            $table->text('client_signature_data')->nullable();
            $table->text('additional_terms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
