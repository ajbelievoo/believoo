<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getDashboardData(int $whmcsClientId, array $vpsIds = [])
 * @method static ?array getServerDetails(int $serviceId, int $vpsId)
 * @method static array executeServerAction(int $vpsId, string $action)
 * @method static ?array getRealtimeBandwidth(int $vpsId)
 * @method static ?array getBandwidthHistory(int $vpsId, int $days = 30)
 * @method static void refreshData(int $clientId, array $vpsIds = [])
 * @method static array healthCheck()
 *
 * @see \App\Services\ServerManagementService
 */
class ServerManagement extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\ServerManagementService::class;
    }
}
