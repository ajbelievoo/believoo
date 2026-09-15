<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subject');
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->text('content_html')->nullable();
            $table->text('content_text')->nullable();
            $table->string('status', 20)->default('draft'); // draft, scheduled, sending, sent, paused
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('segment', 50)->default('all'); // all, active, clients, newsletter
            $table->unsignedBigInteger('sent_count')->default(0);
            $table->unsignedBigInteger('opened_count')->default(0);
            $table->unsignedBigInteger('clicked_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
