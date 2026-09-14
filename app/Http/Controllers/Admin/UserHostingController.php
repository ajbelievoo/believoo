<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProxmoxNode;
use App\Models\ProxmoxVm;
use App\Models\UserHosting;
use App\Models\User;
use App\Models\Service;
use App\Models\VpsPlan;
use App\Services\ProxmoxApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserHostingController extends Controller
{
    public function index()
    {
        $hostings = UserHosting::with(['user', 'service', 'order'])
            ->latest()
            ->paginate(20);

        return view('admin.hostings.index', compact('hostings'));
    }

    public function show(UserHosting $hosting)
    {
        $hosting->load(['user', 'service', 'order']);
        return view('admin.hostings.show', compact('hosting'));
    }

    public function edit(UserHosting $hosting)
    {
        $hosting->load(['user', 'service', 'order']);
        return view('admin.hostings.edit', compact('hosting'));
    }

    public function update(Request $request, UserHosting $hosting)
    {
        $validated = $request->validate([
            'status'                  => 'required|in:pending,active,suspended,cancelled,expired',
            'plan_name'               => 'nullable|string|max:255',
            'hosting_type'            => 'nullable|in:shared,vps,dedicated,cloud,reseller',
            'primary_domain'          => 'nullable|string|max:255',
            'server_ip'               => 'nullable|string|max:50',
            'ipv6'                    => 'nullable|string|max:100',
            'gateway'                 => 'nullable|string|max:50',
            'server_hostname'         => 'nullable|string|max:255',
            'datacenter_location'     => 'nullable|string|max:255',
            'os_name'                 => 'nullable|string|max:100',
            'boot_mode'               => 'nullable|string|max:50',
            'cpu_cores'               => 'nullable|integer|min:1',
            'ram_size'                => 'nullable|string|max:50',
            'storage_size'            => 'nullable|string|max:50',
            'bandwidth'               => 'nullable|string|max:50',
            'control_panel_url'       => 'nullable|url|max:255',
            'control_panel_username'  => 'nullable|string|max:255',
            'root_password'           => 'nullable|string|max:255',
            'automated_backup'        => 'nullable|boolean',
            'backup_status'           => 'nullable|string|max:100',
            'expiry_date'             => 'nullable|date',
            'has_streaming_addon'     => 'nullable|boolean',
            'admin_notes'             => 'nullable|string',
        ]);

        $validated['automated_backup'] = $request->boolean('automated_backup');
        $validated['has_streaming_addon'] = $request->boolean('has_streaming_addon');

        $hosting->update($validated);

        return redirect()->route('admin.hostings.show', $hosting)
            ->with('success', 'Hosting updated successfully. Client can now see their plan details.');
    }

    public function destroy(UserHosting $hosting)
    {
        $hosting->delete();
        return redirect()->route('admin.hostings.index')
            ->with('success', 'Hosting deleted.');
    }

    /**
     * Recreate Proxmox VM for this hosting using correct plan specs.
     */
    public function recreateVm(UserHosting $hosting)
    {
        // 1. Look up the VPS plan by plan_name (case-insensitive)
        $planName = trim($hosting->plan_name ?? '');
        $plan = null;
        if (!empty($planName)) {
            $plan = VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$planName])
                ->orWhereRaw('LOWER(slug) = LOWER(?)', [$planName])
                ->first();
        }

        if (!$plan) {
            return redirect()->route('admin.hostings.edit', $hosting)
                ->with('error', 'Could not find VpsPlan for "' . $planName . '". Please save a valid plan name first.');
        }

        // 2. Update hosting specs from the plan
        $hosting->update([
            'cpu_cores'    => $plan->cpu_cores,
            'ram_size'     => $plan->memory_gb . ' GB',
            'storage_size' => $plan->disk_gb . ' GB ' . $plan->disk_type,
            'os_name'      => 'Ubuntu 22.04',
        ]);

        // 3. Find Proxmox node
        $node = ProxmoxNode::where('status', 'active')
            ->where('max_vms', '>', 0)
            ->whereRaw('current_vms < max_vms')
            ->orderByRaw('current_vms ASC')
            ->first();

        if (!$node) {
            $node = ProxmoxNode::where('status', 'active')->first();
        }

        if (!$node) {
            return redirect()->route('admin.hostings.edit', $hosting)
                ->with('error', 'No active Proxmox node available.');
        }

        $apiToken = $node->getDecryptedApiToken();
        if (!$apiToken) {
            return redirect()->route('admin.hostings.edit', $hosting)
                ->with('error', 'No API token for node: ' . $node->name);
        }

        // 4. Prepare VM config
        $vmName = 'vps-' . $hosting->user_id . '-' . $hosting->id;
        $rootPassword = $hosting->root_password ?: bin2hex(random_bytes(8));

        $vmConfig = [
            'name'      => $vmName,
            'cpu'       => $plan->cpu_cores,
            'memory'    => ($plan->memory_gb ?? 1) * 1024,
            'disk'      => $plan->disk_gb ?? 20,
            'iso'       => 'ubuntu-22.04-live-server-amd64.iso',
            'storage'   => 'local',
            'ciuser'    => 'root',
            'cipassword'=> $rootPassword,
            'ipconfig0' => 'ip=dhcp',
        ];

        try {
            $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
            $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);

            Log::info('Admin: Recreating VM for hosting', [
                'hosting_id' => $hosting->id,
                'node'       => $node->name,
                'config'     => $vmConfig,
            ]);

            $result = $proxmox->createAndStartVm($vmConfig);

            if (!$result) {
                throw new \Exception('Failed to create VM via Proxmox API');
            }

            $vmid = $result['vmid'];

            // Wait briefly and try to get IP
            $ipAddress = null;
            try {
                sleep(5);
                $vmStatus = $proxmox->getVmStatus($vmid);
                if (!empty($vmStatus['ip'])) {
                    $ipAddress = $vmStatus['ip'];
                }
            } catch (\Exception $ipEx) {
                Log::warning('Admin: Could not get VM IP after recreation', [
                    'vmid' => $vmid,
                    'error' => $ipEx->getMessage(),
                ]);
            }

            // 5. Create / update ProxmoxVm record
            $hostname = $vmName . '.believoo.com';
            ProxmoxVm::updateOrCreate(
                ['vmid' => $vmid],
                [
                    'user_id'            => $hosting->user_id,
                    'name'               => $vmName,
                    'hostname'           => $hostname,
                    'node'               => $node->name,
                    'cpu_cores'          => $plan->cpu_cores,
                    'memory_mb'          => $vmConfig['memory'],
                    'disk_gb'            => $plan->disk_gb,
                    'storage'            => 'local',
                    'iso'                => $vmConfig['iso'],
                    'ip_address'         => $ipAddress,
                    'plan_name'          => $hosting->plan_name,
                    'status'             => 'running',
                    'created_at_proxmox' => now(),
                    'started_at'         => now(),
                ]
            );

            // 6. Update hosting record
            $hosting->update([
                'status'             => 'active',
                'server_ip'          => $ipAddress,
                'server_hostname'    => $hostname,
                'root_password'      => $rootPassword,
                'datacenter_location'=> $node->display_name,
                'vps_id'             => $vmid,
                'admin_notes'        => ($hosting->admin_notes ? $hosting->admin_notes . "\n" : '')
                                        . 'VM recreated by admin. VMID: ' . $vmid . ' on node: ' . $node->name,
            ]);

            // 7. Update node VM count
            $node->increment('current_vms');

            return redirect()->route('admin.hostings.edit', $hosting)
                ->with('success', 'VM recreated successfully! VMID: ' . $vmid
                    . ' | Specs: ' . $plan->cpu_cores . ' CPU, ' . $plan->memory_gb . ' GB RAM, ' . $plan->disk_gb . ' GB Disk'
                    . ($ipAddress ? ' | IP: ' . $ipAddress : ''));

        } catch (\Exception $e) {
            Log::error('Admin: VM recreation failed', [
                'hosting_id' => $hosting->id,
                'error'      => $e->getMessage(),
            ]);

            return redirect()->route('admin.hostings.edit', $hosting)
                ->with('error', 'VM recreation failed: ' . $e->getMessage());
        }
    }
}
