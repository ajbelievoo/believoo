<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ovh_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('all');
            $table->string('plan_code')->default('*');
            $table->decimal('margin_percent', 8, 4)->nullable();
            $table->decimal('fixed_markup', 15, 4)->default(0);
            $table->decimal('min_margin_percent', 8, 4)->nullable();
            $table->decimal('max_margin_percent', 8, 4)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->unsignedTinyInteger('round_to')->default(2);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'plan_code', 'is_active', 'priority']);
            $table->index(['active_from', 'active_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ovh_pricing_rules');
    }
};
