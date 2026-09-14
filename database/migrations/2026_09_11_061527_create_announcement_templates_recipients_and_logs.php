<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->text('message')->nullable();
            $table->text('message_hi')->nullable();
            $table->string('type', 20)->default('info');
            $table->string('locale', 10)->default('en');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('announcement_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->string('email');
            $table->string('product', 20)->nullable(); // believoo, bconnect, ghc
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->string('click_url')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'email']);
        });

        Schema::create('announcement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
            $table->string('email')->nullable();
            $table->string('product', 20)->nullable();
            $table->string('level', 20)->default('info'); // info, warning, error
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('email')->unique();
            $table->boolean('announcements')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_preferences');
        Schema::dropIfExists('announcement_logs');
        Schema::dropIfExists('announcement_recipients');
        Schema::dropIfExists('announcement_templates');
    }
};
