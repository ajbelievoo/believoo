<?php

use App\Http\Controllers\Api\V1\CurrencyApiController;
use App\Http\Controllers\Api\V1\LicenseApiController;
use App\Http\Controllers\Api\V1\ServerApiController;
use App\Http\Controllers\Api\V1\SupportApiController;
use App\Http\Controllers\Client\VmMigrationController;
use App\Http\Controllers\Client\ServerImportController;
use App\Http\Controllers\Api\StreamingStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes provide REST API access for BelieVoo dashboard functions.
| Authentication: Bearer Token (API Key) or Session
|
*/

Route::prefix('v1')->group(function () {
    
    // Public routes (no auth required)
    Route::get('/health', function () {
        return response()->json([
            'status' => 'healthy',
            'service' => 'BelieVoo API',
            'version' => '1.0.0',
            'timestamp' => now()->toIso8601String(),
        ]);
    });

    // ===== PAYMENT WEBHOOKS (no auth, signature verified internally) =====
    Route::post('/webhooks/cashfree', [\App\Http\Controllers\PaymentController::class, 'cashfreeWebhook'])
        ->name('api.webhooks.cashfree');
    Route::post('/webhooks/razorpay', [\App\Http\Controllers\PaymentController::class, 'razorpayWebhook'])
        ->name('api.webhooks.razorpay');

    // Authenticated routes
    Route::middleware(['auth:sanctum'])->group(function () {
        
        // ================= SERVER MANAGEMENT =================
        Route::prefix('servers')->group(function () {
            // List all servers
            Route::get('/', [ServerApiController::class, 'index']);
            
            // Get server details
            Route::get('/{id}', [ServerApiController::class, 'show']);
            
            // Server control actions
            Route::post('/{id}/start', [ServerApiController::class, 'start']);
            Route::post('/{id}/stop', [ServerApiController::class, 'stop']);
            Route::post('/{id}/restart', [ServerApiController::class, 'restart']);
            
            // Generic action endpoint
            Route::post('/{id}/action', [ServerApiController::class, 'action']);
            
            // Server stats & monitoring
            Route::get('/{id}/stats', [ServerApiController::class, 'stats']);
            Route::get('/{id}/bandwidth', [ServerApiController::class, 'bandwidth']);
            Route::get('/{id}/health', [ServerApiController::class, 'health']);
            
            // Bulk actions
            Route::post('/bulk-action', [ServerApiController::class, 'bulkAction']);
            
            // VM Migration endpoints
            Route::get('/{id}/migration/nodes', [VmMigrationController::class, 'getAvailableNodes']);
            Route::post('/{id}/migration/start', [VmMigrationController::class, 'startMigration']);
            Route::get('/{id}/migration/active', [VmMigrationController::class, 'getActiveMigration']);
        });
        
        // VM Migration progress
        Route::get('/vm-migrations/{id}/progress', [VmMigrationController::class, 'getProgress']);
        Route::get('/vm-migrations', [VmMigrationController::class, 'index']);

        // ================= SERVER IMPORT (External Server Migration) =================
        Route::prefix('server-imports')->group(function () {
            // List imports
            Route::get('/', [ServerImportController::class, 'index']);
            
            // Stats
            Route::get('/stats', [ServerImportController::class, 'getStats']);
            
            // Test connection before starting
            Route::post('/test-connection', [ServerImportController::class, 'testConnection']);
            
            // Import actions
            Route::post('/{hostingId}/start', [ServerImportController::class, 'startImport']);
            Route::get('/{hostingId}/active', [ServerImportController::class, 'getActiveImport']);
            Route::delete('/{importId}/cancel', [ServerImportController::class, 'cancelImport']);
            Route::get('/{importId}/progress', [ServerImportController::class, 'getProgress']);
        });

        // ================= LICENSE MANAGEMENT =================
        Route::prefix('licenses')->group(function () {
            // List available license types
            Route::get('/types', [LicenseApiController::class, 'types']);
            
            // User's licenses
            Route::get('/', [LicenseApiController::class, 'index']);
            Route::get('/{id}', [LicenseApiController::class, 'show']);
            
            // Order & activate
            Route::post('/order', [LicenseApiController::class, 'createOrder']);
            Route::post('/{id}/activate', [LicenseApiController::class, 'activate']);
            
            // Order status
            Route::get('/orders/{orderNumber}', [LicenseApiController::class, 'orderStatus']);
        });

        // ================= SUPPORT / TICKETS =================
        Route::prefix('tickets')->group(function () {
            // Priority reference
            Route::get('/priorities', [SupportApiController::class, 'priorities']);
            
            // CRUD operations
            Route::get('/', [SupportApiController::class, 'index']);
            Route::post('/', [SupportApiController::class, 'store']);
            Route::get('/{ticketId}', [SupportApiController::class, 'show']);
            
            // Messages
            Route::post('/{ticketId}/messages', [SupportApiController::class, 'addMessage']);
            
            // Actions
            Route::post('/{ticketId}/close', [SupportApiController::class, 'close']);
        });

        // ================= CURRENCY CONVERSION =================
        Route::prefix('currencies')->group(function () {
            // List available currencies
            Route::get('/', [CurrencyApiController::class, 'index']);
            
            // Get current exchange rates
            Route::get('/rates', [CurrencyApiController::class, 'rates']);
            
            // Convert price
            Route::post('/convert', [CurrencyApiController::class, 'convert']);
            
            // Set user currency preference
            Route::post('/set', [CurrencyApiController::class, 'setCurrency']);
            
            // Get payment gateway for currency
            Route::get('/payment-gateway', [CurrencyApiController::class, 'paymentGateway']);
        });

        // ================= USER PROFILE =================
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
        });
    });
});

// ================= API KEY MANAGEMENT =================
Route::middleware(['auth'])->prefix('api-keys')->group(function () {
    Route::get('/', function (Request $request) {
        $keys = \App\Models\ApiKey::where('user_id', $request->user()->id)
            ->orWhere('keyable_type', 'App\Models\User')
            ->where('keyable_id', $request->user()->id)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $keys->map(fn($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'key' => substr($key->key, 0, 12) . '...',
                'permissions' => $key->permissions,
                'is_active' => $key->is_active,
                'last_used_at' => $key->last_used_at,
                'created_at' => $key->created_at,
            ]),
        ]);
    });
    
    Route::post('/', function (Request $request) {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'expires_at' => 'nullable|date',
        ]);
        
        $key = \App\Models\ApiKey::create([
            'keyable_type' => 'App\Models\User',
            'keyable_id' => $request->user()->id,
            'name' => $validated['name'],
            'key' => \App\Models\ApiKey::generate(),
            'secret' => hash('sha256', random_bytes(32)),
            'permissions' => $validated['permissions'],
            'expires_at' => $validated['expires_at'] ?? now()->addYear(),
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'API key created',
            'data' => [
                'id' => $key->id,
                'name' => $key->name,
                'key' => $key->key, // Show full key only once
                'secret' => $key->secret, // Show secret only once
            ],
        ], 201);
    });
    
    Route::delete('/{id}', function (Request $request, int $id) {
        $key = \App\Models\ApiKey::where('id', $id)
            ->where(function($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhere('keyable_id', $request->user()->id);
            })
            ->firstOrFail();
            
        $key->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'API key revoked',
        ]);
    });
});

// Streaming Engine API
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/streaming/status/{appId}', [StreamingStatusController::class, 'show'])
        ->name('api.streaming.status');
});

// Audio Mixer API - Backend Audio Mixing for BelieVoo Live
use App\Http\Controllers\Api\AudioMixerController;

Route::middleware(['auth:sanctum'])->prefix('audio-mixer')->group(function () {
    // Initialize and start mixer
    Route::post('/init', [AudioMixerController::class, 'initialize'])
        ->name('api.audio-mixer.init');
    
    // Start/Stop mixer
    Route::post('/{mixerId}/start', [AudioMixerController::class, 'start'])
        ->name('api.audio-mixer.start');
    Route::post('/{mixerId}/stop', [AudioMixerController::class, 'stop'])
        ->name('api.audio-mixer.stop');
    
    // Real-time volume control
    Route::post('/{mixerId}/volume', [AudioMixerController::class, 'adjustVolume'])
        ->name('api.audio-mixer.volume');
    
    // Switch music track
    Route::post('/{mixerId}/music', [AudioMixerController::class, 'switchMusic'])
        ->name('api.audio-mixer.music');
    
    // Get status
    Route::get('/{mixerId}/status', [AudioMixerController::class, 'status'])
        ->name('api.audio-mixer.status');
    
    // List active mixers
    Route::get('/active', [AudioMixerController::class, 'active'])
        ->name('api.audio-mixer.active');
    
    // Health checks
    Route::get('/ffmpeg-check', [AudioMixerController::class, 'checkFFmpeg'])
        ->name('api.audio-mixer.ffmpeg-check');
    Route::get('/health', [AudioMixerController::class, 'health'])
        ->name('api.audio-mixer.health');
});

// Public audio mixer health check (no auth required)
Route::get('/audio-mixer/health', [AudioMixerController::class, 'health'])
    ->name('api.audio-mixer.health.public');

// Public read-only branding for ghc.believoo.com (controlled from Admin > Settings > GHC Site)
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

    // Sanitize upstream provider references from public metadata
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
