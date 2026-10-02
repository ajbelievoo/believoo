<?php

use App\Http\Controllers\Api\V1\BconnectController;
use App\Http\Controllers\Api\V1\CurrencyApiController;
use App\Http\Controllers\Api\V1\LicenseApiController;
use App\Http\Controllers\Api\V1\ServerApiController;
use App\Http\Controllers\Api\V1\SupportApiController;
use App\Http\Controllers\Api\AudioMixerController;
use App\Http\Controllers\Api\DocsController;
use App\Http\Controllers\Api\StreamingStatusController;
use App\Http\Controllers\Client\ServerImportController;
use App\Http\Controllers\Client\VmMigrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Believoo public REST API. Authentication is via API key in the
| Authorization: Bearer <token> or X-API-Key: <token> header.
|
| Scopes:
|   user:read, servers:read, servers:control, server-imports:read,
|   server-imports:write, licenses:read, licenses:write,
|   tickets:read, tickets:write, currencies:read, currencies:write,
|   streaming:read, audio-mixer:read, audio-mixer:write,
|   bconnect:read, bconnect:write,
|   churn:read
|
*/

Route::prefix('v1')->group(function () {

    // Public routes
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'service' => 'BelieVoo API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // Payment webhooks (no auth, signatures verified internally)
    Route::post('/webhooks/cashfree', [\App\Http\Controllers\PaymentController::class, 'cashfreeWebhook'])
        ->name('api.webhooks.cashfree');
    Route::post('/webhooks/razorpay', [\App\Http\Controllers\PaymentController::class, 'razorpayWebhook'])
        ->name('api.webhooks.razorpay');

    // BMyDesk desktop agent (code-based remote sessions) — public, rate-limited
    Route::prefix('bmydesk/agent')->middleware('throttle:30,1')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'register']);
        Route::get('/{code}/status', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'status']);
        Route::post('/{code}/end', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'end']);
        Route::post('/broadcast-auth', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'broadcastAuth']);
        Route::get('/version', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'version']);
        Route::post('/login', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'login']);
        Route::post('/{code}/join', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'join']);
        Route::post('/{code}/respond', [\App\Http\Controllers\Bconnect\AgentApiController::class, 'respond']);
    });

    // Authenticated routes
    Route::middleware(['api.key'])->group(function () {

        // User profile
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                ],
            ]);
        })->middleware('api.key:user:read')->name('api.user');

        // Server management
        Route::prefix('servers')->middleware('api.key:servers:read')->group(function () {
            Route::get('/', [ServerApiController::class, 'index']);
            Route::get('/{id}', [ServerApiController::class, 'show']);
            Route::get('/{id}/stats', [ServerApiController::class, 'stats']);
            Route::get('/{id}/bandwidth', [ServerApiController::class, 'bandwidth']);
            Route::get('/{id}/health', [ServerApiController::class, 'health']);

            Route::middleware('api.key:servers:control')->group(function () {
                Route::post('/{id}/start', [ServerApiController::class, 'start']);
                Route::post('/{id}/stop', [ServerApiController::class, 'stop']);
                Route::post('/{id}/restart', [ServerApiController::class, 'restart']);
                Route::post('/{id}/action', [ServerApiController::class, 'action']);
                Route::post('/bulk-action', [ServerApiController::class, 'bulkAction']);
            });

            Route::get('/{id}/migration/nodes', [VmMigrationController::class, 'getAvailableNodes'])
                ->middleware('api.key:servers:read');
            Route::post('/{id}/migration/start', [VmMigrationController::class, 'startMigration'])
                ->middleware('api.key:servers:control');
            Route::get('/{id}/migration/active', [VmMigrationController::class, 'getActiveMigration'])
                ->middleware('api.key:servers:read');
        });

        Route::get('/vm-migrations', [VmMigrationController::class, 'index'])
            ->middleware('api.key:servers:read');
        Route::get('/vm-migrations/{id}/progress', [VmMigrationController::class, 'getProgress'])
            ->middleware('api.key:servers:read');

        // Server imports
        Route::prefix('server-imports')->group(function () {
            Route::get('/', [ServerImportController::class, 'index'])->middleware('api.key:server-imports:read');
            Route::get('/stats', [ServerImportController::class, 'getStats'])->middleware('api.key:server-imports:read');
            Route::post('/test-connection', [ServerImportController::class, 'testConnection'])->middleware('api.key:server-imports:write');
            Route::post('/{hostingId}/start', [ServerImportController::class, 'startImport'])->middleware('api.key:server-imports:write');
            Route::get('/{hostingId}/active', [ServerImportController::class, 'getActiveImport'])->middleware('api.key:server-imports:read');
            Route::delete('/{importId}/cancel', [ServerImportController::class, 'cancelImport'])->middleware('api.key:server-imports:write');
            Route::get('/{importId}/progress', [ServerImportController::class, 'getProgress'])->middleware('api.key:server-imports:read');
        });

        // Licenses
        Route::prefix('licenses')->group(function () {
            Route::get('/types', [LicenseApiController::class, 'types'])->middleware('api.key:licenses:read');
            Route::get('/', [LicenseApiController::class, 'index'])->middleware('api.key:licenses:read');
            Route::get('/{id}', [LicenseApiController::class, 'show'])->middleware('api.key:licenses:read');
            Route::post('/order', [LicenseApiController::class, 'createOrder'])->middleware('api.key:licenses:write');
            Route::post('/{id}/activate', [LicenseApiController::class, 'activate'])->middleware('api.key:licenses:write');
            Route::get('/orders/{orderNumber}', [LicenseApiController::class, 'orderStatus'])->middleware('api.key:licenses:read');
        });

        // Support tickets
        Route::prefix('tickets')->group(function () {
            Route::get('/priorities', [SupportApiController::class, 'priorities'])->middleware('api.key:tickets:read');
            Route::get('/', [SupportApiController::class, 'index'])->middleware('api.key:tickets:read');
            Route::post('/', [SupportApiController::class, 'store'])->middleware('api.key:tickets:write');
            Route::get('/{ticketId}', [SupportApiController::class, 'show'])->middleware('api.key:tickets:read');
            Route::post('/{ticketId}/messages', [SupportApiController::class, 'addMessage'])->middleware('api.key:tickets:write');
            Route::post('/{ticketId}/close', [SupportApiController::class, 'close'])->middleware('api.key:tickets:write');
        });

        // Churn risk
        Route::prefix('churn-risk')->middleware('api.key:churn:read')->group(function () {
            Route::get('/users', [\App\Http\Controllers\Api\V1\ChurnRiskController::class, 'users']);
            Route::get('/companies', [\App\Http\Controllers\Api\V1\ChurnRiskController::class, 'companies']);
            Route::get('/users/{user}', [\App\Http\Controllers\Api\V1\ChurnRiskController::class, 'showUser']);
            Route::get('/companies/{company}', [\App\Http\Controllers\Api\V1\ChurnRiskController::class, 'showCompany']);
        });

        // B-Connect workspace
        Route::prefix('bconnect')->group(function () {
            Route::get('/companies', [BconnectController::class, 'companies'])->middleware('api.key:bconnect:read');
            Route::get('/projects', [BconnectController::class, 'projects'])->middleware('api.key:bconnect:read');
            Route::get('/tickets', [BconnectController::class, 'tickets'])->middleware('api.key:bconnect:read');
            Route::get('/tickets/{ticket}', [BconnectController::class, 'showTicket'])->middleware('api.key:bconnect:read');
            Route::post('/tickets', [BconnectController::class, 'storeTicket'])->middleware('api.key:bconnect:write');
            Route::put('/tickets/{ticket}', [BconnectController::class, 'updateTicket'])->middleware('api.key:bconnect:write');
            Route::get('/time-entries', [BconnectController::class, 'timeEntries'])->middleware('api.key:bconnect:read');
            Route::post('/time-entries', [BconnectController::class, 'storeTimeEntry'])->middleware('api.key:bconnect:write');
        });

        // Currencies
        Route::prefix('currencies')->group(function () {
            Route::get('/', [CurrencyApiController::class, 'index'])->middleware('api.key:currencies:read');
            Route::get('/rates', [CurrencyApiController::class, 'rates'])->middleware('api.key:currencies:read');
            Route::post('/convert', [CurrencyApiController::class, 'convert'])->middleware('api.key:currencies:read');
            Route::post('/set', [CurrencyApiController::class, 'setCurrency'])->middleware('api.key:currencies:write');
            Route::get('/payment-gateway', [CurrencyApiController::class, 'paymentGateway'])->middleware('api.key:currencies:read');
        });
    });
});

// Streaming Engine API
Route::middleware('api.key:streaming:read')->group(function () {
    Route::get('/streaming/status/{appId}', [StreamingStatusController::class, 'show'])
        ->name('api.streaming.status');
});

// Audio Mixer API
Route::middleware('api.key:audio-mixer:read')->prefix('audio-mixer')->group(function () {
    Route::post('/init', [AudioMixerController::class, 'initialize'])
        ->middleware('api.key:audio-mixer:write')->name('api.audio-mixer.init');
    Route::post('/{mixerId}/start', [AudioMixerController::class, 'start'])
        ->middleware('api.key:audio-mixer:write')->name('api.audio-mixer.start');
    Route::post('/{mixerId}/stop', [AudioMixerController::class, 'stop'])
        ->middleware('api.key:audio-mixer:write')->name('api.audio-mixer.stop');
    Route::post('/{mixerId}/volume', [AudioMixerController::class, 'adjustVolume'])
        ->middleware('api.key:audio-mixer:write')->name('api.audio-mixer.volume');
    Route::post('/{mixerId}/music', [AudioMixerController::class, 'switchMusic'])
        ->middleware('api.key:audio-mixer:write')->name('api.audio-mixer.music');
    Route::get('/{mixerId}/status', [AudioMixerController::class, 'status'])
        ->name('api.audio-mixer.status');
    Route::get('/active', [AudioMixerController::class, 'active'])
        ->name('api.audio-mixer.active');
});

// Public audio mixer health check (no auth required)
Route::get('/audio-mixer/ffmpeg-check', [AudioMixerController::class, 'checkFFmpeg'])
    ->name('api.audio-mixer.ffmpeg-check');
Route::get('/audio-mixer/health', [AudioMixerController::class, 'health'])
    ->name('api.audio-mixer.health.public');

// Public chatbot
Route::post('/chatbot', [\App\Http\Controllers\ChatbotController::class, 'message'])
    ->name('api.chatbot.message')
    ->middleware('throttle:30,1');
Route::get('/chatbot/history', [\App\Http\Controllers\ChatbotController::class, 'history'])
    ->name('api.chatbot.history');
Route::post('/chatbot/feedback', [\App\Http\Controllers\ChatbotController::class, 'feedback'])
    ->name('api.chatbot.feedback')
    ->middleware('throttle:10,1');

// Public API documentation
Route::get('/docs', [DocsController::class, 'index'])
    ->name('api.docs');
Route::get('/docs/openapi.json', [DocsController::class, 'openapi'])
    ->name('api.docs.openapi');

// Public read-only GHC branding
Route::get('/ghc-settings', function () {
    $keys = [
        'ghc_site_name', 'ghc_tagline', 'ghc_logo', 'ghc_favicon', 'ghc_og_image', 'ghc_meta_title',
        'ghc_meta_description', 'ghc_meta_keywords', 'ghc_support_email', 'ghc_primary_color',
        'ghc_hero_badge', 'ghc_hero_title', 'ghc_hero_subtitle',
        'ghc_announce_text', 'ghc_announce_url',
    ];
    $s = \App\Models\Setting::whereIn('key', $keys)->pluck('value', 'key');

    $metaTitle = $s['ghc_meta_title'] ?? 'GHC - Enterprise Cloud Hosting';
    $metaDesc = $s['ghc_meta_description'] ?? 'Fully automated VPS & Server infrastructure platform';
    $metaKeywords = $s['ghc_meta_keywords'] ?? 'vps hosting, dedicated servers, cloud hosting, web hosting, domain registration';

    $forbidden = ['ovh', 'cheap'];
    $metaTitle = str_ireplace($forbidden, 'cloud', $metaTitle);
    $metaDesc = str_ireplace($forbidden, 'cloud', $metaDesc);
    $metaKeywords = collect(explode(',', $metaKeywords))
        ->map(fn ($k) => trim(str_ireplace(['ovh', 'cheap'], '', $k)))
        ->filter()
        ->implode(', ');

    return response()->json([
        'name' => $s['ghc_site_name'] ?? 'GHC',
        'tagline' => $s['ghc_tagline'] ?? 'Go Host Cloud',
        'logo_url' => !empty($s['ghc_logo']) ? asset('storage/' . $s['ghc_logo']) : null,
        'favicon_url' => !empty($s['ghc_favicon']) ? asset('storage/' . $s['ghc_favicon']) : null,
        'og_image_url' => !empty($s['ghc_og_image']) ? asset('storage/' . $s['ghc_og_image']) : null,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDesc,
        'meta_keywords' => $metaKeywords,
        'support_email' => $s['ghc_support_email'] ?? 'support@believoo.com',
        'primary_color' => $s['ghc_primary_color'] ?? '#00f0ff',
        'hero_badge' => $s['ghc_hero_badge'] ?? 'GHC-powered cloud marketplace',
        'hero_title' => $s['ghc_hero_title'] ?? 'Cloud, hosting and servers configured like a real provider.',
        'hero_subtitle' => $s['ghc_hero_subtitle'] ?? 'Browse real synced plans by category, compare specs, choose billing duration and buy from the client dashboard.',
        'announce_text' => $s['ghc_announce_text'] ?? '',
        'announce_url' => $s['ghc_announce_url'] ?? '',
    ]);
})->name('api.ghc.settings');
