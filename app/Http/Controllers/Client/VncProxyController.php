<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UserHosting;
use App\Models\ProxmoxVm;
use App\Models\ProxmoxNode;
use App\Services\ProxmoxApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VncProxyController extends Controller
{
    /**
     * Create a VNC proxy ticket for the client's VM
     * Uses password-based auth (required for noVNC, API tokens don't work)
     */
    public function create(Request $request, int $hostingId)
    {
        $hosting = UserHosting::where('id', $hostingId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $vpsId = $hosting->vps_id;
        if (!$vpsId) {
            return response()->json(['error' => 'No VM linked to this hosting'], 404);
        }

        // Get Proxmox VM and node
        $proxmoxVm = ProxmoxVm::where('vmid', $vpsId)->first();
        $nodeName  = $proxmoxVm?->node ?? config('proxmox.node', 'ns548195');

        // Build Proxmox API service
        $proxmoxNode = ProxmoxNode::where('name', $nodeName)->first();
        if ($proxmoxNode && $proxmoxNode->getDecryptedApiToken()) {
            $proxmoxUrl = 'https://' . $proxmoxNode->hostname . ':' . $proxmoxNode->port;
            $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $proxmoxNode->getDecryptedApiToken(), $nodeName);
        } else {
            $proxmox = app(ProxmoxApiService::class);
        }

        // Use password-based VNC ticket (required for noVNC auth)
        $vncData = $proxmox->createVncTicket((int) $vpsId);

        if (!$vncData || !$vncData['ticket']) {
            return response()->json([
                'error' => 'Failed to create VNC ticket. Check Proxmox password in settings.',
            ], 500);
        }

        $host = $vncData['host'];
        $port = $vncData['port'];

        // Build noVNC URL - use PVEAuthCookie for authentication
        $novncUrl = $vncData['base_url'] . '/?console=kvm'
            . '&novnc=1'
            . '&vmid=' . $vpsId
            . '&node=' . $nodeName
            . '&resize=scale'
            . '&ticket=' . urlencode($vncData['ticket']);

        return response()->json([
            'success'    => true,
            'ticket'     => $vncData['ticket'],
            'pve_ticket' => $vncData['pve_ticket'],
            'port'       => $port,
            'host'       => $host,
            'node'       => $nodeName,
            'base_url'   => $vncData['base_url'],
            'novnc_url'  => $novncUrl,
        ]);
    }
}
