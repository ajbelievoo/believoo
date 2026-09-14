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
    Route::get('/dashboard', [\App\Http\Controllers\Bconnect\DashboardController::class, 'index'])->name('bconnect.dashboard');

    // Company / Enterprise
    Route::get('/company', [\App\Http\Controllers\Bconnect\CompanyController::class, 'index'])->name('bconnect.company');
    Route::put('/company', [\App\Http\Controllers\Bconnect\CompanyController::class, 'update'])->name('bconnect.company.update');
    Route::post('/company/apply-domain', [\App\Http\Controllers\Bconnect\CompanyController::class, 'applyDomain'])->name('bconnect.company.apply-domain');
    Route::get('/company/setup', [\App\Http\Controllers\Bconnect\CompanyController::class, 'setup'])->name('bconnect.company.setup');
    Route::post('/company/setup', [\App\Http\Controllers\Bconnect\CompanyController::class, 'store'])->name('bconnect.company.store');

    // Members / Roles
    Route::get('/members', [\App\Http\Controllers\Bconnect\MemberController::class, 'index'])->name('bconnect.members');
    Route::post('/members', [\App\Http\Controllers\Bconnect\MemberController::class, 'store'])->name('bconnect.members.store');
    Route::put('/members/{member}', [\App\Http\Controllers\Bconnect\MemberController::class, 'update'])->name('bconnect.members.update');
    Route::delete('/members/{member}', [\App\Http\Controllers\Bconnect\MemberController::class, 'destroy'])->name('bconnect.members.destroy');

    // Projects
    Route::resource('projects', \App\Http\Controllers\Bconnect\ProjectController::class)->names('bconnect.projects');
    Route::get('/projects/{project}/chat', [\App\Http\Controllers\Bconnect\ChatController::class, 'project'])->name('bconnect.projects.chat');
    Route::get('/projects/{project}/whiteboard', [\App\Http\Controllers\Bconnect\WhiteboardController::class, 'show'])->name('bconnect.projects.whiteboard');
    Route::post('/projects/{project}/whiteboard', [\App\Http\Controllers\Bconnect\WhiteboardController::class, 'update'])->name('bconnect.projects.whiteboard.update');

    // Chat messages
    Route::post('/chat/messages', [\App\Http\Controllers\Bconnect\ChatController::class, 'store'])->name('bconnect.chat.store');

    // Meetings
    Route::get('/meetings', [\App\Http\Controllers\Bconnect\MeetingController::class, 'index'])->name('bconnect.meetings');
    Route::post('/agora/token', [\App\Http\Controllers\Bconnect\AgoraController::class, 'token'])->name('bconnect.agora.token');
    Route::post('/meetings', [\App\Http\Controllers\Bconnect\MeetingController::class, 'store'])->name('bconnect.meetings.store');
    Route::get('/meetings/{room}', [\App\Http\Controllers\Bconnect\MeetingController::class, 'room'])->name('bconnect.meeting.room');
    Route::post('/meetings/{room}/end', [\App\Http\Controllers\Bconnect\MeetingController::class, 'endMeeting'])->name('bconnect.meeting.end');
    Route::post('/meetings/{room}/record/start', [\App\Http\Controllers\Bconnect\AgoraCloudRecordingController::class, 'start'])->name('bconnect.meeting.record.start');
    Route::post('/meetings/{room}/record/stop', [\App\Http\Controllers\Bconnect\AgoraCloudRecordingController::class, 'stop'])->name('bconnect.meeting.record.stop');

    // Tickets / Bugs
    Route::get('/tickets', [\App\Http\Controllers\Bconnect\TicketController::class, 'index'])->name('bconnect.tickets');
    Route::post('/tickets', [\App\Http\Controllers\Bconnect\TicketController::class, 'store'])->name('bconnect.tickets.store');
    Route::get('/tickets/{ticket}', [\App\Http\Controllers\Bconnect\TicketController::class, 'show'])->name('bconnect.tickets.show');
    Route::post('/tickets/{ticket}/comments', [\App\Http\Controllers\Bconnect\TicketController::class, 'comment'])->name('bconnect.tickets.comment');
    Route::put('/tickets/{ticket}/status', [\App\Http\Controllers\Bconnect\TicketController::class, 'updateStatus'])->name('bconnect.tickets.status');

    // Remote Desktop
    Route::get('/remote', [\App\Http\Controllers\Bconnect\RemoteController::class, 'index'])->name('bconnect.remote');
    Route::get('/remote/agent', [\App\Http\Controllers\Bconnect\AgentController::class, 'index'])->name('bconnect.remote.agent');
    Route::post('/remote/agent/request', [\App\Http\Controllers\Bconnect\AgentController::class, 'requestBeta'])->name('bconnect.remote.agent.request');
    Route::post('/remote/request', [\App\Http\Controllers\Bconnect\RemoteController::class, 'requestControl'])->name('bconnect.remote.request');
    Route::post('/remote/{session}/respond', [\App\Http\Controllers\Bconnect\RemoteController::class, 'respond'])->name('bconnect.remote.respond');
    Route::get('/remote/{session}/room', [\App\Http\Controllers\Bconnect\RemoteController::class, 'room'])->name('bconnect.remote.room');

    // Billing / Invoices
    Route::get('/billing', [\App\Http\Controllers\Bconnect\BillingController::class, 'index'])->name('bconnect.billing');
    Route::post('/invoices', [\App\Http\Controllers\Bconnect\BillingController::class, 'storeInvoice'])->name('bconnect.invoices.store');
    Route::get('/invoices/{invoice}/pay', [\App\Http\Controllers\Bconnect\BillingController::class, 'payInvoice'])->name('bconnect.billing.pay');
    Route::post('/invoices/{invoice}/mark-paid', [\App\Http\Controllers\Bconnect\BillingController::class, 'markPaid'])->name('bconnect.billing.markPaid');
    Route::post('/billing/callback/razorpay', [\App\Http\Controllers\Bconnect\BillingController::class, 'razorpayCallback'])->name('bconnect.billing.callback.razorpay');
    Route::post('/billing/callback/cashfree', [\App\Http\Controllers\Bconnect\BillingController::class, 'cashfreeCallback'])->name('bconnect.billing.callback.cashfree');

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Bconnect\NotificationController::class, 'index'])->name('bconnect.notifications');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\Bconnect\NotificationController::class, 'markRead'])->name('bconnect.notifications.read');

    // File Manager
    Route::get('/files', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'index'])->name('bconnect.files');
    Route::post('/files', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'store'])->name('bconnect.files.store');
    Route::delete('/files/{file}', [\App\Http\Controllers\Bconnect\FileManagerController::class, 'destroy'])->name('bconnect.files.destroy');

    // Push Notifications
    Route::post('/push/subscribe', [\App\Http\Controllers\Bconnect\PushController::class, 'subscribe'])->name('bconnect.push.subscribe');
    Route::post('/push/test', [\App\Http\Controllers\Bconnect\PushController::class, 'sendTest'])->name('bconnect.push.test');

    // Audit Logs
    Route::get('/audit-logs', [\App\Http\Controllers\Bconnect\AuditController::class, 'index'])->name('bconnect.audit');

    // Support
    Route::get('/support', [\App\Http\Controllers\Bconnect\SupportController::class, 'index'])->name('bconnect.support');

    // Settings
    Route::get('/settings', [\App\Http\Controllers\Bconnect\SettingsController::class, 'index'])->name('bconnect.settings');
    Route::post('/settings', [\App\Http\Controllers\Bconnect\SettingsController::class, 'update'])->name('bconnect.settings.update');

    Route::post('/logout', [\App\Http\Controllers\Bconnect\AuthController::class, 'logout'])->name('bconnect.logout');
});

// Super Admin routes on main domain /admin/bconnect
Route::prefix('admin/bconnect')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'index'])->name('admin.bconnect.index');
    Route::get('/companies/{company}', [\App\Http\Controllers\Admin\BconnectAdminController::class, 'company'])->name('admin.bconnect.company');
});
