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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->morphs('keyable'); // For admin or service keys
            
            // API Key Details
            $table->string('name'); // e.g., "Mobile App", "External Integration"
            $table->string('key', 64)->unique();
            $table->string('secret', 64)->nullable();
            
            // Permissions
            $table->json('permissions')->nullable(); // ['vms:read', 'vms:control', 'stats:read']
            
            // Rate Limiting
            $table->integer('rate_limit')->default(60); // Requests per minute
            $table->integer('requests_count')->default(0);
            
            // IP Restriction
            $table->json('allowed_ips')->nullable(); // ['192.168.1.1', '10.0.0.0/8']
            
            // Status & Expiry
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
