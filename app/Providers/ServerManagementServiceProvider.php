<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\WhmcsApiService;
use App\Services\VirtualizorApiService;
use App\Services\ServerManagementService;

class ServerManagementServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register WHMCS API Service as singleton
        $this->app->singleton(WhmcsApiService::class, function ($app) {
            return new WhmcsApiService();
        });

        // Register Virtualizor API Service as singleton
        $this->app->singleton(VirtualizorApiService::class, function ($app) {
            return new VirtualizorApiService();
        });

        // Register combined Server Management Service
        $this->app->singleton(ServerManagementService::class, function ($app) {
            return new ServerManagementService(
                $app->make(WhmcsApiService::class),
                $app->make(VirtualizorApiService::class)
            );
        });

        // Merge configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/server-management.php',
            'server-management'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/server-management.php' => config_path('server-management.php'),
        ], 'server-management-config');

        // Publish views (if needed)
        $this->publishes([
            __DIR__ . '/../../resources/views/dashboard' => resource_path('views/dashboard'),
        ], 'server-management-views');

        // Register blade components
        $this->loadViewComponentsAs('server', [
            \App\View\Components\ServerStatusCard::class,
            \App\View\Components\BandwidthChart::class,
            \App\View\Components\ServerControls::class,
        ]);
    }
}
