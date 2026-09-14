<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('license_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('proxmox_vm_id')->nullable()->constrained('proxmox_vms')->onDelete('set null');
            
            // License Details
            $table->string('license_type'); // aapanel, cpanel, cpanel_plus, plesk, etc.
            $table->string('order_number')->unique();
            
            // Pricing
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('billing_cycle'); // monthly, yearly
            
            // Payment Status
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable(); // razorpay, cashfree, paypal, payu
            $table->string('payment_id')->nullable(); // Gateway transaction ID
            $table->json('payment_response')->nullable(); // Full gateway response
            
            // Activation Status
            $table->enum('activation_status', ['pending_payment', 'ready_to_activate', 'activating', 'active', 'failed'])->default('pending_payment');
            $table->foreignId('vps_license_id')->nullable()->constrained('vps_licenses')->onDelete('set null');
            
            // Activation Details
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // IP/Server tracking for license binding
            $table->string('server_ip')->nullable();
            $table->string('server_hostname')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'payment_status']);
            $table->index(['license_type', 'activation_status']);
            $table->index('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_orders');
    }
};
