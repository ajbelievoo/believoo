<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ovh_products', function (Blueprint $table) {
            $table->id();
            $table->string('category')->index();
            $table->string('family')->nullable()->index();
            $table->string('plan_code')->unique();
            $table->string('invoice_name')->nullable();
            $table->text('description')->nullable();
            $table->integer('cpu_cores')->nullable();
            $table->integer('ram_gb')->nullable();
            $table->integer('disk_gb')->nullable();
            $table->string('disk_type')->nullable();
            $table->integer('bandwidth_mbps')->nullable();
            $table->string('currency', 3)->default('INR');
            $table->decimal('price_monthly', 15, 2)->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->decimal('commission_percent', 5, 2)->default(0);
            $table->json('durations')->nullable();
            $table->json('ovh_config')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ovh_products');
    }
};
