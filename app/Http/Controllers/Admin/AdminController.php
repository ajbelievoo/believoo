<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Models\Agreement;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\UserDomain;
use App\Models\ProxmoxVm;
use App\Models\VpsPlan;
use App\Services\ProxmoxApiService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected ProxmoxApiService $proxmox;

    public function __construct(ProxmoxApiService $proxmox)
    {
        $this->proxmox = $proxmox;
    }

    public function dashboard()
    {
        $stats = [
            'users' => User::count(),
            'orders' => Order::count(),
            'revenue' => Order::where('status', 'paid')->sum('amount'),
            'agreements' => Agreement::count(),
            'invoices' => Invoice::count(),
            'projects' => Project::count(),
            'tickets' => Ticket::where('status', 'open')->count(),
        ];

        // Resource Monitoring Data
        $proxmoxResources = $this->proxmox->getNodeResourceUsage();
        $totalDomains = UserDomain::count();
        
        // === LIVE STATS FROM PROXMOX API ===
        // Fetch actual VMs from Proxmox (not just database)
        $liveVms = $this->proxmox->listVms();
        $liveVmIds = collect($liveVms)->pluck('vmid')->toArray();
        
        // Get LIVE VM counts
        $totalVps = count($liveVms);
        $activeVps = collect($liveVms)->where('status', 'running')->count();

        // Inventory Status - Calculate remaining sellable capacity
        $nodeTotalRam = 64 * 1024 * 1024 * 1024; // 64GB in bytes
        $nodeTotalDisk = 900 * 1024 * 1024 * 1024; // 900GB in bytes
        $nodeTotalBandwidth = 5 * 1024 * 1024 * 1024 * 1024; // 5TiB in bytes
        
        // Sync database with Proxmox - remove VMs that don't exist in Proxmox anymore
        ProxmoxVm::whereNotNull('vmid')
            ->where('vmid', '>', 0)
            ->whereNotIn('vmid', $liveVmIds)
            ->delete();
        
        // Calculate used resources from LIVE Proxmox VMs
        $usedRam = 0;
        $usedDisk = 0;
        $activeVmCount = 0;
        
        foreach ($liveVms as $vm) {
            // Get VM config for accurate specs
            $config = $this->proxmox->getVmConfig($vm['vmid']);
            if ($config) {
                $usedRam += ($config['memory'] ?? $config['maxmem'] ?? 0);
                // Disk calculation from config
                if (isset($config['scsi0']) || isset($config['ide0']) || isset($config['sata0'])) {
                    $diskStr = $config['scsi0'] ?? $config['ide0'] ?? $config['sata0'] ?? '';
                    if (preg_match('/size=(\d+)G/', $diskStr, $matches)) {
                        $usedDisk += $matches[1] * 1024 * 1024 * 1024; // Convert GB to bytes
                    }
                }
                $activeVmCount++;
            }
        }
        
        // Fallback: If API returns 0, use database as backup
        if ($usedRam == 0 && $activeVmCount == 0) {
            $activeVms = ProxmoxVm::whereNotNull('vmid')
                ->where('vmid', '>', 0)
                ->get();
            $usedRam = $activeVms->sum('memory_mb') * 1024 * 1024;
            $usedDisk = $activeVms->sum('disk_gb') * 1024 * 1024 * 1024;
            $activeVmCount = $activeVms->count();
        }
        
        // Debug: Log live vs database comparison
        \Log::info('Inventory Live Stats', [
            'proxmox_vm_count' => count($liveVms),
            'active_vm_count' => $activeVmCount,
            'used_ram_gb' => round($usedRam / 1024 / 1024 / 1024, 1),
            'used_disk_gb' => round($usedDisk / 1024 / 1024 / 1024, 1),
        ]);
        
        // Calculate remaining resources
        $remainingRam = max(0, $nodeTotalRam - $usedRam);
        $remainingDisk = max(0, $nodeTotalDisk - $usedDisk);
        
        // Calculate how many VPS plans can fit in remaining resources
        $vpsPlans = VpsPlan::where('is_active', true)->where('is_sold_out', false)->get();
        $sellablePlans = [];
        
        foreach ($vpsPlans as $plan) {
            $planRamBytes = $plan->memory_gb * 1024 * 1024 * 1024;
            $planDiskBytes = $plan->disk_gb * 1024 * 1024 * 1024;
            
            $canFitRam = floor($remainingRam / $planRamBytes);
            $canFitDisk = floor($remainingDisk / $planDiskBytes);
            $canFit = min($canFitRam, $canFitDisk);
            
            if ($canFit > 0) {
                $sellablePlans[] = [
                    'plan' => $plan,
                    'can_sell' => (int) $canFit,
                ];
            }
        }
        
        // Sort by can_sell count descending
        usort($sellablePlans, fn($a, $b) => $b['can_sell'] <=> $a['can_sell']);
        
        $inventoryStatus = [
            'total_ram_gb' => 64,
            'used_ram_gb' => round($usedRam / 1024 / 1024 / 1024, 1),
            'remaining_ram_gb' => round($remainingRam / 1024 / 1024 / 1024, 1),
            'ram_percent' => round(($usedRam / $nodeTotalRam) * 100, 1),
            'total_disk_gb' => 900,
            'used_disk_gb' => round($usedDisk / 1024 / 1024 / 1024, 1),
            'remaining_disk_gb' => round($remainingDisk / 1024 / 1024 / 1024, 1),
            'disk_percent' => round(($usedDisk / $nodeTotalDisk) * 100, 1),
            'total_bandwidth_tb' => 5,
            'bandwidth_used_tb' => 0.1, // Placeholder - would need actual usage from OVH API
            'bandwidth_percent' => 2, // Placeholder
            'total_vms' => $totalVps,
            'sellable_plans' => $sellablePlans,
            'low_stock' => ($remainingRam / $nodeTotalRam) < 0.15 || ($remainingDisk / $nodeTotalDisk) < 0.15,
        ];

        // Check for resource alerts (RAM > 85%)
        $resourceAlert = null;
        if ($proxmoxResources['available'] && $proxmoxResources['ram_percent'] > 85) {
            $resourceAlert = [
                'type' => 'warning',
                'message' => "Proxmox Node RAM usage is at {$proxmoxResources['ram_percent']}%! Consider optimizing resources or upgrading.",
                'icon' => 'fa-exclamation-triangle',
            ];
        }

        $recentOrders = Order::with('user')->latest()->take(5)->get();
        $recentUsers = User::latest()->take(5)->get();
        $pendingTickets = Ticket::with('user')->where('status', 'open')->take(5)->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentOrders',
            'recentUsers',
            'pendingTickets',
            'proxmoxResources',
            'totalDomains',
            'activeVps',
            'totalVps',
            'resourceAlert',
            'inventoryStatus'
        ));
    }

    public function markAllNotificationsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    }

    public function markNotificationRead(string $id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if (!$notification) {
            return back();
        }

        $notification->markAsRead();

        $data = $notification->data ?? [];
        $url = $data['action_url'] ?? $data['url'] ?? null;
        if (!$url && isset($data['actions']) && is_array($data['actions'])) {
            foreach ($data['actions'] as $action) {
                if (!empty($action['url'])) {
                    $url = $action['url'];
                    break;
                }
            }
        }
        if (!$url && isset($data['ticket_id'])) {
            $url = route('admin.tickets.index');
        }

        return $url ? redirect($url) : back();
    }
}
