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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('base_currency', 3)->default('USD');
            $table->string('target_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->decimal('previous_rate', 15, 6)->nullable();
            $table->timestamp('fetched_at');
            $table->string('source')->default('exchangerate-api'); // API provider name
            $table->json('api_response')->nullable();
            $table->timestamps();

            $table->unique(['base_currency', 'target_currency']);
            $table->index('target_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
