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
        Schema::create('vps_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('proxmox_vm_id')->nullable()->constrained('proxmox_vms')->onDelete('set null');
            
            // License Details
            $table->enum('type', ['cpanel', 'plesk', 'aapanel', 'directadmin', 'cloudpanel', 'none'])->default('none');
            $table->string('license_key')->nullable(); // Encrypted storage
            $table->string('activation_code')->nullable();
            $table->text('notes')->nullable();
            
            // Pricing & Billing
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly', 'lifetime'])->default('monthly');
            
            // Status & Validity
            $table->enum('status', ['active', 'suspended', 'expired', 'pending', 'cancelled'])->default('pending');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            
            // Provider Integration
            $table->string('provider_name')->nullable(); // e.g., 'cpanel_partner', 'plesk_partner'
            $table->string('provider_order_id')->nullable();
            $table->json('provider_response')->nullable();
            
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['expires_at', 'status']); // For renewal reminders
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vps_licenses');
    }
};
