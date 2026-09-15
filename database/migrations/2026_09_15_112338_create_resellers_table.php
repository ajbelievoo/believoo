<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('slug')->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('brand_color', 20)->default('#00b7ff');
            $table->string('logo_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('support_email')->nullable();
            $table->string('billing_email')->nullable();
            $table->decimal('default_margin_percent', 8, 4)->default(10.0000);
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('custom_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resellers');
    }
};
