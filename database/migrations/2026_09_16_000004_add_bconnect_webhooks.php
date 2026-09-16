<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bconnect_webhooks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies')->cascadeOnDelete();
            $t->string('url');
            $t->string('secret')->nullable();
            $t->json('events')->nullable(); // e.g. ["ticket.created", "invoice.paid"]
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        Schema::create('bconnect_webhook_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('webhook_id')->constrained('bconnect_webhooks')->cascadeOnDelete();
            $t->string('event');
            $t->text('payload');
            $t->unsignedSmallInteger('status_code')->nullable();
            $t->text('response')->nullable();
            $t->unsignedTinyInteger('attempt')->default(1);
            $t->timestamp('delivered_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_webhook_logs');
        Schema::dropIfExists('bconnect_webhooks');
    }
};
