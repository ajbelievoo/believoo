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
        Schema::create('dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_domain_id')->constrained('user_domains')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('record_type', 10); // A, AAAA, CNAME, MX, TXT, NS, SRV, CAA
            $table->string('name', 255); // Hostname/Subdomain
            $table->text('value'); // IP address, domain, or text value
            $table->integer('ttl')->default(3600); // Time to live in seconds
            $table->integer('priority')->nullable(); // For MX and SRV records
            $table->integer('weight')->nullable(); // For SRV records
            $table->integer('port')->nullable(); // For SRV records
            $table->string('protocol', 10)->nullable(); // For SRV records
            $table->string('service', 50)->nullable(); // For SRV records
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System records like NS
            $table->boolean('is_provisioned')->default(false); // Auto-provisioned from VPS
            $table->string('source', 50)->default('manual'); // manual, vps, provider, import
            $table->text('notes')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // For dynamic records
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index(['user_domain_id', 'record_type']);
            $table->index(['user_id', 'record_type']);
            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dns_records');
    }
};
