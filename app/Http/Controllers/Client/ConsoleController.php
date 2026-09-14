<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UserHosting;
use App\Models\ProxmoxVm;
use App\Services\ConsoleProxyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Console Controller
 * 
 * Provides secure, masked console access to Proxmox VMs via reverse proxy.
 * All console interactions go through this controller to hide Proxmox host details.
 */
class ConsoleController extends Controller
{
    /**
     * Create a secure console session for the authenticated user
     */
    public function createSession(Request $request, int $hostingId)
    {
        try {
            // Verify user owns this hosting
            $hosting = UserHosting::where('id', $hostingId)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $vpsId = $hosting->vps_id;
            if (!$vpsId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No VM linked to this hosting',
                ], 404);
            }

            // Get Proxmox VM record to find node
            $proxmoxVm = ProxmoxVm::where('vmid', $vpsId)->first();
            $nodeName = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');

            // Build ConsoleProxyService for this node
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $nodeName)->first();
            
            if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
                $proxyService = ConsoleProxyService::forNode(
                    $proxmoxUrl,
                    $proxmoxNode->getDecryptedApiToken(),
                    $nodeName
                );
            } else {
                $proxyService = app(ConsoleProxyService::class);
            }

            // Create secure console session
            $session = $proxyService->createSecureConsoleSession((int) $vpsId, Auth::id());

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create console session. Please ensure the VM is running.',
                ], 500);
            }

            Log::info('Console session created', [
                'user_id' => Auth::id(),
                'hosting_id' => $hostingId,
                'vmid' => $vpsId,
                'session_id' => substr($session['session_id'], 0, 8) . '...',
            ]);

            return response()->json([
                'success' => true,
                'console_url' => $session['console_url'],
                'websocket_url' => $session['websocket_url'],
                'session_id' => $session['session_id'],
                'expires_in' => $session['expires_in'],
                'domain' => $session['domain'],
            ]);

        } catch (\Exception $e) {
            Log::error('Console session creation failed', [
                'user_id' => Auth::id(),
                'hosting_id' => $hostingId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create console session: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serve the noVNC console page
     */
    public function showConsole(string $sessionId)
    {
        try {
            $proxyService = app(ConsoleProxyService::class);
            
            // Validate session
            $sessionData = $proxyService->getSessionData($sessionId);
            
            if (!$sessionData) {
                return view('client.console.error', [
                    'message' => 'Console session expired or invalid. Please refresh and try again.',
                ]);
            }

            // Verify user owns this session
            if ($sessionData['user_id'] !== Auth::id()) {
                return view('client.console.error', [
                    'message' => 'Access denied. This console session belongs to another user.',
                ]);
            }

            // Get the noVNC HTML
            $html = $proxyService->getNoVncHtml($sessionId);

            if (!$html) {
                return view('client.console.error', [
                    'message' => 'Failed to load console interface. Please try again.',
                ]);
            }

            // Return raw HTML
            return response($html)->header('Content-Type', 'text/html');

        } catch (\Exception $e) {
            Log::error('Console display failed', [
                'session_id' => substr($sessionId, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);

            return view('client.console.error', [
                'message' => 'An error occurred while loading the console.',
            ]);
        }
    }

    /**
     * WebSocket proxy endpoint for VNC connection
     */
    public function websocketProxy(Request $request, string $sessionId)
    {
        try {
            $proxyService = app(ConsoleProxyService::class);
            
            // Validate and refresh session
            $sessionData = $proxyService->getSessionData($sessionId);
            
            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expired',
                ], 401);
            }

            // Refresh session to extend expiration during active use
            $proxyService->refreshSession($sessionId);

            // Get internal credentials for proxy
            $internal = $sessionData['_internal'] ?? [];
            
            if (empty($internal['ticket']) || empty($internal['pve_auth_cookie'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid session credentials',
                ], 401);
            }

            // Build Proxmox WebSocket URL
            $proxmoxNode = \App\Models\ProxmoxNode::where('name', $sessionData['node'])->first();
            $proxmoxHost = $proxmoxNode?->hostname ?? config('proxmox.host', 'localhost');
            $proxmoxPort = $proxmoxNode?->port ?? 8006;
            
            $wsUrl = "wss://{$proxmoxHost}:{$proxmoxPort}/api2/nodes/{$sessionData['node']}/qemu/{$sessionData['vmid']}/vncwebsocket";
            $wsUrl .= "?port={$internal['port']}&vncticket=" . urlencode($internal['ticket']);

            // Return proxy connection details
            return response()->json([
                'success' => true,
                'websocket_url' => $wsUrl,
                'pve_auth_cookie' => $internal['pve_auth_cookie'],
            ]);

        } catch (\Exception $e) {
            Log::error('WebSocket proxy failed', [
                'session_id' => substr($sessionId, 0, 8) . '...',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'WebSocket proxy error',
            ], 500);
        }
    }

    /**
     * Invalidate a console session
     */
    public function destroySession(string $sessionId)
    {
        try {
            $proxyService = app(ConsoleProxyService::class);
            
            // Validate session belongs to current user
            $sessionData = $proxyService->getSessionData($sessionId);
            
            if ($sessionData && $sessionData['user_id'] === Auth::id()) {
                $proxyService->invalidateSession($sessionId);
            }

            return response()->json([
                'success' => true,
                'message' => 'Session terminated',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check console session health
     */
    public function sessionHealth(string $sessionId)
    {
        try {
            $proxyService = app(ConsoleProxyService::class);
            $sessionData = $proxyService->getSessionData($sessionId);

            if (!$sessionData) {
                return response()->json([
                    'success' => false,
                    'healthy' => false,
                    'message' => 'Session expired',
                ]);
            }

            // Check if user still owns the VM
            $vmid = $sessionData['vmid'];
            $hosting = UserHosting::where('vps_id', $vmid)
                ->where('user_id', Auth::id())
                ->first();

            if (!$hosting) {
                // Invalidate session if user no longer owns the VM
                $proxyService->invalidateSession($sessionId);
                
                return response()->json([
                    'success' => false,
                    'healthy' => false,
                    'message' => 'Access revoked',
                ]);
            }

            // Refresh session
            $proxyService->refreshSession($sessionId);

            return response()->json([
                'success' => true,
                'healthy' => true,
                'expires_at' => $sessionData['expires_at'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'healthy' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
