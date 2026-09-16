<?php

require __DIR__.'/support.php';

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\UpgradeController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamInvitationController;
use App\Livewire\ServiceIndex;
use App\Livewire\About;
use App\Livewire\Contact;
use App\Livewire\ServiceDetail;
use App\Livewire\PortfolioIndex;
use App\Livewire\PortfolioDetail;
use App\Livewire\ClientDashboard;
use App\Livewire\Checkout;
use App\Livewire\MyOrders;
use App\Livewire\VpsHosting;
use App\Livewire\WebHosting;
use App\Livewire\HostingLanding;
use App\Livewire\ServerDashboard;
use App\Http\Controllers\ServerDashboardController;
use App\Models\Service;
use App\Models\Portfolio;
use App\Models\Setting;
use App\Models\PageContent;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\StreamingPlansController;
use App\Livewire\StreamingManagement;

// ── B-CONNECT (subdomain: bc.believoo.com) ────────────────────────
require __DIR__.'/bconnect.php';

Route::get('auth/google', [SocialLoginController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [SocialLoginController::class, 'handleGoogleCallback']);

Route::get('auth/facebook', [SocialLoginController::class, 'redirectToFacebook'])->name('auth.facebook');
Route::get('auth/facebook/callback', [SocialLoginController::class, 'handleFacebookCallback']);

Route::get('auth/twitter', [SocialLoginController::class, 'redirectToTwitter'])->name('auth.twitter');
Route::get('auth/twitter/callback', [SocialLoginController::class, 'handleTwitterCallback']);

// Newsletter subscription (public)
Route::post('/newsletter/subscribe', [\App\Http\Controllers\NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [\App\Http\Controllers\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Email campaign tracking (public, no auth)
Route::get('/campaign/pixel/{token}', [\App\Http\Controllers\CampaignTrackingController::class, 'pixel'])->name('campaign.pixel');
Route::get('/campaign/click/{token}', [\App\Http\Controllers\CampaignTrackingController::class, 'click'])->name('campaign.click');

// ── Support Agent Portal (subdomain: agent.believoo.com) ────────────
Route::domain('agent.believoo.com')->group(function () {
    Route::get('/', [\App\Http\Controllers\AgentAuthController::class, 'showLogin']);
    Route::get('/login', [\App\Http\Controllers\AgentAuthController::class, 'showLogin'])->name('agent.sub.login');
    Route::post('/login', [\App\Http\Controllers\AgentAuthController::class, 'login'])->name('agent.sub.login.post');
    Route::get('/keep-alive', [\App\Http\Controllers\AgentAuthController::class, 'keepAlive'])->name('agent.sub.keepalive');
    Route::middleware(['agent'])->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\AgentAuthController::class, 'dashboard'])->name('agent.sub.dashboard');
        Route::post('/logout', [\App\Http\Controllers\AgentAuthController::class, 'logout'])->name('agent.sub.logout');
    });
});

Route::get('/track/announcement/{announcement}/{recipient}/open.png', [\App\Http\Controllers\AnnouncementTrackingController::class, 'open'])->name('track.announcement.open');
Route::get('/track/announcement/{announcement}/{recipient}/click', [\App\Http\Controllers\AnnouncementTrackingController::class, 'click'])->name('track.announcement.click');
Route::get('/unsubscribe/announcements', [\App\Http\Controllers\AnnouncementTrackingController::class, 'unsubscribe'])->name('announcements.unsubscribe');
Route::get('/resubscribe/announcements', [\App\Http\Controllers\AnnouncementTrackingController::class, 'resubscribe'])->name('announcements.resubscribe');
Route::get('/announcements/rss.xml', [\App\Http\Controllers\AnnouncementFeedController::class, 'rss'])->name('announcements.rss');
Route::get('/announcements.json', [\App\Http\Controllers\AnnouncementFeedController::class, 'json'])->name('announcements.json');
Route::post('/push/subscribe', [\App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');
Route::view('/offline', 'offline')->name('offline');

Route::get('/', function () {
    $services = Service::where('is_active', true)
        ->whereNotIn('category', ['ovh_dedicated', 'ovh_web_hosting', 'ovh_vps'])
        ->whereNotIn('slug', [
            'managed-vps-cloud',
            'streaming-addon',
            'app-development',
            'adsense-approval-service',
        ])
        ->get();
    $portfolios = Portfolio::where('is_visible', true)->limit(3)->get();
    $settings = Setting::pluck('value', 'key');
    $content = PageContent::where('page_name', 'home')->pluck('value', 'key');
    
    return view('welcome', compact('services', 'portfolios', 'settings', 'content'));
})->name('home');

Route::get('/services', ServiceIndex::class)->name('services.index');
Route::get('/services/streaming', [\App\Http\Controllers\StreamingPlansController::class, 'index'])->name('services.streaming');
Route::get('/services/{service:slug}', ServiceDetail::class)->name('services.show');
Route::get('/portfolio', PortfolioIndex::class)->name('portfolio.index');
Route::get('/portfolio/{portfolio:slug}', PortfolioDetail::class)->name('portfolio.show');
Route::get('/about', About::class)->name('about');
Route::get('/contact', Contact::class)->name('contact');

Route::get('/terms', function() {
    $settings = App\Models\Setting::pluck('value', 'key');
    return view('terms', compact('settings'));
})->name('terms');

Route::get('/policy', function() {
    $settings = App\Models\Setting::pluck('value', 'key');
    return view('policy', compact('settings'));
})->name('policy');

// XML Sitemap
Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Blog
Route::get('/blog', [\App\Http\Controllers\PostController::class, 'index'])->name('blog.index');
Route::get('/blog/{post:slug}', [\App\Http\Controllers\PostController::class, 'show'])->name('blog.show');

// Knowledge Base
Route::get('/help', [\App\Http\Controllers\KnowledgeBaseController::class, 'index'])->name('kb.index');
Route::get('/help/{article}', [\App\Http\Controllers\KnowledgeBaseController::class, 'show'])->name('kb.show');

// Status Page
Route::get('/status', [\App\Http\Controllers\StatusPageController::class, 'index'])->name('status');

Route::get('/hosting', function() {
    return redirect('https://ghc.believoo.com', 301);
})->name('hosting');

Route::get('/vps-hosting', function() {
    return redirect('https://ghc.believoo.com', 301);
})->name('vps-hosting');
Route::get('/web-hosting', function() {
    return redirect('https://ghc.believoo.com', 301);
})->name('web-hosting');

// Streaming API Documentation - Complete Guide
Route::get('/docs/streaming', function() {
    return view('docs.streaming-complete');
})->name('docs.streaming');

// Old streaming docs (redirect to new)
Route::get('/docs/streaming-old', function() {
    return view('docs.streaming');
})->name('docs.streaming.old');

// VPS Streaming Backend Setup Guide
Route::get('/docs/vps-streaming-setup', function() {
    return view('docs.vps-streaming-setup');
})->name('docs.vps-streaming-setup');

// SDK Downloads
Route::get('/sdk/downloads', [App\Http\Controllers\StreamingSdkController::class, 'sdkDownloads'])->name('streaming.sdk.downloads');
Route::get('/sdk/android', [App\Http\Controllers\StreamingSdkController::class, 'downloadAndroidSdk'])->name('streaming.sdk.android');
Route::get('/sdk/flutter', [App\Http\Controllers\StreamingSdkController::class, 'downloadFlutterSdk'])->name('streaming.sdk.flutter');
Route::get('/sdk/react', [App\Http\Controllers\StreamingSdkController::class, 'downloadReactSdk'])->name('streaming.sdk.react');
Route::get('/sdk/docs/{platform}', [App\Http\Controllers\StreamingSdkController::class, 'getIntegrationDocs'])->name('streaming.sdk.docs');

// Redirect old hosting pages to ghc.believoo.com
Route::get('/vps-hosting.html', function() {
    return redirect('https://ghc.believoo.com', 301);
});
Route::get('/web-hosting.html', function() {
    return redirect('https://ghc.believoo.com', 301);
});
Route::get('/dedicated-servers.html', function() {
    return redirect('https://ghc.believoo.com', 301);
});
Route::get('/hosting-pricing.html', function() {
    return redirect('https://ghc.believoo.com', 301);
});

// Public Domain Search (no auth required)
Route::get('/domains/search', [App\Http\Controllers\Client\DomainController::class, 'search'])->name('client.domains.search');
Route::post('/domains/search', [App\Http\Controllers\Client\DomainController::class, 'searchDomains'])->name('client.domains.search.post');

// Domain Registration Form (accessible to guests, will redirect to login if needed)
Route::get('/domains/register', [App\Http\Controllers\Client\DomainController::class, 'showRegistrationForm'])->name('client.domains.register.form');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/client/dashboard', ClientDashboard::class)->name('client.dashboard')->middleware('check.phone');
    Route::get('/client/orders', MyOrders::class)->name('client.orders')->middleware('check.phone');
    Route::get('/client/referrals', \App\Livewire\ClientReferrals::class)->name('client.referrals')->middleware('check.phone');

    // Server Management Dashboard Routes
    Route::get('/client/servers', ServerDashboard::class)->name('client.servers')->middleware('check.phone');
    Route::get('/api/servers/data', [ServerDashboardController::class, 'getData'])->name('api.servers.data')->middleware('check.phone');
    Route::get('/api/servers/{serviceId}/{vpsId}/details', [ServerDashboardController::class, 'getServerDetails'])->name('api.servers.details')->middleware('check.phone');
    Route::post('/api/servers/{vpsId}/action', [ServerDashboardController::class, 'executeAction'])->name('api.servers.action')->middleware('check.phone');
    Route::get('/api/servers/{vpsId}/console', [ServerDashboardController::class, 'getConsoleUrl'])->name('api.servers.console')->middleware('check.phone');
    
    // Secure Console Proxy Routes (masks Proxmox host)
    Route::prefix('console')->name('client.console.')->middleware(['auth', 'verified', 'check.phone'])->group(function () {
        // Create new console session
        Route::post('/session/{hostingId}', [\App\Http\Controllers\Client\ConsoleController::class, 'createSession'])->name('session.create');
        
        // View console page (masked domain)
        Route::get('/{sessionId}', [\App\Http\Controllers\Client\ConsoleController::class, 'showConsole'])->name('view');
        
        // WebSocket proxy endpoint
        Route::get('/ws/{sessionId}', [\App\Http\Controllers\Client\ConsoleController::class, 'websocketProxy'])->name('websocket');
        
        // Session health check
        Route::get('/health/{sessionId}', [\App\Http\Controllers\Client\ConsoleController::class, 'sessionHealth'])->name('health');
        
        // Destroy session
        Route::delete('/session/{sessionId}', [\App\Http\Controllers\Client\ConsoleController::class, 'destroySession'])->name('session.destroy');
    });
    Route::post('/api/servers/refresh', [ServerDashboardController::class, 'refreshData'])->name('api.servers.refresh')->middleware('check.phone');
    Route::get('/api/servers/health', [ServerDashboardController::class, 'healthCheck'])->name('api.servers.health');
    
    // Server Import Routes (for dashboard session auth)
    Route::prefix('api/server-imports')->middleware(['check.phone'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\ServerImportController::class, 'index']);
        Route::get('/stats', [\App\Http\Controllers\Client\ServerImportController::class, 'getStats']);
        Route::post('/test-connection', [\App\Http\Controllers\Client\ServerImportController::class, 'testConnection']);
        Route::post('/{hostingId}/start', [\App\Http\Controllers\Client\ServerImportController::class, 'startImport']);
        Route::get('/{hostingId}/active', [\App\Http\Controllers\Client\ServerImportController::class, 'getActiveImport']);
        Route::delete('/{importId}/cancel', [\App\Http\Controllers\Client\ServerImportController::class, 'cancelImport']);
        Route::get('/{importId}/progress', [\App\Http\Controllers\Client\ServerImportController::class, 'getProgress']);
    });
    
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit')->middleware('check.phone');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware('check.phone');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy')->middleware('check.phone');

    // Complete Profile Routes (for social login users) - no check.phone middleware
    Route::get('/profile/complete', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::post('/profile/complete', [ProfileController::class, 'completeStore'])->name('profile.complete.store');

    // Checkout Routes
    Route::get('/checkout/{service:slug}/{tier?}', Checkout::class)->name('checkout')->middleware('check.phone');

    // Invoice Routes
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'view'])->name('invoice.view')->middleware('check.phone');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoice.download')->middleware('check.phone');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'download'])->name('invoices.download')->middleware('check.phone');

    // Upgrade Routes
    Route::get('/upgrade/{hosting}', [UpgradeController::class, 'showUpgradeOptions'])->name('upgrade.options')->middleware('check.phone');
    Route::post('/upgrade/{hosting}', [UpgradeController::class, 'upgrade'])->name('upgrade.process')->middleware('check.phone');

    // VPS Streaming Addon Activation
    Route::get('/client/hosting/{hosting}/activate-streaming', [StreamingPlansController::class, 'activateVpsAddon'])->name('client.streaming.activate')->middleware('check.phone');

    // Payment Routes - Razorpay
    Route::post('/payment/razorpay/create', [PaymentController::class, 'createRazorpayOrder'])->name('payment.razorpay.create')->middleware('check.phone');
    Route::post('/payment/razorpay/callback', [PaymentController::class, 'razorpayCallback'])->name('payment.razorpay.callback')->middleware('check.phone');

    // Payment Routes - Cashfree
    Route::post('/payment/cashfree/create', [PaymentController::class, 'createCashfreeOrder'])->name('payment.cashfree.create')->middleware('check.phone');
    Route::get('/payment/cashfree/callback', [PaymentController::class, 'cashfreeCallback'])->name('payment.cashfree.callback')->middleware('check.phone');

    // Payment Routes - PayPal
    Route::post('/payment/paypal/create', [PaymentController::class, 'createPaypalOrder'])->name('payment.paypal.create')->middleware('check.phone');
    Route::get('/payment/paypal/callback', [PaymentController::class, 'paypalCallback'])->name('payment.paypal.callback')->middleware('check.phone');
    Route::post('/payment/payu/create', [PaymentController::class, 'createPayuOrder'])->name('payment.payu.create')->middleware('check.phone');
    Route::post('/payment/payu/callback', [PaymentController::class, 'payuCallback'])->name('payment.payu.callback')->middleware('check.phone');
    Route::get('/wallet/top-up', [WalletController::class, 'topup'])->name('wallet.topup')->middleware('check.phone');
    Route::post('/wallet/top-up/create', [WalletController::class, 'createTopup'])->name('wallet.topup.create')->middleware('check.phone');
    Route::post('/wallet/top-up/callback', [WalletController::class, 'topupCallback'])->name('wallet.topup.callback')->middleware('check.phone');
    Route::post('/wallet/top-up/cashfree/create', [WalletController::class, 'createCashfreeTopup'])->name('wallet.topup.cashfree.create')->middleware('check.phone');
    Route::get('/wallet/top-up/cashfree/callback', [WalletController::class, 'cashfreeTopupCallback'])->name('wallet.topup.cashfree.callback')->middleware('check.phone');
    Route::post('/wallet/top-up/paypal/create', [WalletController::class, 'createPaypalTopup'])->name('wallet.topup.paypal.create')->middleware('check.phone');
    Route::get('/wallet/top-up/paypal/callback', [WalletController::class, 'paypalTopupCallback'])->name('wallet.topup.paypal.callback')->middleware('check.phone');
    Route::post('/payment/wallet/pay', [WalletController::class, 'pay'])->name('payment.wallet.pay')->middleware('check.phone');

    // Team Management Routes
    Route::resource('teams', TeamController::class)->middleware('check.phone');
    Route::get('teams/{team}/members', [TeamController::class, 'members'])->name('teams.members')->middleware('check.phone');
    Route::post('teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add')->middleware('check.phone');
    Route::delete('teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.remove')->middleware('check.phone');
    Route::put('teams/{team}/members/{user}', [TeamController::class, 'updateMemberRole'])->name('teams.members.update')->middleware('check.phone');

    // Team Invitation Routes
    Route::post('teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('teams.invitations.store')->middleware('check.phone');
    Route::get('teams/{team}/invitations', [TeamInvitationController::class, 'index'])->name('teams.invitations.index')->middleware('check.phone');
    Route::delete('teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('teams.invitations.destroy')->middleware('check.phone');
    Route::post('teams/{team}/invitations/{invitation}/resend', [TeamInvitationController::class, 'resend'])->name('teams.invitations.resend')->middleware('check.phone');

    // Public Invitation Routes (no auth required)
    Route::get('team-invitations/{token}/accept', [TeamInvitationController::class, 'accept'])->name('teams.invitations.accept');
    Route::get('team-invitations/{token}/decline', [TeamInvitationController::class, 'decline'])->name('teams.invitations.decline');

    // Domain Management Routes (Auth Required)
    Route::prefix('domains')->name('client.domains.')->middleware('check.phone')->group(function () {
        // Registration (POST only - GET is public and handled above)
        Route::post('/register', [App\Http\Controllers\Client\DomainController::class, 'register'])->name('register');
        
        // My Domains Dashboard
        Route::get('/my-domains', [App\Http\Controllers\Client\DomainController::class, 'myDomains'])->name('my-domains');
        
        // Domain Details & Management
        Route::get('/{domain}', [App\Http\Controllers\Client\DomainController::class, 'show'])->name('show');
        Route::post('/{domain}/dns', [App\Http\Controllers\Client\DomainController::class, 'updateDns'])->name('dns.update');
        Route::post('/{domain}/nameservers', [App\Http\Controllers\Client\DomainController::class, 'updateNameservers'])->name('nameservers.update');
        Route::post('/{domain}/renew', [App\Http\Controllers\Client\DomainController::class, 'renew'])->name('renew');
        Route::get('/{domain}/auth-code', [App\Http\Controllers\Client\DomainController::class, 'getAuthCode'])->name('auth-code');
        Route::post('/{domain}/auto-renew', [App\Http\Controllers\Client\DomainController::class, 'toggleAutoRenew'])->name('auto-renew.toggle');
    });

    // VPS Plans Routes (Public)
    Route::prefix('vps')->name('vps-plans.')->group(function () {
        Route::get('/', [App\Http\Controllers\VpsPlanController::class, 'index'])->name('index');
        Route::get('/category/{category}', [App\Http\Controllers\VpsPlanController::class, 'category'])->name('category');
        Route::get('/{vpsPlan:slug}', [App\Http\Controllers\VpsPlanController::class, 'show'])->name('show');
        Route::get('/{vpsPlan:slug}/configure', [App\Http\Controllers\VpsPlanController::class, 'configure'])->name('configure');
        Route::post('/{vpsPlan:slug}/set-os', [App\Http\Controllers\VpsPlanController::class, 'setOs'])->name('set-os');
    });

    // Agreement Routes
    Route::get('/agreements/{agreement}', [\App\Http\Controllers\AgreementController::class, 'view'])->name('client.agreement.view')->middleware('check.phone');
    Route::get('/agreements/{agreement}/download', [\App\Http\Controllers\AgreementController::class, 'download'])->name('client.agreement.download')->middleware('check.phone');

    // Milestone Payment Routes
    Route::get('/milestones/{milestone}/pay', [\App\Http\Controllers\MilestonePaymentController::class, 'createPayment'])->name('milestone.pay')->middleware('check.phone');
    Route::post('/milestones/payment/callback', [\App\Http\Controllers\MilestonePaymentController::class, 'razorpayCallback'])->name('milestone.razorpay.callback')->middleware('check.phone');
    Route::get('/milestones/payment/cashfree/callback', [\App\Http\Controllers\MilestonePaymentController::class, 'cashfreeCallback'])->name('milestone.cashfree.callback')->middleware('check.phone');
    Route::get('/milestones/payment/paypal/callback', [\App\Http\Controllers\MilestonePaymentController::class, 'paypalCallback'])->name('milestone.paypal.callback')->middleware('check.phone');
    Route::post('/milestones/payment/payu/callback', [\App\Http\Controllers\MilestonePaymentController::class, 'payuCallback'])->name('milestone.payu.callback')->middleware('check.phone');
    Route::post('/milestones/{milestone}/payment-proof', [\App\Http\Controllers\MilestonePaymentController::class, 'submitPaymentProof'])->name('milestone.payment-proof')->middleware('check.phone');

    // AMC Routes
    Route::get('/agreements/{agreement}/amc/subscribe', [\App\Http\Controllers\AmcController::class, 'showSubscribeForm'])->name('client.amc.subscribe')->middleware('check.phone');
    Route::post('/agreements/{agreement}/amc/subscribe', [\App\Http\Controllers\AmcController::class, 'subscribe'])->name('client.amc.subscribe.post')->middleware('check.phone');
    Route::post('/amc/payment/callback', [\App\Http\Controllers\AmcController::class, 'razorpayCallback'])->name('amc.razorpay.callback')->middleware('check.phone');
    Route::post('/amc/subscriptions/{subscription}/payment-proof', [\App\Http\Controllers\AmcController::class, 'submitPaymentProof'])->name('client.amc.payment-proof')->middleware('check.phone');

    // Invoice Routes
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\AgreementInvoiceController::class, 'download'])->name('client.invoice.download')->middleware('check.phone');
    Route::get('/invoices/{invoice}', [\App\Http\Controllers\AgreementInvoiceController::class, 'view'])->name('client.invoice.view')->middleware('check.phone');

    // Referral Routes
    Route::post('/referrals', [\App\Http\Controllers\ReferralController::class, 'store'])->name('client.referrals.store')->middleware('check.phone');
    Route::get('/referrals/stats', [\App\Http\Controllers\ReferralController::class, 'getReferralStats'])->name('client.referrals.stats')->middleware('check.phone');
    Route::get('/referrals/list', [\App\Http\Controllers\ReferralController::class, 'getReferrals'])->name('client.referrals.list')->middleware('check.phone');

    // Server Migration Routes - ORDER MATTERS: specific routes before {id} wildcard
    Route::prefix('migrations')->name('client.migration.')->middleware('check.phone')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\MigrationController::class, 'index'])->name('index');
        Route::get('/wizard', [\App\Http\Controllers\Client\MigrationController::class, 'wizard'])->name('wizard');
        Route::post('/wizard/start', [\App\Http\Controllers\Client\MigrationController::class, 'start'])->name('start');
        Route::get('/{id}/status', [\App\Http\Controllers\Client\MigrationController::class, 'status'])->name('status');
        Route::get('/{id}', [\App\Http\Controllers\Client\MigrationController::class, 'show'])->name('show');
    });
    
    // VPS Snapshot Routes
    Route::prefix('snapshots')->name('client.snapshots.')->middleware('check.phone')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\SnapshotController::class, 'index'])->name('index');
        Route::post('/create', [\App\Http\Controllers\Client\SnapshotController::class, 'create'])->name('create');
        Route::post('/quick', [\App\Http\Controllers\Client\SnapshotController::class, 'quickSnapshot'])->name('quick');
        Route::post('/{id}/restore', [\App\Http\Controllers\Client\SnapshotController::class, 'restore'])->name('restore');
        Route::delete('/{id}', [\App\Http\Controllers\Client\SnapshotController::class, 'delete'])->name('delete');
    });
    
    // VPS License Routes
    Route::prefix('licenses')->name('client.licenses.')->middleware('check.phone')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\LicenseController::class, 'index'])->name('index');
        Route::post('/purchase', [\App\Http\Controllers\Client\LicenseController::class, 'purchase'])->name('purchase');
        Route::post('/{id}/activate', [\App\Http\Controllers\Client\LicenseController::class, 'activate'])->name('activate');
    });
    
    // DNS Scanner Routes
    Route::prefix('dns-scanner')->name('client.dns.')->middleware('check.phone')->group(function () {
        Route::get('/', [\App\Http\Controllers\Client\DnsScannerController::class, 'index'])->name('index');
        Route::post('/scan', [\App\Http\Controllers\Client\DnsScannerController::class, 'scan'])->name('scan');
        Route::post('/migrate', [\App\Http\Controllers\Client\DnsScannerController::class, 'migrate'])->name('migrate');
        Route::post('/export', [\App\Http\Controllers\Client\DnsScannerController::class, 'export'])->name('export');
    });

    // DNS Management API Routes (Hostinger/OVH Style)
    Route::prefix('api/dns')->name('api.dns.')->middleware('check.phone')->group(function () {
        // Get DNS records for a domain
        Route::get('/records/{domainId}', [\App\Http\Controllers\Client\DnsRecordController::class, 'getDnsRecords'])->name('records');
        
        // Get nameserver info
        Route::get('/nameservers/{domainId}', [\App\Http\Controllers\Client\DnsRecordController::class, 'getNameserverInfo'])->name('nameservers');
        
        // CRUD operations for DNS records
        Route::post('/records', [\App\Http\Controllers\Client\DnsRecordController::class, 'store'])->name('store');
        Route::put('/records/{id}', [\App\Http\Controllers\Client\DnsRecordController::class, 'update'])->name('update');
        Route::delete('/records/{id}', [\App\Http\Controllers\Client\DnsRecordController::class, 'destroy'])->name('destroy');
    });
});

// Connect External Domain API
Route::post('/api/domains/connect-external', [\App\Http\Controllers\Client\DomainController::class, 'connectExternalDomain'])
    ->middleware(['auth', 'verified'])
    ->name('api.domains.connect-external');

// Migration Progress API (internal use, no auth required for script updates)
Route::post('/api/migrations/{id}/progress', [\App\Http\Controllers\Client\MigrationController::class, 'updateProgress'])->name('api.migration.progress');

// Webhook Routes (No auth required)
// ── External Integration Webhooks (Telegram, FB/IG, Email) ─────────
Route::post('/webhook/telegram', [\App\Http\Controllers\IntegrationWebhookController::class, 'telegram']);
Route::get('/webhook/whatsapp', [\App\Http\Controllers\IntegrationWebhookController::class, 'whatsappVerify']);
Route::post('/webhook/whatsapp', [\App\Http\Controllers\IntegrationWebhookController::class, 'whatsappMessage']);
Route::get('/webhook/meta', [\App\Http\Controllers\IntegrationWebhookController::class, 'metaVerify']);
Route::post('/webhook/meta', [\App\Http\Controllers\IntegrationWebhookController::class, 'metaMessage']);
Route::post('/webhook/inbound-email', [\App\Http\Controllers\IntegrationWebhookController::class, 'inboundEmail']);
Route::post('/webhook/email-ticket', [\App\Http\Controllers\IntegrationWebhookController::class, 'emailToTicket']);

Route::post('/payment/razorpay/webhook', [PaymentController::class, 'razorpayWebhook'])->name('payment.razorpay.webhook');
Route::post('/payment/cashfree/webhook', [PaymentController::class, 'cashfreeWebhook'])->name('payment.cashfree.webhook');
Route::post('/payment/paypal/webhook', [PaymentController::class, 'paypalWebhook'])->name('payment.paypal.webhook');

Route::get('/admin/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create']);
Route::post('/admin/login', [App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);

// ── Streaming Services Routes ────────────────────────────────────────
Route::get('/services/streaming', [StreamingPlansController::class, 'index'])
    ->name('services.streaming');
Route::get('/services/streaming/checkout/{planSlug}', [StreamingPlansController::class, 'checkout'])
    ->name('services.streaming.checkout');
Route::post('/services/streaming/process', [StreamingPlansController::class, 'processOrder'])
    ->name('services.streaming.process');

// ── Streaming Engine — Auth-guarded client routes ────────────────────
Route::middleware(['auth', 'verified', 'check.phone'])->group(function () {
    Route::get('/client/streaming', \App\Livewire\StreamingManagement::class)
        ->name('client.streaming');
    Route::get('/client/streaming/projects', \App\Livewire\StreamingManagement::class)
        ->name('client.streaming.projects');
    Route::post('/client/streaming/projects', \App\Livewire\StreamingManagement::class)
        ->name('client.streaming.projects.store');

    // Fetch streaming API key credentials on demand (not embedded in HTML)
    Route::get('/api/streaming/api-keys/{apiKey}/credential/{field}', [\App\Http\Controllers\Client\StreamingApiKeyController::class, 'credential'])
        ->name('api.streaming.credential');
});

// ── Support Agent Portal (path fallback: believoo.com/agent) ────────
Route::get('/agent/login', [\App\Http\Controllers\AgentAuthController::class, 'showLogin'])->name('agent.login');
Route::post('/agent/login', [\App\Http\Controllers\AgentAuthController::class, 'login'])->name('agent.login.post');
Route::get('/agent/keep-alive', [\App\Http\Controllers\AgentAuthController::class, 'keepAlive'])->name('agent.keepalive');
Route::middleware(['agent'])->group(function () {
    Route::get('/agent/dashboard', [\App\Http\Controllers\AgentAuthController::class, 'dashboard'])->name('agent.dashboard');
    Route::post('/agent/logout', [\App\Http\Controllers\AgentAuthController::class, 'logout'])->name('agent.logout');
});

// Admin Routes (Custom - Not Filament)
// Status page & FAQ search (public)
Route::get('/status', [\App\Http\Controllers\StatusPageController::class, 'index'])->name('status');
Route::get('/faq', [\App\Http\Controllers\FaqController::class, 'index'])->name('faq');

require __DIR__.'/admin.php';

require __DIR__.'/auth.php';
