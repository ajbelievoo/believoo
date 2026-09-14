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
        Schema::create('user_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('domain_provider_id')->constrained()->onDelete('restrict');
            
            // Domain info
            $table->string('domain_name')->index(); // example.com
            $table->string('tld'); // com
            $table->string('sld'); // example
            
            // Registration details
            $table->date('registration_date');
            $table->date('expiry_date');
            $table->integer('registration_period')->default(1); // Years
            
            // Provider info
            $table->string('provider_domain_id')->nullable(); // Provider's domain ID
            $table->string('provider_order_id')->nullable(); // Provider's order ID
            
            // Status
            $table->string('status')->default('active'); // active, expired, suspended, pending_transfer
            $table->boolean('auto_renew')->default(true);
            
            // DNS
            $table->json('nameservers')->nullable();
            $table->boolean('use_provider_dns')->default(false);
            $table->boolean('use_believoo_dns')->default(true);
            
            // Contact info (stored as JSON)
            $table->json('registrant_contact')->nullable();
            $table->json('admin_contact')->nullable();
            $table->json('technical_contact')->nullable();
            $table->json('billing_contact')->nullable();
            
            // Privacy protection
            $table->boolean('whois_privacy')->default(false);
            
            // Pricing
            $table->decimal('purchase_price', 10, 2)->nullable(); // Provider's cost
            $table->decimal('selling_price', 10, 2)->nullable(); // What customer paid
            $table->decimal('profit_margin', 10, 2)->nullable(); // Believoo's profit
            $table->string('currency', 3)->default('USD');
            
            // Auth/Transfer code
            $table->text('auth_code')->nullable(); // EPP code
            
            // Metadata
            $table->json('metadata')->nullable(); // Provider-specific data
            $table->text('notes')->nullable();
            
            // Timestamps
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('renewal_reminder_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->unique(['user_id', 'domain_name']);
            $table->index(['status', 'expiry_date']);
            $table->index('provider_domain_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_domains');
    }
};
