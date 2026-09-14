<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Route;

/**
 * Trait for Filament Edit/View/Create pages where the resource index route
 * conflicts with a custom admin route. Safely resolves the index URL by
 * falling back to the custom admin route when the Filament index doesn't exist.
 */
trait RedirectsToAdminIndex
{
    /**
     * Map of Filament resource slug → custom admin route name.
     */
    protected static array $adminRouteMap = [
        'agreements'           => 'admin.agreements.index',
        'agreement-requests'   => 'admin.agreements.index',
        'orders'               => 'admin.orders.index',
        'invoices'             => 'admin.invoices.index',
        'tickets'              => 'admin.tickets.index',
        'projects'             => 'admin.projects.index',
        'users'                => 'admin.users.index',
        'leads'                => 'admin.leads.index',
        'services'             => 'admin.services.index',
        'amc-subscriptions'    => 'admin.amc-subscriptions.index',
        'exchange-rates'       => 'admin.exchange-rates.index',
        'streaming-plans'      => 'admin.streaming-plans.index',
        'portfolios'           => 'admin.portfolio.index',
        'stores'               => 'admin.stores.index',
        'teams'                => 'admin.teams.index',
        'settings'             => 'admin.settings.index',
        'user-hostings'        => 'admin.hostings.index',
    ];

    protected function getAdminIndexUrl(): string
    {
        $resource = static::getResource();
        $slug = $resource::getSlug();

        // Try custom admin route first
        if (isset(static::$adminRouteMap[$slug])) {
            $routeName = static::$adminRouteMap[$slug];
            if (Route::has($routeName)) {
                return route($routeName);
            }
        }

        // Try Filament index route
        try {
            return $resource::getUrl('index');
        } catch (\Exception $e) {
            return url('/admin');
        }
    }

    public function getBreadcrumbs(): array
    {
        $resource = static::getResource();

        return [
            $this->getAdminIndexUrl() => $resource::getBreadcrumb(),
            '#' => $this->getBreadcrumb(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getAdminIndexUrl();
    }
}
