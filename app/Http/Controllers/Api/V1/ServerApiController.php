<?php

namespace App\Http\Controllers\Api\V1;

use App\Facades\ServerManagement;
use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\ProxmoxVm;
use App\Services\HealthMonitorService;
use App\Services\ProxmoxApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServerApiController extends Controller
{
    protected ProxmoxApiService $proxmox;
    protected HealthMonitorService $healthMonitor;

    public function __construct(ProxmoxApiService $proxmox, HealthMonitorService $healthMonitor)
    {
        $this->proxmox = $proxmox;
        $this->healthMonitor = $healthMonitor;
    }

    /**
     * Get all servers list
     * GET /api/v1/servers
     */
    public function index(Request $request): JsonResponse
    {
        $this->checkPermission($request, 'vms:read');

        $vms = ProxmoxVm::with(['user', 'node'])
            ->when($request->user(), fn($q) => $q->where('user_id', $request->user()->id))
            ->get()
            ->map(fn($vm) => [
                'id' => $vm->id,
                'vmid' => $vm->vmid,
                'name' => $vm->name,
                'hostname' => $vm->hostname,
                'ip_address' => $vm->ip_address,
                'status' => $vm->status,
                'cores' => $vm->cores,
                'memory' => $vm->memory_mb,
                'disk' => $vm->disk_gb,
                'os' => $vm->os_type,
                'created_at' => $vm->created_at,
            ]);

        return response()->json([
            'success' => true,
            'data' => $vms,
            'count' => $vms->count(),
        ]);
    }

    /**
     * Get server details
     * GET /api/v1/servers/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'vms:read');

        $vm = ProxmoxVm::findOrFail($id);
        
        // Authorization check
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get live data from Proxmox
        $liveData = $this->proxmox->getVmStatus($vm->vmid);
        $stats = $this->proxmox->getVmStats($vm->vmid);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $vm->id,
                'vmid' => $vm->vmid,
                'name' => $vm->name,
                'hostname' => $vm->hostname,
                'ip_address' => $vm->ip_address,
                'status' => $liveData['status'] ?? $vm->status,
                'specs' => [
                    'cores' => $vm->cores,
                    'memory_mb' => $vm->memory_mb,
                    'disk_gb' => $vm->disk_gb,
                    'os' => $vm->os_type,
                ],
                'live_stats' => $stats,
                'uptime' => $liveData['uptime'] ?? 0,
                'health' => $this->getLastHealthCheck($vm->id),
                'created_at' => $vm->created_at,
            ],
        ]);
    }

    /**
     * Start server
     * POST /api/v1/servers/{id}/start
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'vms:control');

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->proxmox->startVm($vm->vmid);

        if ($result['success']) {
            $vm->update(['status' => 'running']);
            
            return response()->json([
                'success' => true,
                'message' => 'Server started successfully',
                'data' => [
                    'vmid' => $vm->vmid,
                    'status' => 'running',
                    'started_at' => now(),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to start server',
            'error' => $result['error'] ?? null,
        ], 400);
    }

    /**
     * Stop server
     * POST /api/v1/servers/{id}/stop
     */
    public function stop(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'vms:control');

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->proxmox->stopVm($vm->vmid);

        if ($result['success']) {
            $vm->update(['status' => 'stopped']);
            
            return response()->json([
                'success' => true,
                'message' => 'Server stopped successfully',
                'data' => [
                    'vmid' => $vm->vmid,
                    'status' => 'stopped',
                    'stopped_at' => now(),
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to stop server',
        ], 400);
    }

    /**
     * Restart server
     * POST /api/v1/servers/{id}/restart
     */
    public function restart(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'vms:control');

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = $this->proxmox->restartVm($vm->vmid);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Server restarted successfully' : 'Failed to restart server',
            'data' => [
                'vmid' => $vm->vmid,
                'restarted_at' => now(),
            ],
        ]);
    }

    /**
     * Get server stats
     * GET /api/v1/servers/{id}/stats
     */
    public function stats(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'stats:read');

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $stats = $this->proxmox->getVmStats($vm->vmid);
        $rrdData = $this->proxmox->getVmRrdData($vm->vmid, 'day');

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $stats,
                'history' => $rrdData,
                'timestamp' => now(),
            ],
        ]);
    }

    /**
     * Get bandwidth usage
     * GET /api/v1/servers/{id}/bandwidth
     */
    public function bandwidth(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'stats:read');

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $days = $request->input('days', 30);
        $bandwidth = ServerManagement::getBandwidthHistory($vm->id, $days);

        return response()->json([
            'success' => true,
            'data' => $bandwidth,
        ]);
    }

    /**
     * Get health status
     * GET /api/v1/servers/{id}/health
     */
    public function health(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'stats:read');

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $healthCheck = $this->healthMonitor->checkVmHealth($vm);
        $history = $this->healthMonitor->getVmMetricsHistory($vm->id, 24);

        return response()->json([
            'success' => true,
            'data' => [
                'current' => [
                    'status' => $healthCheck->status,
                    'message' => $healthCheck->message,
                    'metric_value' => $healthCheck->metric_value,
                    'checked_at' => $healthCheck->created_at,
                ],
                'history' => $history,
            ],
        ]);
    }

    /**
     * Execute action on server
     * POST /api/v1/servers/{id}/action
     */
    public function action(Request $request, int $id): JsonResponse
    {
        $this->checkPermission($request, 'vms:control');

        $validated = $request->validate([
            'action' => 'required|in:start,stop,restart,shutdown,suspend,resume',
        ]);

        $vm = ProxmoxVm::findOrFail($id);
        
        if ($request->user() && $vm->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $result = match ($validated['action']) {
            'start' => $this->proxmox->startVm($vm->vmid),
            'stop' => $this->proxmox->stopVm($vm->vmid),
            'restart' => $this->proxmox->restartVm($vm->vmid),
            'shutdown' => $this->proxmox->shutdownVm($vm->vmid),
            'suspend' => $this->proxmox->suspendVm($vm->vmid),
            'resume' => $this->proxmox->resumeVm($vm->vmid),
            default => ['success' => false, 'message' => 'Unknown action'],
        };

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? 'Action completed',
            'data' => [
                'vmid' => $vm->vmid,
                'action' => $validated['action'],
                'timestamp' => now(),
            ],
        ]);
    }

    /**
     * Bulk actions on multiple servers
     * POST /api/v1/servers/bulk-action
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->checkPermission($request, 'vms:control');

        $validated = $request->validate([
            'server_ids' => 'required|array',
            'server_ids.*' => 'integer|exists:proxmox_vms,id',
            'action' => 'required|in:start,stop,restart',
        ]);

        $results = [];
        
        foreach ($validated['server_ids'] as $serverId) {
            $vm = ProxmoxVm::find($serverId);
            
            if (!$vm) {
                $results[$serverId] = ['success' => false, 'error' => 'Server not found'];
                continue;
            }
            
            if ($request->user() && $vm->user_id !== $request->user()->id) {
                $results[$serverId] = ['success' => false, 'error' => 'Unauthorized'];
                continue;
            }

            $result = match ($validated['action']) {
                'start' => $this->proxmox->startVm($vm->vmid),
                'stop' => $this->proxmox->stopVm($vm->vmid),
                'restart' => $this->proxmox->restartVm($vm->vmid),
                default => ['success' => false, 'message' => 'Unknown action'],
            };

            $results[$serverId] = [
                'success' => $result['success'],
                'vmid' => $vm->vmid,
                'message' => $result['message'] ?? null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'action' => $validated['action'],
                'results' => $results,
                'summary' => [
                    'total' => count($results),
                    'success' => collect($results)->where('success', true)->count(),
                    'failed' => collect($results)->where('success', false)->count(),
                ],
            ],
        ]);
    }

    /**
     * Check permission for API key
     */
    private function checkPermission(Request $request, string $permission): void
    {
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            // Allow if authenticated via session
            return;
        }

        $key = ApiKey::where('key', $apiKey)->first();
        
        if (!$key || !$key->isValid()) {
            abort(401, 'Invalid API key');
        }

        if (!$key->hasPermission($permission)) {
            abort(403, 'Insufficient permissions');
        }

        if (!$key->isIpAllowed($request->ip())) {
            abort(403, 'IP not allowed');
        }

        $key->recordUsage();
    }

    /**
     * Get last health check for VM
     */
    private function getLastHealthCheck(int $vmId): ?array
    {
        $check = \App\Models\ServerHealthCheck::where('proxmox_vm_id', $vmId)
            ->latest()
            ->first();

        if (!$check) {
            return null;
        }

        return [
            'status' => $check->status,
            'message' => $check->message,
            'checked_at' => $check->created_at,
        ];
    }
}
