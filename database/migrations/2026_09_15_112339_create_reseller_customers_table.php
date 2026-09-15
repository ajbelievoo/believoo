<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('margin_percent', 8, 4)->nullable();
            $table->json('custom_prices')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('onboarded_at')->nullable();
            $table->timestamps();

            $table->unique(['reseller_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_customers');
    }
};
