<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateMissingTablesSeeder extends Seeder
{
    public function run(): void
    {
        // Create project_feedback table
        if (!Schema::hasTable('project_feedback')) {
            Schema::create('project_feedback', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->integer('rating');
                $table->text('review')->nullable();
                $table->integer('nps_score')->nullable();
                $table->boolean('would_recommend')->nullable();
                $table->json('categories')->nullable();
                $table->boolean('is_approved')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
            echo "project_feedback table created!\n";
        }

        // Create project_documents table
        if (!Schema::hasTable('project_documents')) {
            Schema::create('project_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
                $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('file_path');
                $table->string('file_name');
                $table->string('file_type')->nullable();
                $table->integer('file_size')->nullable();
                $table->enum('document_type', ['contract', 'design', 'deliverable', 'invoice', 'other'])->default('other');
                $table->enum('visibility', ['client', 'admin', 'both'])->default('both');
                $table->integer('download_count')->default(0);
                $table->timestamps();
            });
            echo "project_documents table created!\n";
        }

        // Create agreement_invoices table
        if (!Schema::hasTable('agreement_invoices')) {
            Schema::create('agreement_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
                $table->foreignId('milestone_id')->nullable()->constrained('agreement_milestones')->onDelete('set null');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->string('invoice_number')->unique();
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->decimal('subtotal', 12, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2);
                $table->decimal('paid_amount', 12, 2)->default(0);
                $table->decimal('balance_amount', 12, 2);
                $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
                $table->string('payment_method')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
            echo "agreement_invoices table created!\n";
        }
    }
}
