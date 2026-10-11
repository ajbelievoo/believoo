<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 6 — public content (blog/KB/portal), canned responses, meeting
// waiting-room/attendance, billing extras (quotes, credit notes, recurring,
// tax), per-company remote policies.
return new class extends Migration
{
    public function up(): void
    {
        // ── Blog ──
        Schema::create('bconnect_blog_posts', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('excerpt', 500)->nullable();
            $t->longText('body')->nullable();
            $t->string('cover')->nullable();
            $t->boolean('published')->default(false);
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        // ── Knowledge base ──
        Schema::create('bconnect_kb_articles', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->string('category', 60)->default('General');
            $t->longText('body')->nullable();
            $t->boolean('published')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();
        });

        // ── Canned responses for ticket replies ──
        Schema::create('bconnect_canned_responses', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('title');
            $t->text('body');
            $t->timestamps();
        });

        // ── Meeting participants (waiting room + attendance) ──
        Schema::create('bconnect_meeting_participants', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('meeting_id')->index();
            $t->unsignedBigInteger('member_id')->nullable()->index();
            $t->string('name', 120);
            $t->string('status', 20)->default('joined'); // waiting|joined|left|denied
            $t->timestamp('joined_at')->nullable();
            $t->timestamp('left_at')->nullable();
            $t->timestamps();
        });

        // ── Recurring invoices ──
        Schema::create('bconnect_recurring_invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('client_id');
            $t->decimal('amount', 12, 2);
            $t->string('currency', 8)->default('INR');
            $t->text('description')->nullable();
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->string('interval', 12)->default('monthly'); // monthly|quarterly|yearly
            $t->date('next_run');
            $t->unsignedBigInteger('last_invoice_id')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        // ── Quotes / estimates ──
        Schema::create('bconnect_quotes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('client_id');
            $t->string('quote_number')->unique();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 8)->default('INR');
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->string('status', 20)->default('draft'); // draft|sent|accepted|declined|converted
            $t->text('description')->nullable();
            $t->json('metadata')->nullable();
            $t->unsignedBigInteger('converted_invoice_id')->nullable();
            $t->timestamps();
        });

        // ── Credit notes ──
        Schema::create('bconnect_credit_notes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('invoice_id')->index();
            $t->string('credit_number')->unique();
            $t->decimal('amount', 12, 2);
            $t->text('reason')->nullable();
            $t->timestamps();
        });

        // ── Ticket extensions: public portal, tags, CSAT, merge ──
        Schema::table('bconnect_tickets', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_tickets', 'public_token')) {
                $t->string('public_token', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'reporter_name')) {
                $t->string('reporter_name', 120)->nullable();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'reporter_email')) {
                $t->string('reporter_email', 190)->nullable();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'source')) {
                $t->string('source', 20)->default('internal'); // internal|portal|email
            }
            if (!Schema::hasColumn('bconnect_tickets', 'tags')) {
                $t->json('tags')->nullable();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'csat_score')) {
                $t->unsignedTinyInteger('csat_score')->nullable();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'csat_comment')) {
                $t->string('csat_comment', 500)->nullable();
            }
            if (!Schema::hasColumn('bconnect_tickets', 'merged_into')) {
                $t->unsignedBigInteger('merged_into')->nullable();
            }
        });

        // internal notes on ticket comments
        Schema::table('bconnect_ticket_comments', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_ticket_comments', 'is_internal')) {
                $t->boolean('is_internal')->default(false);
            }
        });

        // ── Meetings: waiting room, password, recurrence ──
        Schema::table('bconnect_meetings', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_meetings', 'password')) {
                $t->string('password', 60)->nullable();
            }
            if (!Schema::hasColumn('bconnect_meetings', 'waiting_room')) {
                $t->boolean('waiting_room')->default(false);
            }
            if (!Schema::hasColumn('bconnect_meetings', 'recurrence')) {
                $t->string('recurrence', 12)->nullable(); // daily|weekly
            }
        });

        // ── Invoices: tax rate ──
        Schema::table('bconnect_invoices', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_invoices', 'tax_rate')) {
                $t->decimal('tax_rate', 5, 2)->default(0);
            }
        });

        // ── Company remote policies ──
        Schema::table('bconnect_companies', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_companies', 'remote_policies')) {
                $t->json('remote_policies')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_blog_posts');
        Schema::dropIfExists('bconnect_kb_articles');
        Schema::dropIfExists('bconnect_canned_responses');
        Schema::dropIfExists('bconnect_meeting_participants');
        Schema::dropIfExists('bconnect_recurring_invoices');
        Schema::dropIfExists('bconnect_quotes');
        Schema::dropIfExists('bconnect_credit_notes');

        Schema::table('bconnect_tickets', function (Blueprint $t) {
            $t->dropColumn(['public_token', 'reporter_name', 'reporter_email', 'source', 'tags', 'csat_score', 'csat_comment', 'merged_into']);
        });
        Schema::table('bconnect_ticket_comments', fn (Blueprint $t) => $t->dropColumn('is_internal'));
        Schema::table('bconnect_meetings', fn (Blueprint $t) => $t->dropColumn(['password', 'waiting_room', 'recurrence']));
        Schema::table('bconnect_invoices', fn (Blueprint $t) => $t->dropColumn('tax_rate'));
        Schema::table('bconnect_companies', fn (Blueprint $t) => $t->dropColumn('remote_policies'));
    }
};
