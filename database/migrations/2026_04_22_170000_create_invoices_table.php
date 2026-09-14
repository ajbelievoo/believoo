<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->string('invoice_number')->unique();
            $table->string('invoice_type')->default('service'); // service, hosting, manual
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('pending'); // pending, paid, cancelled, refunded
            $table->string('payment_gateway')->nullable(); // razorpay, cashfree, manual, admin
            $table->string('payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('due_date')->nullable();
            $table->json('line_items')->nullable(); // Array of items
            $table->json('billing_details')->nullable(); // Address, GST, etc.
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who created
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['invoice_number']);
            $table->index(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
