<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sso_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sso_provider_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->json('attributes')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['sso_provider_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sso_links');
    }
};
