<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class VirtualizorApiService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiPass;
    protected int $cacheTtl;
    protected int $port;

    public function __construct()
    {
        // Read from database settings first, fallback to .env/config
        // (string) cast ensures null is never assigned to typed string properties (PHP 8 strict typing)
        $this->baseUrl = rtrim(
            (string) (Setting::getValue('virtualizor_base_url') ?: config('server-management.virtualizor.base_url', '')),
            '/'
        );
        $this->apiKey = (string) (Setting::getValue('virtualizor_api_key') ?: config('server-management.virtualizor.api_key', ''));
        $this->apiPass = (string) (Setting::getValue('virtualizor_api_pass') ?: config('server-management.virtualizor.api_pass', ''));
        $this->port = (int) (Setting::getValue('virtualizor_port') ?: config('server-management.virtualizor.port', 4085));
        $this->cacheTtl = (int) (Setting::getValue('virtualizor_cache_ttl') ?: config('server-management.virtualizor.cache_ttl', 60));
    }

    /**
     * Check if Virtualizor API credentials are configured.
     * Returns false when apiKey or apiPass is empty — used to show a graceful
     * "not configured" state instead of making API calls with empty credentials.
     */
    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->apiPass !== '';
    }

    /**
     * Generate API signature
     */
    protected function generateSignature(array $params): array
    {
        $timestamp = time();
        $params['act'] = $params['act'] ?? 'listvs';
        $params['api_key'] = $this->apiKey;
        $params['api_pass'] = $this->apiPass;
        $params['timestamp'] = $timestamp;

        // Generate signature
        $queryString = http_build_query($params);
        $signature = hash_hmac('sha256', $queryString, $this->apiPass);

        $params['signature'] = $signature;

        return $params;
    }

    /**
     * Make a request to Virtualizor API
     */
    protected function request(string $act, array $params = []): ?array
    {
        try {
            $params['act'] = $act;
            $params = $this->generateSignature($params);

            $url = "{$this->baseUrl}:{$this->port}/index.php?";

            $response = Http::withOptions([
                'verify' => false, // For self-signed certs on internal servers
            ])
            ->timeout(30)
            ->asForm()
            ->post($url, $params);

            if (!$response->successful()) {
                Log::error('Virtualizor API request failed', [
                    'act' => $act,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['error']) && !empty($data['error'])) {
                Log::error('Virtualizor API returned error', [
                    'act' => $act,
                    'error' => $data['error'],
                ]);
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Virtualizor API exception', [
                'act' => $act,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all VPS for a user
     */
    public function getVpsList(int $userId = 0): ?array
    {
        $cacheKey = $userId > 0 ? "virtualizor_vps_list_user_{$userId}" : 'virtualizor_all_vps';

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            $params = [];
            if ($userId > 0) {
                $params['uid'] = $userId;
            }

            return $this->request('listvs', $params);
        });
    }

    /**
     * Get VPS details
     */
    public function getVpsDetails(int $vpsId): ?array
    {
        $cacheKey = "virtualizor_vps_details_{$vpsId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($vpsId) {
            $result = $this->request('listvs', [
                'vpsid' => $vpsId,
            ]);

            return $result['vs'][$vpsId] ?? null;
        });
    }

    /**
     * Get VPS status and resource usage
     */
    public function getVpsStatus(int $vpsId): ?array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'status',
        ]);

        if (!$result) {
            return null;
        }

        return [
            'vps_id' => $vpsId,
            'status' => $this->mapStatus($result['status'] ?? 'unknown'),
            'state' => $result['state'] ?? 'unknown',
            'hostname' => $result['hostname'] ?? null,
            'os_name' => $result['os_name'] ?? null,
            'resources' => [
                'cpu' => [
                    'cores' => $result['cores'] ?? 0,
                    'usage_percent' => $result['cpu'] ?? 0,
                ],
                'ram' => [
                    'used' => $this->formatBytes($result['ram_used'] ?? 0),
                    'total' => $this->formatBytes($result['ram'] ?? 0),
                    'used_bytes' => $result['ram_used'] ?? 0,
                    'total_bytes' => $result['ram'] ?? 0,
                    'usage_percent' => $result['ram_percent'] ?? 0,
                ],
                'disk' => [
                    'used' => $this->formatBytes($result['disk_used'] ?? 0),
                    'total' => $this->formatBytes($result['disk'] ?? 0),
                    'used_bytes' => $result['disk_used'] ?? 0,
                    'total_bytes' => $result['disk'] ?? 0,
                    'usage_percent' => $result['disk_percent'] ?? 0,
                ],
                'bandwidth' => [
                    'used' => $this->formatBytes($result['bandwidth_used'] ?? 0),
                    'total' => $this->formatBytes($result['bandwidth'] ?? 0),
                    'used_bytes' => $result['bandwidth_used'] ?? 0,
                    'total_bytes' => $result['bandwidth'] ?? 0,
                    'usage_percent' => $result['bandwidth_percent'] ?? 0,
                ],
            ],
            'network' => [
                'ip' => $result['ip'] ?? null,
                'ipv6' => $result['ipv6'] ?? null,
                'gateway' => $result['gateway'] ?? null,
                'netmask' => $result['netmask'] ?? null,
            ],
            'uptime' => $result['uptime'] ?? null,
            'time' => $result['time'] ?? null,
        ];
    }

    /**
     * Start a VPS
     */
    public function startVps(int $vpsId): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'start',
        ]);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'VPS started successfully' : 'Failed to start VPS'),
            'data' => $result,
        ];
    }

    /**
     * Stop a VPS
     */
    public function stopVps(int $vpsId): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'stop',
        ]);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'VPS stopped successfully' : 'Failed to stop VPS'),
            'data' => $result,
        ];
    }

    /**
     * Restart a VPS
     */
    public function restartVps(int $vpsId): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'restart',
        ]);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'VPS restarted successfully' : 'Failed to restart VPS'),
            'data' => $result,
        ];
    }

    /**
     * Power off a VPS (hard stop)
     */
    public function powerOffVps(int $vpsId): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'poweroff',
        ]);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'VPS powered off successfully' : 'Failed to power off VPS'),
            'data' => $result,
        ];
    }

    /**
     * Reinstall VPS OS
     */
    public function reinstallVps(int $vpsId, ?int $osId = null): array
    {
        $params = [
            'vpsid' => $vpsId,
            'action' => 'reinstall',
        ];
        
        if ($osId) {
            $params['osid'] = $osId;
        }
        
        $result = $this->request('vs', $params);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'OS reinstallation started. This may take a few minutes.' : 'Failed to start OS reinstallation'),
            'data' => $result,
        ];
    }

    /**
     * Boot VPS into rescue mode
     */
    public function rescueVps(int $vpsId): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'rescue',
        ]);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'VPS is booting into rescue mode' : 'Failed to boot into rescue mode'),
            'data' => $result,
        ];
    }

    /**
     * Reset VPS root password
     */
    public function resetPassword(int $vpsId): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'resetpass',
        ]);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'Root password reset successfully. Check your email for the new password.' : 'Failed to reset password'),
            'data' => $result,
        ];
    }

    /**
     * Change VPS hostname
     */
    public function changeHostname(int $vpsId, string $hostname): array
    {
        $result = $this->request('vs', [
            'vpsid' => $vpsId,
            'action' => 'hostname',
            'hostname' => $hostname,
        ]);

        $this->clearVpsCache($vpsId);

        return [
            'success' => $result !== null && empty($result['error']),
            'message' => $result['message'] ?? ($result ? 'Hostname updated successfully' : 'Failed to update hostname'),
            'data' => $result,
        ];
    }

    /**
     * Get bandwidth usage statistics
     */
    public function getBandwidthStats(int $vpsId, int $days = 30): ?array
    {
        $result = $this->request('bandwidth', [
            'vpsid' => $vpsId,
            'show' => $days,
        ]);

        if (!$result) {
            return null;
        }

        return [
            'vps_id' => $vpsId,
            'total_in' => $this->formatBytes($result['total_in'] ?? 0),
            'total_out' => $this->formatBytes($result['total_out'] ?? 0),
            'total' => $this->formatBytes(($result['total_in'] ?? 0) + ($result['total_out'] ?? 0)),
            'daily_stats' => $result['bandwidth'] ?? [],
        ];
    }

    /**
     * Get all VPS with full status for dashboard
     */
    public function getDashboardData(array $vpsIds = []): array
    {
        $servers = [];

        foreach ($vpsIds as $vpsId) {
            $details = $this->getVpsStatus($vpsId);
            if ($details) {
                $servers[] = $details;
            }
        }

        return [
            'servers' => $servers,
            'total' => count($servers),
            'running' => count(array_filter($servers, fn($s) => $s['state'] === 'running')),
            'stopped' => count(array_filter($servers, fn($s) => $s['state'] === 'stopped')),
        ];
    }

    /**
     * Map Virtualizor status to standardized status
     */
    protected function mapStatus(string $status): string
    {
        return match ($status) {
            '1', 'active' => 'active',
            '0', 'inactive' => 'inactive',
            'suspended' => 'suspended',
            default => 'unknown',
        };
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Clear cache for a VPS
     */
    public function clearVpsCache(int $vpsId): void
    {
        Cache::forget("virtualizor_vps_details_{$vpsId}");
        Cache::forget("virtualizor_vps_status_{$vpsId}");
    }

    /**
     * Clear all VPS cache
     */
    public function clearAllCache(): void
    {
        Cache::forget('virtualizor_all_vps');
    }

    /**
     * Suspend a VPS (Billing suspension)
     */
    public function suspendVps(int $vpsId): bool
    {
        try {
            Log::info("Virtualizor: Suspending VPS {$vpsId}");

            $result = $this->request('vs', [
                'vpsid' => $vpsId,
                'action' => 'suspend',
            ]);

            if ($result !== null && empty($result['error'])) {
                Log::info("Virtualizor: VPS {$vpsId} suspended successfully");
                $this->clearVpsCache($vpsId);
                return true;
            }

            Log::error("Virtualizor: Failed to suspend VPS {$vpsId}", [
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Virtualizor: Exception suspending VPS {$vpsId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Unsuspend a VPS (Restore after payment)
     */
    public function unsuspendVps(int $vpsId): bool
    {
        try {
            Log::info("Virtualizor: Unsuspending VPS {$vpsId}");

            $result = $this->request('vs', [
                'vpsid' => $vpsId,
                'action' => 'unsuspend',
            ]);

            if ($result !== null && empty($result['error'])) {
                Log::info("Virtualizor: VPS {$vpsId} unsuspended successfully");
                $this->clearVpsCache($vpsId);
                return true;
            }

            Log::error("Virtualizor: Failed to unsuspend VPS {$vpsId}", [
                'error' => $result['error'] ?? 'Unknown error',
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Virtualizor: Exception unsuspending VPS {$vpsId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
