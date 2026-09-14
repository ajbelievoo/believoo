<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // B-Connect companies
        Schema::create('bconnect_companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('logo')->nullable();
            $t->string('domain')->nullable()->unique();
            $t->string('plan')->default('free'); // free, pro, enterprise
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // Company memberships (users with role)
        Schema::create('bconnect_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('user_id')->constrained();
            $t->string('role')->default('developer'); // super_admin, company_admin, manager, developer, client
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        // B-Connect projects
        Schema::create('bconnect_projects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->string('name');
            $t->text('description')->nullable();
            $t->foreignId('client_id')->nullable()->constrained('bconnect_members');
            $t->string('status')->default('active');
            $t->timestamps();
        });

        // B-Connect tickets/bugs
        Schema::create('bconnect_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('project_id')->constrained('bconnect_projects');
            $t->foreignId('reporter_id')->constrained('bconnect_members');
            $t->foreignId('assignee_id')->nullable()->constrained('bconnect_members');
            $t->string('title');
            $t->text('description');
            $t->string('type')->default('bug'); // bug, feature, task, support
            $t->string('status')->default('open'); // open, in-progress, testing, resolved, closed
            $t->string('priority')->default('medium');
            $t->text('ai_summary')->nullable();
            $t->json('attachments')->nullable();
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();
        });

        // Ticket comments / discussion
        Schema::create('bconnect_ticket_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_id')->constrained('bconnect_tickets');
            $t->foreignId('member_id')->constrained('bconnect_members');
            $t->text('message');
            $t->json('attachments')->nullable();
            $t->timestamps();
        });

        // Meetings / conference rooms
        Schema::create('bconnect_meetings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('project_id')->nullable()->constrained('bconnect_projects');
            $t->foreignId('created_by')->constrained('bconnect_members');
            $t->string('room_id')->unique();
            $t->string('title');
            $t->text('ai_summary')->nullable();
            $t->json('action_items')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->timestamps();
        });

        // Remote desktop sessions
        Schema::create('bconnect_remote_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('requested_by')->constrained('bconnect_members');
            $t->foreignId('target_id')->nullable()->constrained('bconnect_members');
            $t->string('session_code')->unique();
            $t->string('status')->default('pending'); // pending, active, rejected, ended
            $t->string('permission')->default('view'); // view, control
            $t->timestamp('started_at')->nullable();
            $t->timestamp('ended_at')->nullable();
            $t->timestamps();
        });

        // Subscriptions & invoices
        Schema::create('bconnect_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('client_id')->nullable()->constrained('bconnect_members');
            $t->string('invoice_number')->unique();
            $t->decimal('amount', 12, 2);
            $t->string('currency')->default('INR');
            $t->string('status')->default('pending'); // pending, paid, overdue
            $t->text('description')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        // Notifications
        Schema::create('bconnect_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('member_id')->constrained('bconnect_members');
            $t->string('type');
            $t->string('title');
            $t->text('message');
            $t->boolean('is_read')->default(false);
            $t->string('url')->nullable();
            $t->timestamps();
        });

        // Audit logs
        Schema::create('bconnect_audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('member_id')->constrained('bconnect_members');
            $t->string('action');
            $t->string('ip_address');
            $t->text('details')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('bconnect_audit_logs');
        Schema::dropIfExists('bconnect_notifications');
        Schema::dropIfExists('bconnect_invoices');
        Schema::dropIfExists('bconnect_remote_sessions');
        Schema::dropIfExists('bconnect_meetings');
        Schema::dropIfExists('bconnect_ticket_comments');
        Schema::dropIfExists('bconnect_tickets');
        Schema::dropIfExists('bconnect_projects');
        Schema::dropIfExists('bconnect_members');
        Schema::dropIfExists('bconnect_companies');
    }
};
