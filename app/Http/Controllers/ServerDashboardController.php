<?php

namespace App\Http\Controllers;

use App\Facades\ServerManagement;
use App\Models\ProxmoxVm;
use App\Models\UserHosting;
use App\Services\ProxmoxApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerDashboardController extends Controller
{
    /**
     * Display the server dashboard page
     */
    public function index()
    {
        return view('dashboard.servers');
    }

    /**
     * Get dashboard data via API
     */
    public function getData(Request $request): JsonResponse
    {
        $whmcsClientId = Auth::user()->whmcs_client_id ?? $request->input('client_id');
        $vpsIds = $request->input('vps_ids', []);

        if (!$whmcsClientId) {
            return response()->json([
                'error' => 'WHMCS Client ID not configured for this user',
            ], 400);
        }

        $data = ServerManagement::getDashboardData($whmcsClientId, $vpsIds);

        return response()->json($data);
    }

    /**
     * Get detailed server information
     */
    public function getServerDetails(int $serviceId, int $vpsId): JsonResponse
    {
        $details = ServerManagement::getServerDetails($serviceId, $vpsId);

        if (!$details) {
            return response()->json([
                'error' => 'Server not found or API error',
            ], 404);
        }

        return response()->json($details);
    }

    /**
     * Execute a server action
     */
    public function executeAction(Request $request, int $vpsId): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:start,stop,restart,poweroff',
        ]);

        $action = $request->input('action');
        $result = ServerManagement::executeServerAction($vpsId, $action);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Get real-time bandwidth for a VPS
     */
    public function getBandwidth(int $vpsId, Request $request): JsonResponse
    {
        $days = $request->input('days', 1);
        $bandwidth = ServerManagement::getBandwidthHistory($vpsId, $days);

        if (!$bandwidth) {
            return response()->json([
                'error' => 'Failed to retrieve bandwidth data',
            ], 500);
        }

        return response()->json($bandwidth);
    }

    /**
     * Refresh all data (clear cache)
     */
    public function refreshData(Request $request): JsonResponse
    {
        $clientId = Auth::user()->whmcs_client_id ?? $request->input('client_id');
        $vpsIds = $request->input('vps_ids', []);

        ServerManagement::refreshData($clientId, $vpsIds);

        return response()->json([
            'message' => 'Cache cleared successfully',
        ]);
    }

    /**
     * Health check for API connections
     */
    public function healthCheck(): JsonResponse
    {
        $status = ServerManagement::healthCheck();

        return response()->json([
            'status' => $status['all_healthy'] ? 'healthy' : 'degraded',
            'services' => $status,
        ]);
    }

    /**
     * Get NoVNC Console URL for a VPS
     * Returns the WebSocket URL and ticket for in-browser terminal access
     */
    public function getConsoleUrl(int $vpsId): JsonResponse
    {
        $user = Auth::user();

        // Verify user owns this VPS
        $hosting = UserHosting::where('vps_id', $vpsId)
            ->where('user_id', $user->id)
            ->first();

        if (!$hosting) {
            return response()->json([
                'success' => false,
                'message' => 'VPS not found or access denied',
            ], 403);
        }

        try {
            // Get Proxmox VM record to find node
            $proxmoxVm = ProxmoxVm::where('vmid', $vpsId)->first();
            $node = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');

            // Build Proxmox API service
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $node)->first();

            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $node);
            } else {
                $proxmox = app(ProxmoxApiService::class);
            }

            // Get VNC proxy ticket from Proxmox
            $vncData = $proxmox->createVncProxy($vpsId);

            if (!$vncData || empty($vncData['ticket'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create VNC session. Please ensure the VM is running.',
                ], 500);
            }

            // Build NoVNC URL
            $host = $vncData['host'];
            $port = $vncData['port'];
            $ticket = $vncData['ticket'];
            $baseUrl = rtrim($vncData['base_url'], '/');

            // NoVNC WebSocket URL format: wss://host:port/api2/nodes/node/qemu/vmid/vncwebsocket?port=port&vncticket=ticket
            $wsUrl = "wss://{$host}:8006/api2/nodes/{$node}/qemu/{$vpsId}/vncwebsocket?port={$port}&vncticket={$ticket}";

            // Direct NoVNC viewer URL (using Proxmox's built-in NoVNC)
            $novncUrl = "{$baseUrl}/?console=kvm&novnc=1&vmid={$vpsId}&vmname={$hosting->server_hostname}&node={$node}&resize=off&path=api2/nodes/{$node}/qemu/{$vpsId}/vncwebsocket/port={$port}/vncticket={$ticket}";

            return response()->json([
                'success' => true,
                'console_url' => $novncUrl,
                'websocket_url' => $wsUrl,
                'ticket' => $ticket,
                'host' => $host,
                'port' => $port,
                'node' => $node,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate console URL: ' . $e->getMessage(),
            ], 500);
        }
    }
}
