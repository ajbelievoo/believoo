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
        Schema::create('domain_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_provider_id')->constrained()->onDelete('cascade');
            
            $table->string('tld'); // e.g., com, in, net
            $table->integer('years')->default(1); // Registration period
            
            // Provider pricing (what we pay)
            $table->decimal('provider_register_price', 10, 2);
            $table->decimal('provider_renew_price', 10, 2);
            $table->decimal('provider_transfer_price', 10, 2)->nullable();
            
            // Believoo selling price (what customer pays)
            $table->decimal('selling_register_price', 10, 2);
            $table->decimal('selling_renew_price', 10, 2);
            $table->decimal('selling_transfer_price', 10, 2)->nullable();
            
            // Profit margins
            $table->decimal('register_profit', 10, 2)->virtualAs('selling_register_price - provider_register_price');
            $table->decimal('renew_profit', 10, 2)->virtualAs('selling_renew_price - provider_renew_price');
            
            // Currency
            $table->string('currency', 3)->default('USD');
            
            // Promo/Discount
            $table->boolean('is_promo')->default(false);
            $table->date('promo_start')->nullable();
            $table->date('promo_end')->nullable();
            $table->decimal('promo_price', 10, 2)->nullable();
            
            // TLD routing rules
            $table->integer('priority')->default(100); // For dynamic routing
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->unique(['domain_provider_id', 'tld', 'years']);
            $table->index(['tld', 'is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_pricing');
    }
};
