<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agreement_id')->constrained('agreements')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->integer('rating')->comment('1-5 stars');
            $table->text('review')->nullable();
            $table->integer('nps_score')->nullable()->comment('0-10 Net Promoter Score');
            $table->boolean('would_recommend')->nullable();
            $table->json('categories')->nullable()->comment('Communication, Quality, Timeline, etc.');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_feedback');
    }
};
