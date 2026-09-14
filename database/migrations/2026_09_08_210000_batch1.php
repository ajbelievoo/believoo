<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // AI training corrections
        Schema::create('ai_corrections', function (Blueprint $t) {
            $t->id();
            $t->string('question');
            $t->text('wrong_answer');
            $t->text('correct_answer');
            $t->text('admin_note')->nullable();
            $t->boolean('applied')->default(false);
            $t->timestamps();
        });

        // Chat flow steps (simple flow builder)
        Schema::create('chat_flows', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('trigger_keywords'); // comma separated
            $t->integer('step_order')->default(0);
            $t->string('step_type')->default('message'); // message, ask_input, action
            $t->text('content'); // message text or question
            $t->string('next_step')->nullable(); // step name
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // Chat tags
        Schema::create('chat_tags', function (Blueprint $t) {
            $t->id();
            $t->string('session_id');
            $t->string('tag');
            $t->timestamps();
            $t->index('session_id');
        });

        // Internal notes (agent only, client can't see)
        Schema::create('chat_notes', function (Blueprint $t) {
            $t->id();
            $t->string('session_id');
            $t->foreignId('agent_id')->constrained('support_agents');
            $t->text('note');
            $t->timestamps();
            $t->index('session_id');
        });

        // Chat transfers
        Schema::create('chat_transfers', function (Blueprint $t) {
            $t->id();
            $t->string('session_id');
            $t->foreignId('from_agent_id')->constrained('support_agents');
            $t->foreignId('to_agent_id')->constrained('support_agents');
            $t->string('reason')->nullable();
            $t->timestamps();
        });

        // Blocked IPs
        Schema::create('blocked_ips', function (Blueprint $t) {
            $t->id();
            $t->string('ip_address')->unique();
            $t->string('reason')->nullable();
            $t->timestamps();
        });

        // Referral codes
        Schema::create('referral_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('code')->unique();
            $t->integer('uses')->default(0);
            $t->decimal('reward_amount', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // Loyalty points
        Schema::create('loyalty_points', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->integer('points')->default(0);
            $t->string('type')->default('earned'); // earned, redeemed, expired
            $t->string('reference')->nullable(); // order_id, ticket_id etc
            $t->timestamps();
        });

        // SLA breaches
        Schema::create('sla_breaches', function (Blueprint $t) {
            $t->id();
            $t->string('type'); // chat, ticket, callback
            $t->string('reference_id');
            $t->integer('expected_seconds');
            $t->integer('actual_seconds')->nullable();
            $t->boolean('resolved')->default(false);
            $t->timestamps();
        });

        // Push subscriptions (for browser push)
        Schema::create('push_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->string('session_id');
            $t->text('endpoint');
            $t->text('keys'); // p256dh + auth
            $t->timestamps();
            $t->index('session_id');
        });

        // Chat archive (searchable)
        Schema::create('chat_archive', function (Blueprint $t) {
            $t->id();
            $t->string('session_id');
            $t->string('client_name')->nullable();
            $t->string('client_email')->nullable();
            $t->text('transcript');
            $t->string('tags')->nullable();
            $t->integer('rating')->nullable();
            $t->text('summary')->nullable();
            $t->timestamps();
            $t->index(['client_email', 'session_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('ai_corrections');
        Schema::dropIfExists('chat_flows');
        Schema::dropIfExists('chat_tags');
        Schema::dropIfExists('chat_notes');
        Schema::dropIfExists('chat_transfers');
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('loyalty_points');
        Schema::dropIfExists('sla_breaches');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('chat_archive');
    }
};
