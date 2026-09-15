<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\AgreementController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AmcSubscriptionController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\PortfolioController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\UserHostingController;
use App\Http\Controllers\Admin\ExchangeRateController;
use App\Http\Controllers\Admin\StreamingPlanController;
use App\Http\Controllers\Admin\StreamAnalyticsController;
use App\Http\Controllers\Admin\MailTestController;
use App\Http\Controllers\Admin\GhcController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AiChatController;
use App\Http\Controllers\Admin\ChurnRiskController;
use App\Http\Controllers\Admin\EmailCampaignController;
use App\Http\Controllers\Admin\ApiKeyController;

Route::middleware(['auth', 'admin', '2fa', 'log.admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard - Only one route needed
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [AdminController::class, 'dashboard']);

    // Unified brand management hub
    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');

    // GHC (Go Host Cloud) unified admin
    Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->prefix('ghc')->name('ghc.')->group(function () {
        Route::get('/', [GhcController::class, 'index'])->name('index');
        Route::post('/sync', [GhcController::class, 'syncCatalog'])->name('sync');
        Route::post('/orders/{id}/retry', [GhcController::class, 'retryOrder'])->name('orders.retry');
        Route::post('/subscriptions/{id}/action', [GhcController::class, 'subscriptionAction'])->name('subscriptions.action');
        Route::post('/credentials', [GhcController::class, 'updateCredentials'])->name('credentials.update');
        Route::post('/settings', [GhcController::class, 'updateSettings'])->name('settings.update');
        Route::post('/tld', [GhcController::class, 'updateTld'])->name('tld.update');
        Route::post('/plan/{planCode}', [GhcController::class, 'updatePlan'])->name('plan.update');
        Route::post('/margin', [GhcController::class, 'updateMargin'])->name('margin.update');
        Route::post('/ticket/{id}', [GhcController::class, 'updateTicket'])->name('ticket.update');
        Route::post('/user/{id}', [GhcController::class, 'updateUser'])->name('user.update');
    });

    // Users
    Route::resource('users', UserController::class);
    Route::post('users/{user}/password', [UserController::class, 'changePassword'])->name('users.password');
    Route::post('users/{user}/wallet-adjust', [UserController::class, 'adjustWallet'])->name('users.wallet-adjust');

    // Orders
    Route::resource('orders', OrderController::class);
    Route::post('orders/{order}/update-status', [OrderController::class, 'updateStatus'])->name('orders.update-status');

    // Hostings
    Route::resource('hostings', UserHostingController::class)->except(['create', 'store']);
    Route::post('hostings/{hosting}/recreate-vm', [UserHostingController::class, 'recreateVm'])->name('hostings.recreate-vm');

    // Agreements
    Route::resource('agreements', AgreementController::class);
    Route::post('agreements/{agreement}/update-status', [AgreementController::class, 'updateStatus'])->name('agreements.update-status');

    // Agreement Requests (Project Requests from Clients)
    Route::get('agreement-requests', [AgreementController::class, 'requests'])->name('agreement-requests.index');
    Route::get('agreement-requests/{agreementRequest}', [AgreementController::class, 'showRequest'])->name('agreement-requests.show');
    Route::post('agreement-requests/{agreementRequest}/update-status', [AgreementController::class, 'updateRequestStatus'])->name('agreement-requests.update-status');

    // Invoices
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/update-status', [InvoiceController::class, 'updateStatus'])->name('invoices.update-status');

    // Projects
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/update-status', [ProjectController::class, 'updateStatus'])->name('projects.update-status');

    // AI Analytics & Training
    Route::get('ai-analytics', [\App\Http\Controllers\Admin\AiAnalyticsController::class, 'index'])->name('ai-analytics.index');
    Route::get('ai-training', [\App\Http\Controllers\Admin\AiTrainingController::class, 'index'])->name('ai-training.index');
    Route::put('ai-training/{correction}', [\App\Http\Controllers\Admin\AiTrainingController::class, 'update'])->name('ai-training.update');
    Route::post('ai-training/{correction}/apply', [\App\Http\Controllers\Admin\AiTrainingController::class, 'apply'])->name('ai-training.apply');
    Route::delete('ai-training/{correction}', [\App\Http\Controllers\Admin\AiTrainingController::class, 'destroy'])->name('ai-training.destroy');
    Route::resource('chat-flows', \App\Http\Controllers\Admin\ChatFlowController::class)->except('show');
    Route::get('agent-leaderboard', [\App\Http\Controllers\Admin\AgentLeaderboardController::class, 'index'])->name('agent-leaderboard.index');
    Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('conversions', [\App\Http\Controllers\Admin\ConversionController::class, 'index'])->name('conversions.index');
    Route::get('chat-archive', [\App\Http\Controllers\Admin\ChatArchiveController::class, 'index'])->name('chat-archive.index');
    Route::get('chat-archive/export', [\App\Http\Controllers\Admin\ChatArchiveController::class, 'export'])->name('chat-archive.export');
    Route::get('referrals', [\App\Http\Controllers\Admin\ReferralController::class, 'index'])->name('referrals.index');
    Route::post('referrals/{user}/points', [\App\Http\Controllers\Admin\ReferralController::class, 'updatePoints'])->name('referrals.points');
    Route::get('security', [\App\Http\Controllers\Admin\SecurityController::class, 'index'])->name('security.index');
    Route::post('security/block', [\App\Http\Controllers\Admin\SecurityController::class, 'block'])->name('security.block');
    Route::delete('security/{ip}/unblock', [\App\Http\Controllers\Admin\SecurityController::class, 'unblock'])->name('security.unblock');

    // AI Knowledge Base
    Route::resource('knowledge', \App\Http\Controllers\Admin\KnowledgeController::class)->except('show');

    // Support Team (live chat agents)
    Route::resource('support-agents', \App\Http\Controllers\Admin\SupportAgentController::class)->except('show');
    Route::post('support-agents/{supportAgent}/toggle-online', [\App\Http\Controllers\Admin\SupportAgentController::class, 'toggleOnline'])->name('support-agents.toggle-online');
    Route::get('support-agents/{supportAgent}/performance', [\App\Http\Controllers\Admin\SupportAgentController::class, 'performance'])->name('support-agents.performance');

    // Tickets
    Route::resource('tickets', TicketController::class);
    Route::post('tickets/{ticket}/reply', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('tickets/{ticket}/update-status', [TicketController::class, 'updateStatus'])->name('tickets.update-status');

    // Services
    Route::resource('services', ServiceController::class);

    // Blog Posts
    Route::resource('posts', App\Http\Controllers\Admin\PostController::class);

    // AMC Subscriptions
    Route::resource('amc-subscriptions', AmcSubscriptionController::class);

    // Leads
    Route::resource('leads', LeadController::class);
    Route::post('leads/{lead}/update-status', [LeadController::class, 'updateStatus'])->name('leads.update-status');

    // Email Accounts (believoo.com mailboxes)
    Route::resource('mailboxes', \App\Http\Controllers\Admin\MailboxController::class)->except(['show']);
    Route::get('mail/deliverability', [\App\Http\Controllers\Admin\MailDeliverabilityController::class, 'index'])->name('mail.deliverability');

    // Portfolio
    Route::resource('portfolio', PortfolioController::class);

    // Store
    Route::resource('stores', StoreController::class);

    // Testimonials
    Route::resource('testimonials', \App\Http\Controllers\Admin\TestimonialController::class);
    Route::post('testimonials/{testimonial}/toggle', [\App\Http\Controllers\Admin\TestimonialController::class, 'toggle'])->name('testimonials.toggle');

    // Teams
    Route::resource('teams', TeamController::class);

    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('test-mail', [MailTestController::class, 'send'])->name('settings.test-mail');
    Route::redirect('site-settings', 'settings');

    // Exchange Rates
    Route::get('exchange-rates', [ExchangeRateController::class, 'index'])->name('exchange-rates.index');
    Route::put('exchange-rates', [ExchangeRateController::class, 'update'])->name('exchange-rates.update');
    Route::post('exchange-rates/fetch-live', [ExchangeRateController::class, 'fetchLiveRate'])->name('exchange-rates.fetch-live');

    // Streaming Plans
    Route::resource('streaming-plans', StreamingPlanController::class);
    Route::patch('streaming-plans/{streamingPlan}/toggle-status', [StreamingPlanController::class, 'toggleStatus'])->name('streaming-plans.toggle-status');
    Route::post('streaming-plans/{streamingPlan}/duplicate', [StreamingPlanController::class, 'duplicate'])->name('streaming-plans.duplicate');

    // Proxmox VM Management
    Route::prefix('proxmox')->name('proxmox.')->group(function () {
        Route::get('/vms', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'index'])->name('vms.index');
        Route::get('/vms/create', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'create'])->name('vms.create');
        Route::get('/vms/current', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'currentNode'])->name('vms.current');
        Route::post('/vms', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'store'])->name('vms.store');
        Route::get('/vms/{vmid}', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'show'])->name('vms.show');
        
        // VM Actions
        Route::post('/vms/{vmid}/start', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'start'])->name('vms.start');
        Route::post('/vms/{vmid}/stop', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'stop'])->name('vms.stop');
        Route::post('/vms/{vmid}/shutdown', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'shutdown'])->name('vms.shutdown');
        Route::post('/vms/{vmid}/reboot', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'reboot'])->name('vms.reboot');
        Route::delete('/vms/{vmid}', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'destroy'])->name('vms.destroy');
        
        // API for real-time status
        Route::get('/vms/{vmid}/api-status', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'apiStatus'])->name('vms.api-status');
        
        // Fetch panel password on demand (not embedded in page source)
        Route::get('/vms/{vmid}/panel-password', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'panelPassword'])->name('vms.panel-password');
        
        // Manual IP assignment
        Route::post('/vms/{vmid}/assign-ip', [App\Http\Controllers\Admin\ProxmoxVmController::class, 'assignIp'])->name('vms.assign-ip');
    });

    // Domain Provider Management
    Route::prefix('domain-providers')->name('domain-providers.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DomainProviderController::class, 'index'])->name('index');
        Route::get('/test-all', [App\Http\Controllers\Admin\DomainProviderController::class, 'testAll'])->name('test-all');
        Route::get('/{provider}', [App\Http\Controllers\Admin\DomainProviderController::class, 'show'])->name('show');
        Route::get('/{provider}/edit', [App\Http\Controllers\Admin\DomainProviderController::class, 'edit'])->name('edit');
        Route::put('/{provider}', [App\Http\Controllers\Admin\DomainProviderController::class, 'update'])->name('update');
        Route::patch('/{provider}/activate', [App\Http\Controllers\Admin\DomainProviderController::class, 'activate'])->name('activate');
        Route::patch('/{provider}/deactivate', [App\Http\Controllers\Admin\DomainProviderController::class, 'deactivate'])->name('deactivate');
        Route::post('/{provider}/test-connection', [App\Http\Controllers\Admin\DomainProviderController::class, 'testConnection'])->name('test-connection');
        Route::get('/{provider}/pricing', [App\Http\Controllers\Admin\DomainProviderController::class, 'pricing'])->name('pricing');
        Route::post('/{provider}/pricing', [App\Http\Controllers\Admin\DomainProviderController::class, 'updatePricing'])->name('pricing.update');
        Route::post('/{provider}/sync-pricing', [App\Http\Controllers\Admin\DomainProviderController::class, 'syncPricing'])->name('sync-pricing');
    });

    // VPS Plans Management
    Route::prefix('vps-plans')->name('vps-plans.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\VpsPlanController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\VpsPlanController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\VpsPlanController::class, 'store'])->name('store');
        Route::get('/{vpsPlan}/edit', [App\Http\Controllers\Admin\VpsPlanController::class, 'edit'])->name('edit');
        Route::put('/{vpsPlan}', [App\Http\Controllers\Admin\VpsPlanController::class, 'update'])->name('update');
        Route::delete('/{vpsPlan}', [App\Http\Controllers\Admin\VpsPlanController::class, 'destroy'])->name('destroy');
        Route::patch('/{vpsPlan}/toggle-sold-out', [App\Http\Controllers\Admin\VpsPlanController::class, 'toggleSoldOut'])->name('toggle-sold-out');
        Route::post('/bulk-price-update', [App\Http\Controllers\Admin\VpsPlanController::class, 'bulkPriceUpdate'])->name('bulk-price-update');
    });

    // OVH Product Catalog
    Route::prefix('ovh-products')->name('ovh-products.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\OvhProductController::class, 'index'])->name('index');
        Route::get('/{ovhProduct}', [App\Http\Controllers\Admin\OvhProductController::class, 'show'])->name('show');
        Route::patch('/{ovhProduct}/toggle-active', [App\Http\Controllers\Admin\OvhProductController::class, 'toggleActive'])->name('toggle-active');
    });

    // OVH Dynamic Pricing Rules
    Route::resource('ovh-pricing-rules', App\Http\Controllers\Admin\OvhPricingRuleController::class)->names('ovh-pricing-rules');

    // Datacenter / Proxmox Nodes Management
    Route::prefix('datacenter')->name('datacenter.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\DatacenterController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\DatacenterController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\DatacenterController::class, 'store'])->name('store');
        Route::get('/{node}', [App\Http\Controllers\Admin\DatacenterController::class, 'show'])->name('show');
        Route::get('/{node}/edit', [App\Http\Controllers\Admin\DatacenterController::class, 'edit'])->name('edit');
        Route::put('/{node}', [App\Http\Controllers\Admin\DatacenterController::class, 'update'])->name('update');
        Route::delete('/{node}', [App\Http\Controllers\Admin\DatacenterController::class, 'destroy'])->name('destroy');
        Route::post('/{node}/sync', [App\Http\Controllers\Admin\DatacenterController::class, 'sync'])->name('sync');
        Route::post('/bulk-activate', [App\Http\Controllers\Admin\DatacenterController::class, 'bulkActivate'])->name('bulk-activate');
    });

    // IP Pool Management
    Route::prefix('ip-pool')->name('ip-pool.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\IpPoolController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Admin\IpPoolController::class, 'store'])->name('store');
        Route::post('/bulk', [App\Http\Controllers\Admin\IpPoolController::class, 'bulkStore'])->name('bulk');
        Route::delete('/{ip}', [App\Http\Controllers\Admin\IpPoolController::class, 'destroy'])->name('destroy');
        Route::post('/{ip}/release', [App\Http\Controllers\Admin\IpPoolController::class, 'release'])->name('release');
    });

    // Stream Analytics
    Route::get('/stream-analytics', [StreamAnalyticsController::class, 'index'])->name('stream-analytics.index');
    Route::get('/stream-analytics/{id}', [StreamAnalyticsController::class, 'show'])->name('stream-analytics.show');

    // Announcements
    Route::get('announcements/analytics', [\App\Http\Controllers\Admin\AnnouncementAnalyticsController::class, 'index'])->name('announcements.analytics');
    Route::get('announcements/analytics/data', [\App\Http\Controllers\Admin\AnnouncementAnalyticsController::class, 'data'])->name('announcements.analytics.data');
    Route::get('announcements/{announcement}/logs', [\App\Http\Controllers\Admin\AnnouncementController::class, 'logs'])->name('announcements.logs');
    Route::resource('announcements', AnnouncementController::class);
    Route::post('announcements/{announcement}/send', [AnnouncementController::class, 'send'])->name('announcements.send');
    Route::post('announcements/test', [AnnouncementController::class, 'testSend'])->name('announcements.test');
    Route::post('announcements/preview', [AnnouncementController::class, 'preview'])->name('announcements.preview');

    // Email marketing campaigns
    Route::resource('campaigns', EmailCampaignController::class);
    Route::patch('campaigns/{campaign}/send', [EmailCampaignController::class, 'send'])->name('campaigns.send');

    // AI chatbot conversations
    Route::get('ai-messages', [AiChatController::class, 'index'])->name('ai-messages.index');
    Route::get('ai-messages/{sessionId}', [AiChatController::class, 'show'])->name('ai-messages.show');
    Route::delete('ai-messages/{sessionId}', [AiChatController::class, 'destroy'])->name('ai-messages.destroy');
    Route::get('ai-feedback/{feedback}', [AiChatController::class, 'feedback'])->name('ai-messages.feedback');

    // Predictive churn
    Route::get('churn-risk', [ChurnRiskController::class, 'index'])->name('churn-risk.index');

    // API key management
    Route::resource('api-keys', ApiKeyController::class);
    Route::patch('api-keys/{api_key}/regenerate', [ApiKeyController::class, 'regenerate'])->name('api-keys.regenerate');
    Route::get('announcements/audience-count', [AnnouncementController::class, 'audienceCount'])->name('announcements.audience-count');
    Route::get('announcement-templates/{template}/load', [AnnouncementController::class, 'loadTemplate'])->name('announcements.template.load');
    Route::resource('announcement-templates', \App\Http\Controllers\Admin\AnnouncementTemplateController::class)->except(['show']);
});
