<?php

use Illuminate\Support\Facades\Route;

// Public B-CONNECT routes
Route::domain('bc.believoo.com')->group(function () {
    Route::get('/robots.txt', [\App\Http\Controllers\Bconnect\SeoController::class, 'robots'])->name('bconnect.robots');
    Route::get('/sitemap.xml', [\App\Http\Controllers\Bconnect\SeoController::class, 'sitemap'])->name('bconnect.sitemap');
    Route::get('/sitemaps.xml', function () { return redirect()->to('https://bc.believoo.com/sitemap.xml', 301); });
    Route::get('/', [\App\Http\Controllers\Bconnect\AuthController::class, 'landing'])->name('bconnect.home');
    Route::get('/login', [\App\Http\Controllers\Bconnect\AuthController::class, 'showLogin'])->name('bconnect.login');
    Route::post('/login', [\App\Http\Controllers\Bconnect\AuthController::class, 'login']);
    Route::get('/register', [\App\Http\Controllers\Bconnect\AuthController::class, 'showRegister'])->name('bconnect.register');
    Route::post('/register', [\App\Http\Controllers\Bconnect\AuthController::class, 'register']);
    Route::get('/forgot-password', [\App\Http\Controllers\Bconnect\AuthController::class, 'showForgot'])->name('bconnect.forgot-password');
    Route::post('/forgot-password', [\App\Http\Controllers\Bconnect\AuthController::class, 'sendReset'])->name('bconnect.forgot-password.send');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Bconnect\AuthController::class, 'showReset'])->name('bconnect.password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Bconnect\AuthController::class, 'reset'])->name('bconnect.password.update');
    Route::get('/auth/google', [\App\Http\Controllers\Bconnect\AuthController::class, 'redirectToGoogle'])->name('bconnect.google');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Bconnect\AuthController::class, 'handleGoogleCallback'])->name('bconnect.google.callback');
});

// Authenticated B-CONNECT workspace
Route::domain('bc.believoo.com')->middleware(['auth', 'bconnect', 'bconnect.audit'])->group(function () {

    // Common workspace views
    Route::get('/dashboard', [\App\Http\Controllers\Bconnect\DashboardController::class, 'index'])->name('bconnect.dashboard');
    Route::get('/client', [\App\Http\Controllers\Bconnect\ClientPortalController::class, 'index'])->name('bconnect.client.dashboard');
    Route::get('/company/setup', [\App\Http\Controllers\Bconnect\CompanyController::class, 'setup'])->name('bconnect.company.setup');
    Route::post('/company/setup', [\App\Http\Controllers\Bconnect\CompanyController::class, 'store'])->name('bconnect.company.store');
    Route::get('/company', [\App\Http\Controllers\Bconnect\CompanyController::class, 'index'])->middleware('bconnect.permission:settings.view')->name('bconnect.company');
    Route::get('/members', [\App\Http\Controllers\Bconnect\MemberController::class, 'index'])->middleware('bconnect.permission:members.view')->name('bconnect.members');
    Route::get('/projects', [\App\Http\Controllers\Bconnect\ProjectController::class, 'index'])->middleware('bconnect.permission:projects.view')->name('bconnect.projects.index');
    Route::get('/projects/create', [\App\Http\Controllers\Bconnect\ProjectController::class, 'create'])->middleware('bconnect.permission:projects.manage')->name('bconnect.projects.create');
    Route::get('/projects/{project}', [\App\Http\Controllers\Bconnect\ProjectController::class, 'show'])->middleware('bconnect.permission:projects.view')->name('bconnect.projects.show');
    Route::get('/projects/{project}/edit', [\App\Http\Controllers\Bconnect\ProjectController::class, 'edit'])->middleware('bconnect.permission:projects.manage')->name('bconnect.projects.edit');
    Route::get('/projects/{project}/chat', [\App\Http\Controllers\Bconnect\ChatController::class, 'project'])->middleware('bconnect.permission:chat.use')->name('bconnect.projects.chat');
    Route::get('/projects/{project}/whiteboard', [\App\Http\Controllers\Bconnect\WhiteboardController::class, 'show'])->middleware('bconnect.permission:whiteboard.use')->name('bconnect.projects.whiteboard');
    Route::get('/projects/{project}/kanban', [\App\Http\Controllers\Bconnect\KanbanController::class, 'index'])->middleware('bconnect.permission:tickets.view')->name('bconnect.projects.kanban');
    Route::get('/projects/{project}/time-tracking', [\App\Http\Controllers\Bconnect\TimeTrackingController::class, 'index'])->middleware('bconnect.permission:time_tracking.view')->name('bconnect.projects.time_tracking');
    Route::get('/projects/{project}/sprints', [\App\Http\Controllers\Bconnect\SprintController::class, 'index'])->middleware('bconnect.permission:sprints.view')->name('bconnect.projects.sprints');
    Route::get('/sprints', [\App\Http\Controllers\Bconnect\SprintController::class, 'index'])->middleware('bconnect.permission:sprints.view')->name('bconnect.sprints.index');
    Route::get('/sprints/{sprint}', [\App\Http\Controllers\Bconnect\SprintController::class, 'show'])->middleware('bconnect.permission:sprints.view')->name('bconnect.sprints.show');
    Route::get('/kanban', [\App\Http\Controllers\Bconnect\KanbanController::class, 'index'])->middleware('bconnect.permission:tickets.view')->name('bconnect.kanban');
    Route::get('/time-tracking', [\App\Http\Controllers\Bconnect\TimeTrackingController::class, 'index'])->middleware('bconnect.permission:time_tracking.view')->name('bconnect.time_tracking');
    Route::get('/meetings', [\App\Http\Controllers\Bconnect\MeetingController::class, 'index'])->middleware('bconnect.permission:meetings.view')->name('bconnect.meetings');
    Route::get('/calendar', [\App\Http\Controllers\Bconnect\CalendarController::class, 'index'])->name('bconnect.calendar');
    Route::get('/meetings/{room}', [\App\Http\Controllers\Bconnect\MeetingController::class, 'room'])->middleware('bconnect.permission:meetings.view')->name('bconnect.meeting.room');
    Route::get('/meetings/{room}/recording', [\App\Http\Controllers\Bconnect\MeetingController::class, 'recording'])->name('bconnect.meeting.recording');
    Route::get('/tickets', [\App\Http\Controllers\Bconnect\TicketController::class, 'index'])->middleware('bconnect.permission:tickets.view')->name('bconnect.tickets');
    Route::get('/tickets/{ticket}/edit', [\App\Http\Controllers\Bconnect\TicketController::class, 'edit'])->name('bconnect.tickets.edit')->middleware(['bconnect.role:company_admin|manager', 'bconnect.permission:tickets.manage']);
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Bconnect\TicketController::class, 'show'])->middleware('bconnect.permission:tickets.view')->name('bconnect.tickets.show');
    Route::get('/files', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'index'])->middleware('bconnect.permission:files.view')->name('bconnect.files');
    Route::get('/files/{file}/download', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'download'])->middleware('bconnect.permission:files.view')->name('bconnect.files.download');
    Route::get('/notifications', [\App\Http\Controllers\Bconnect\NotificationController::class, 'index'])->name('bconnect.notifications');
    Route::get('/audit-logs', [\App\Http\Controllers\Bconnect\AuditController::class, 'index'])->name('bconnect.audit');
    Route::get('/support', [\App\Http\Controllers\Bconnect\SupportController::class, 'index'])->name('bconnect.support');
    Route::get('/settings', [\App\Http\Controllers\Bconnect\SettingsController::class, 'index'])->middleware('bconnect.permission:settings.view')->name('bconnect.settings');
    Route::get('/remote', [\App\Http\Controllers\Bconnect\RemoteController::class, 'index'])->middleware('bconnect.permission:remote.use')->name('bconnect.remote');
    Route::get('/remote/agent', [\App\Http\Controllers\Bconnect\AgentController::class, 'index'])->name('bconnect.remote.agent');
    Route::get('/remote/{session}/room', [\App\Http\Controllers\Bconnect\RemoteController::class, 'room'])->middleware('bconnect.permission:remote.use')->name('bconnect.remote.room');
    Route::get('/billing', [\App\Http\Controllers\Bconnect\BillingController::class, 'index'])->middleware('bconnect.permission:billing.view,invoices.view')->name('bconnect.billing');
    Route::get('/billing/upgrade', [\App\Http\Controllers\Bconnect\BillingController::class, 'upgrade'])->middleware('bconnect.permission:billing.manage')->name('bconnect.billing.upgrade');
    Route::get('/invoices/{invoice}/pay', [\App\Http\Controllers\Bconnect\BillingController::class, 'payInvoice'])->middleware('bconnect.permission:invoices.view')->name('bconnect.billing.pay');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\Bconnect\BillingController::class, 'downloadInvoice'])->middleware('bconnect.permission:invoices.view')->name('bconnect.billing.download');

    // Agora token for meetings/remote
    Route::post('/agora/token', [\App\Http\Controllers\Bconnect\AgoraController::class, 'token'])->name('bconnect.agora.token');

    // Common member actions
    Route::post('/chat/messages', [\App\Http\Controllers\Bconnect\ChatController::class, 'store'])->middleware('bconnect.permission:chat.use')->name('bconnect.chat.store');
    Route::post('/chat/messages/{message}/read', [\App\Http\Controllers\Bconnect\ChatController::class, 'markRead'])->name('bconnect.chat.read');
    Route::post('/chat/search', [\App\Http\Controllers\Bconnect\ChatController::class, 'search'])->name('bconnect.chat.search');
    Route::post('/chat/typing', [\App\Http\Controllers\Bconnect\ChatController::class, 'typing'])->name('bconnect.chat.typing');
    Route::post('/tickets', [\App\Http\Controllers\Bconnect\TicketController::class, 'store'])->middleware('bconnect.permission:tickets.create')->name('bconnect.tickets.store');
    Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\Bconnect\TicketController::class, 'comment'])->middleware('bconnect.permission:tickets.view')->name('bconnect.tickets.comment');
    Route::post('/files', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'store'])->middleware('bconnect.permission:files.upload')->name('bconnect.files.store');
    Route::post('/time-entries/start', [\App\Http\Controllers\Bconnect\TimeTrackingController::class, 'start'])->middleware('bconnect.permission:time_tracking.create')->name('bconnect.time_entries.start');
    Route::post('/time-entries/{entry}/stop', [\App\Http\Controllers\Bconnect\TimeTrackingController::class, 'stop'])->middleware('bconnect.permission:time_tracking.create')->name('bconnect.time_entries.stop');
    Route::post('/remote/agent/request', [\App\Http\Controllers\Bconnect\AgentController::class, 'requestBeta'])->name('bconnect.remote.agent.request');
    Route::post('/remote/request', [\App\Http\Controllers\Bconnect\RemoteController::class, 'requestControl'])->name('bconnect.remote.request');
    Route::post('/remote/{session}/respond', [\App\Http\Controllers\Bconnect\RemoteController::class, 'respond'])->name('bconnect.remote.respond');
    Route::post('/remote/{session}/start', [\App\Http\Controllers\Bconnect\RemoteController::class, 'start'])->name('bconnect.remote.start');
    Route::post('/remote/{session}/end', [\App\Http\Controllers\Bconnect\RemoteController::class, 'end'])->name('bconnect.remote.end');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Bconnect\NotificationController::class, 'markRead'])->name('bconnect.notifications.read');
    Route::post('/push/subscribe', [\App\Http\Controllers\Bconnect\PushController::class, 'subscribe'])->name('bconnect.push.subscribe');
    Route::post('/push/test', [\App\Http\Controllers\Bconnect\PushController::class, 'sendTest'])->name('bconnect.push.test');
    Route::post('/logout', [\App\Http\Controllers\Bconnect\AuthController::class, 'logout'])->name('bconnect.logout');

    // Payment gateway return callbacks (called by the same browser)
    Route::post('/billing/callback/razorpay', [\App\Http\Controllers\Bconnect\BillingController::class, 'razorpayCallback'])->name('bconnect.billing.callback.razorpay');
    Route::post('/billing/callback/cashfree', [\App\Http\Controllers\Bconnect\BillingController::class, 'cashfreeCallback'])->name('bconnect.billing.callback.cashfree');

    // Reports
    Route::middleware(['bconnect.role:company_admin|manager', 'bconnect.permission:reports.view'])->group(function () {
        Route::get('/reports', [\App\Http\Controllers\Bconnect\ReportsController::class, 'index'])->name('bconnect.reports');
    });

    // Remote access
    Route::middleware(['bconnect.role:company_admin|manager|developer', 'bconnect.permission:remote.use'])->group(function () {
        Route::post('/remote/request', [\App\Http\Controllers\Bconnect\RemoteController::class, 'requestControl'])->name('bconnect.remote.request');
        Route::post('/remote/{session}/respond', [\App\Http\Controllers\Bconnect\RemoteController::class, 'respond'])->name('bconnect.remote.respond');
        Route::post('/remote/{session}/start', [\App\Http\Controllers\Bconnect\RemoteController::class, 'start'])->name('bconnect.remote.start');
        Route::post('/remote/{session}/end', [\App\Http\Controllers\Bconnect\RemoteController::class, 'end'])->name('bconnect.remote.end');
    });

    // Company admin only
    Route::middleware(['bconnect.role:company_admin'])->group(function () {
        // Member management
        Route::middleware(['bconnect.permission:members.manage'])->group(function () {
            Route::post('/members', [\App\Http\Controllers\Bconnect\MemberController::class, 'store'])->name('bconnect.members.store');
            Route::put('/members/{member}', [\App\Http\Controllers\Bconnect\MemberController::class, 'update'])->name('bconnect.members.update');
            Route::delete('/members/{member}', [\App\Http\Controllers\Bconnect\MemberController::class, 'destroy'])->name('bconnect.members.destroy');
        });

        // Sprint management
        Route::middleware(['bconnect.permission:sprints.manage'])->group(function () {
            Route::post('/sprints', [\App\Http\Controllers\Bconnect\SprintController::class, 'store'])->name('bconnect.sprints.store');
            Route::put('/sprints/{sprint}', [\App\Http\Controllers\Bconnect\SprintController::class, 'update'])->name('bconnect.sprints.update');
            Route::delete('/sprints/{sprint}', [\App\Http\Controllers\Bconnect\SprintController::class, 'destroy'])->name('bconnect.sprints.destroy');
        });

        // Invoice management
        Route::middleware(['bconnect.permission:invoices.create,invoices.manage'])->group(function () {
            Route::post('/invoices', [\App\Http\Controllers\Bconnect\BillingController::class, 'storeInvoice'])->name('bconnect.invoices.store');
            Route::get('/billing/billable-time', [\App\Http\Controllers\Bconnect\BillingController::class, 'billableTime'])->name('bconnect.billing.billable_time');
            Route::post('/billing/invoices/from-time', [\App\Http\Controllers\Bconnect\BillingController::class, 'invoiceFromTime'])->name('bconnect.billing.invoice_from_time');
            Route::post('/invoices/{invoice}/mark-paid', [\App\Http\Controllers\Bconnect\BillingController::class, 'markPaid'])->name('bconnect.billing.markPaid');
        });

        // Billing / subscription management
        Route::middleware(['bconnect.permission:billing.manage'])->group(function () {
            Route::post('/billing/upgrade', [\App\Http\Controllers\Bconnect\BillingController::class, 'processUpgrade'])->name('bconnect.billing.upgrade.process');
            Route::post('/billing/cancel', [\App\Http\Controllers\Bconnect\BillingController::class, 'cancelSubscription'])->name('bconnect.billing.cancel');
            Route::post('/billing/renew', [\App\Http\Controllers\Bconnect\BillingController::class, 'renewSubscription'])->name('bconnect.billing.renew');
        });

        // Settings
        Route::middleware(['bconnect.permission:settings.manage'])->group(function () {
            Route::put('/company', [\App\Http\Controllers\Bconnect\CompanyController::class, 'update'])->name('bconnect.company.update');
            Route::post('/company/apply-domain', [\App\Http\Controllers\Bconnect\CompanyController::class, 'applyDomain'])->name('bconnect.company.apply-domain');
            Route::post('/settings', [\App\Http\Controllers\Bconnect\SettingsController::class, 'update'])->name('bconnect.settings.update');
        });
    });

    // Admin / manager (company_admin or manager)
    Route::middleware(['bconnect.role:company_admin|manager'])->group(function () {
        // Project management
        Route::middleware(['bconnect.permission:projects.manage'])->group(function () {
            Route::post('/projects', [\App\Http\Controllers\Bconnect\ProjectController::class, 'store'])->name('bconnect.projects.store');
            Route::put('/projects/{project}', [\App\Http\Controllers\Bconnect\ProjectController::class, 'update'])->name('bconnect.projects.update');
            Route::delete('/projects/{project}', [\App\Http\Controllers\Bconnect\ProjectController::class, 'destroy'])->name('bconnect.projects.destroy');
        });

        // Ticket management
        Route::middleware(['bconnect.permission:tickets.manage'])->group(function () {
            Route::put('/tickets/{ticket}', [\App\Http\Controllers\Bconnect\TicketController::class, 'update'])->name('bconnect.tickets.update');
            Route::delete('/tickets/{ticket}', [\App\Http\Controllers\Bconnect\TicketController::class, 'destroy'])->name('bconnect.tickets.destroy');
            Route::put('/tickets/{ticket}/status', [\App\Http\Controllers\Bconnect\TicketController::class, 'updateStatus'])->name('bconnect.tickets.status');
            Route::post('/kanban/reorder', [\App\Http\Controllers\Bconnect\KanbanController::class, 'reorder'])->name('bconnect.kanban.reorder');
            Route::post('/tickets/{ticket}/kanban-status', [\App\Http\Controllers\Bconnect\KanbanController::class, 'updateStatus'])->name('bconnect.tickets.kanban_status');
        });

        // Time tracking
        Route::middleware(['bconnect.permission:time_tracking.create'])->group(function () {
            Route::post('/time-entries', [\App\Http\Controllers\Bconnect\TimeTrackingController::class, 'store'])->name('bconnect.time_entries.store');
        });

        // Meeting management
        Route::middleware(['bconnect.permission:meetings.create,meetings.manage'])->group(function () {
            Route::post('/meetings', [\App\Http\Controllers\Bconnect\MeetingController::class, 'store'])->name('bconnect.meetings.store');
            Route::post('/meetings/{room}/end', [\App\Http\Controllers\Bconnect\MeetingController::class, 'endMeeting'])->name('bconnect.meeting.end');
            Route::post('/meetings/{room}/record/start', [\App\Http\Controllers\Bconnect\AgoraCloudRecordingController::class, 'start'])->name('bconnect.meeting.record.start');
            Route::post('/meetings/{room}/record/stop', [\App\Http\Controllers\Bconnect\AgoraCloudRecordingController::class, 'stop'])->name('bconnect.meeting.record.stop');
            Route::post('/meetings/{room}/transcript', [\App\Http\Controllers\Bconnect\MeetingTranscriptController::class, 'saveTranscript'])->name('bconnect.meeting.transcript.save');
            Route::post('/meetings/{room}/audio', [\App\Http\Controllers\Bconnect\MeetingTranscriptController::class, 'uploadAudio'])->name('bconnect.meeting.audio.upload');
            Route::post('/meetings/{room}/notes', [\App\Http\Controllers\Bconnect\MeetingTranscriptController::class, 'generateNotes'])->name('bconnect.meeting.notes.generate');
            Route::get('/meetings/{room}/notes', [\App\Http\Controllers\Bconnect\MeetingTranscriptController::class, 'showNotes'])->name('bconnect.meeting.notes');
        });
    });

    // Team leads / developers can edit whiteboards and delete their own files
    Route::middleware(['bconnect.role:company_admin|manager|developer'])->group(function () {
        Route::post('/projects/{project}/whiteboard', [\App\Http\Controllers\Bconnect\WhiteboardController::class, 'update'])->middleware('bconnect.permission:whiteboard.use')->name('bconnect.projects.whiteboard.update');
        Route::delete('/files/{file}', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'destroy'])->middleware('bconnect.permission:files.delete')->name('bconnect.files.destroy');
    });
});

// Super Admin routes on main domain /admin/bconnect
Route::prefix('admin/bconnect')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'index'])->name('admin.bconnect.index');
    Route::get('/companies/{company}', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'company'])->name('admin.bconnect.company');
    Route::put('/companies/{company}/plan', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'updatePlan'])->name('admin.bconnect.company.plan');
    Route::put('/companies/{company}/toggle', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'toggleActive'])->name('admin.bconnect.company.toggle');
    Route::post('/companies/{company}/domain', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'applyDomain'])->name('admin.bconnect.company.domain');
    Route::post('/companies/{company}/notify', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'notifyAdmins'])->name('admin.bconnect.company.notify');
});
