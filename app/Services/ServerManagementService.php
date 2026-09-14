<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ServerManagementService
{
    protected WhmcsApiService $whmcs;
    protected VirtualizorApiService $virtualizor;
    protected ProxmoxApiService $proxmox;

    public function __construct(WhmcsApiService $whmcs, VirtualizorApiService $virtualizor, ProxmoxApiService $proxmox)
    {
        $this->whmcs = $whmcs;
        $this->virtualizor = $virtualizor;
        $this->proxmox = $proxmox;
    }

    /**
     * Get complete dashboard data combining WHMCS and Virtualizor info
     */
    public function getDashboardData(int $whmcsClientId, array $vpsIds = []): array
    {
        $whmcsData = $this->whmcs->getDashboardData($whmcsClientId);
        $virtualizorData = $this->virtualizor->getDashboardData($vpsIds);

        // Merge data by matching server_id from WHMCS with VPS IDs from Virtualizor
        $mergedServers = [];

        foreach ($whmcsData['services'] as $service) {
            $serverId = $service['server_id'];

            // Find matching Virtualizor data
            $vpsData = null;
            foreach ($virtualizorData['servers'] as $vps) {
                if ($vps['vps_id'] == $serverId || $vps['hostname'] === $service['domain']) {
                    $vpsData = $vps;
                    break;
                }
            }

            $mergedServers[] = [
                'id' => $service['id'],
                'whmcs_service' => $service,
                'virtualizor_status' => $vpsData,
                'actions_available' => $vpsData !== null,
            ];
        }

        return [
            'services' => $mergedServers,
            'invoices' => $whmcsData['invoices'],
            'summary' => [
                'total_services' => count($mergedServers),
                'active_vps' => $virtualizorData['running'],
                'stopped_vps' => $virtualizorData['stopped'],
                'unpaid_invoices' => count($whmcsData['invoices']),
            ],
        ];
    }

    /**
     * Get detailed server status with all metrics
     */
    public function getServerDetails(int $serviceId, int $vpsId): ?array
    {
        $whmcsData = $this->whmcs->getService($serviceId);
        $virtualizorStatus = $this->virtualizor->getVpsStatus($vpsId);
        $bandwidthStats = $this->virtualizor->getBandwidthStats($vpsId, 30);

        if (!$whmcsData || !$virtualizorStatus) {
            return null;
        }

        $service = $whmcsData['products']['product'][0] ?? null;
        
        // Fetch Proxmox VM data if available (by matching hostname or IP)
        $proxmoxVm = null;
        try {
            $hostname = $virtualizorStatus['hostname'] ?? null;
            $ip = $virtualizorStatus['network']['ip'] ?? null;
            
            if ($hostname || $ip) {
                $query = \App\Models\ProxmoxVm::query();
                
                if ($hostname) {
                    $query->where('hostname', 'like', '%' . $hostname . '%');
                } elseif ($ip) {
                    $query->where('ip_address', $ip);
                }
                
                $proxmoxVm = $query->first();
                
                // If found, load related user data
                if ($proxmoxVm) {
                    $proxmoxVm->load('user');
                    $proxmoxVm = $proxmoxVm->toArray();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not fetch Proxmox VM data', [
                'vps_id' => $vpsId,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'service' => [
                'id' => $serviceId,
                'name' => $service['name'] ?? null,
                'domain' => $service['domain'] ?? null,
                'status' => $service['status'] ?? null,
                'next_due_date' => $service['nextduedate'] ?? null,
                'billing_cycle' => $service['billingcycle'] ?? null,
            ],
            'server' => $virtualizorStatus,
            'bandwidth_history' => $bandwidthStats,
            'proxmox_vm' => $proxmoxVm,
            'actions' => [
                'can_start' => $virtualizorStatus['state'] === 'stopped',
                'can_stop' => $virtualizorStatus['state'] === 'running',
                'can_restart' => $virtualizorStatus['state'] === 'running',
                'can_power_off' => true,
            ],
        ];
    }

    /**
     * Execute server action (start, stop, restart, poweroff)
     */
    public function executeServerAction(int $vpsId, string $action): array
    {
        $validActions = ['start', 'stop', 'restart', 'poweroff'];

        if (!in_array($action, $validActions)) {
            return [
                'success' => false,
                'message' => 'Invalid action. Valid actions: ' . implode(', ', $validActions),
            ];
        }

        try {
            $result = match ($action) {
                'start' => $this->virtualizor->startVps($vpsId),
                'stop' => $this->virtualizor->stopVps($vpsId),
                'restart' => $this->virtualizor->restartVps($vpsId),
                'poweroff' => $this->virtualizor->powerOffVps($vpsId),
            };

            Log::info("Server action executed", [
                'vps_id' => $vpsId,
                'action' => $action,
                'result' => $result['success'],
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Server action failed", [
                'vps_id' => $vpsId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Action failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get real-time bandwidth usage
     */
    public function getRealtimeBandwidth(int $vpsId): ?array
    {
        return $this->virtualizor->getBandwidthStats($vpsId, 1);
    }

    /**
     * Get bandwidth usage over time period
     */
    public function getBandwidthHistory(int $vpsId, int $days = 30): ?array
    {
        return $this->virtualizor->getBandwidthStats($vpsId, $days);
    }

    /**
     * Refresh all cached data for a client
     */
    public function refreshData(int $clientId, array $vpsIds = []): void
    {
        $this->whmcs->clearCache($clientId);
        $this->virtualizor->clearAllCache();

        foreach ($vpsIds as $vpsId) {
            $this->virtualizor->clearVpsCache($vpsId);
        }
    }

    /**
     * Check if all APIs are accessible
     */
    public function healthCheck(): array
    {
        $whmcsHealthy = false;
        $virtualizorHealthy = false;

        // Test WHMCS
        try {
            $result = $this->whmcs->getClientByEmail('test@example.com');
            $whmcsHealthy = $result !== null || true; // Even error response means API is reachable
        } catch (\Exception $e) {
            // API might be down
        }

        // Test Virtualizor
        try {
            $result = $this->virtualizor->getVpsList(0);
            $virtualizorHealthy = $result !== null;
        } catch (\Exception $e) {
            // API might be down
        }

        return [
            'whmcs' => $whmcsHealthy,
            'virtualizor' => $virtualizorHealthy,
            'all_healthy' => $whmcsHealthy && $virtualizorHealthy,
        ];
    }
}
