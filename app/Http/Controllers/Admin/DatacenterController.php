<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProxmoxNode;
use App\Services\EncryptionService;
use App\Services\ProxmoxApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DatacenterController extends Controller
{
    protected EncryptionService $encryption;
    
    public function __construct(EncryptionService $encryption)
    {
        $this->encryption = $encryption;
    }
    
    /**
     * List all datacenter nodes
     */
    public function index()
    {
        $nodes = ProxmoxNode::orderBy('country_code')
            ->orderBy('city')
            ->paginate(20);
            
        // Stats for dashboard
        $stats = [
            'total_nodes' => ProxmoxNode::count(),
            'active_nodes' => ProxmoxNode::where('status', 'active')->count(),
            'coming_soon' => ProxmoxNode::where('status', 'coming_soon')->count(),
            'maintenance' => ProxmoxNode::where('status', 'maintenance')->count(),
            'total_vms' => ProxmoxNode::sum('current_vms'),
            'available_slots' => ProxmoxNode::where('status', 'active')
                ->selectRaw('SUM(max_vms - current_vms) as available')
                ->first()
                ->available ?? 0,
        ];
        
        return view('admin.datacenter.index', compact('nodes', 'stats'));
    }
    
    /**
     * Show form to create new node
     */
    public function create()
    {
        $regions = [
            'Asia' => ['IN', 'SG', 'JP', 'KR', 'TH', 'ID', 'MY', 'PH', 'VN', 'HK', 'TW'],
            'Europe' => ['DE', 'FR', 'UK', 'NL', 'IT', 'ES', 'PL', 'SE', 'FI', 'CH'],
            'North America' => ['US', 'CA', 'MX'],
            'South America' => ['BR', 'AR', 'CL', 'CO', 'PE'],
            'Oceania' => ['AU', 'NZ'],
            'Africa' => ['ZA', 'NG', 'KE', 'EG', 'MA'],
        ];
        
        $countryFlags = [
            'IN' => '🇮🇳', 'SG' => '🇸🇬', 'US' => '🇺🇸', 'DE' => '🇩🇪',
            'UK' => '🇬🇧', 'FR' => '🇫🇷', 'CA' => '🇨🇦', 'AU' => '🇦🇺',
            'JP' => '🇯🇵', 'BR' => '🇧🇷', 'NL' => '🇳🇱', 'SG' => '🇸🇬',
        ];
        
        return view('admin.datacenter.create', compact('regions', 'countryFlags'));
    }
    
    /**
     * Store new node
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:proxmox_nodes',
            'display_name' => 'required|string',
            'hostname' => 'required|string',
            'port' => 'required|integer|min:1|max:65535',
            'country_code' => 'required|string|size:2',
            'city' => 'required|string',
            'region' => 'nullable|string',
            'status' => 'required|in:active,maintenance,offline,coming_soon',
            'is_default' => 'boolean',
            'max_vms' => 'required|integer|min:1',
            'total_cpu_cores' => 'required|integer|min:1',
            'total_memory_gb' => 'required|integer|min:1',
            'total_disk_gb' => 'required|integer|min:1',
            'api_token' => 'nullable|string',
            'root_password' => 'nullable|string',
            'network_gateway' => 'nullable|ip',
            'network_subnet' => 'nullable|string',
            'provider_name' => 'nullable|string',
            'latency_hint' => 'nullable|string',
        ]);
        
        try {
            // Encrypt sensitive data
            $data = [
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'hostname' => $validated['hostname'],
                'port' => $validated['port'],
                'country_code' => strtoupper($validated['country_code']),
                'city' => $validated['city'],
                'region' => $validated['region'],
                'status' => $validated['status'],
                'is_default' => $validated['is_default'] ?? false,
                'max_vms' => $validated['max_vms'],
                'total_cpu_cores' => $validated['total_cpu_cores'],
                'total_memory_bytes' => $validated['total_memory_gb'] * 1024 * 1024 * 1024,
                'total_disk_bytes' => $validated['total_disk_gb'] * 1024 * 1024 * 1024,
                'network_gateway' => $validated['network_gateway'],
                'network_subnet' => $validated['network_subnet'],
                'provider_name' => $validated['provider_name'],
                'latency_hint' => $validated['latency_hint'],
                'flag_emoji' => $this->getFlagEmoji($validated['country_code']),
            ];
            
            // Encrypt API credentials if provided
            if (!empty($validated['api_token'])) {
                $data['api_token_encrypted'] = $this->encryption->encrypt($validated['api_token']);
            }
            if (!empty($validated['root_password'])) {
                $data['root_password_encrypted'] = $this->encryption->encrypt($validated['root_password']);
            }
            
            // If this is set as default, remove default from others
            if ($data['is_default']) {
                ProxmoxNode::where('is_default', true)->update(['is_default' => false]);
            }
            
            $node = ProxmoxNode::create($data);
            
            Log::info('New Proxmox node created', [
                'node_id' => $node->id,
                'name' => $node->name,
                'country' => $node->country_code,
                'status' => $node->status,
            ]);
            
            return redirect()
                ->route('admin.datacenter.index')
                ->with('success', "Node '{$node->display_name}' created successfully!");
                
        } catch (\Exception $e) {
            Log::error('Failed to create node', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to create node: ' . $e->getMessage())->withInput();
        }
    }
    
    /**
     * Show node details with real-time Proxmox API stats
     */
    public function show(ProxmoxNode $node)
    {
        $node->load('vms');

        // Get real-time stats from Proxmox API
        $apiStats = $this->getNodeStatsFromApi($node);

        // Calculate usage percentages from API data
        $usage = [
            'cpu_percent' => $apiStats['cpu_percent'] ?? 0,
            'ram_percent' => $apiStats['ram_percent'] ?? 0,
            'disk_percent' => $apiStats['disk_percent'] ?? 0,
            'online' => $apiStats['online'] ?? false,
            'uptime' => $apiStats['uptime'] ?? 0,
            'active_vms' => $apiStats['active_vms'] ?? 0,
        ];

        return view('admin.datacenter.show', compact('node', 'apiStats', 'usage'));
    }
    
    /**
     * Show edit form
     */
    public function edit(ProxmoxNode $node)
    {
        $regions = [
            'Asia' => ['IN', 'SG', 'JP', 'KR', 'TH'],
            'Europe' => ['DE', 'FR', 'UK', 'NL'],
            'North America' => ['US', 'CA'],
            'Oceania' => ['AU', 'NZ'],
        ];
        
        return view('admin.datacenter.edit', compact('node', 'regions'));
    }
    
    /**
     * Update node
     */
    public function update(Request $request, ProxmoxNode $node)
    {
        $validated = $request->validate([
            'display_name' => 'required|string',
            'hostname' => 'required|string',
            'port' => 'required|integer',
            'status' => 'required|in:active,maintenance,offline,coming_soon',
            'is_default' => 'boolean',
            'max_vms' => 'required|integer|min:1',
            'total_cpu_cores' => 'required|integer',
            'total_memory_gb' => 'required|integer',
            'total_disk_gb' => 'required|integer',
            'api_token' => 'nullable|string',
            'root_password' => 'nullable|string',
        ]);
        
        try {
            $data = [
                'display_name' => $validated['display_name'],
                'hostname' => $validated['hostname'],
                'port' => $validated['port'],
                'status' => $validated['status'],
                'is_default' => $validated['is_default'] ?? false,
                'max_vms' => $validated['max_vms'],
                'total_cpu_cores' => $validated['total_cpu_cores'],
                'total_memory_bytes' => $validated['total_memory_gb'] * 1024 * 1024 * 1024,
                'total_disk_bytes' => $validated['total_disk_gb'] * 1024 * 1024 * 1024,
            ];
            
            // Update encrypted fields if provided
            if (!empty($validated['api_token'])) {
                $data['api_token_encrypted'] = $this->encryption->encrypt($validated['api_token']);
            }
            if (!empty($validated['root_password'])) {
                $data['root_password_encrypted'] = $this->encryption->encrypt($validated['root_password']);
            }
            
            // Handle default status change
            if ($data['is_default'] && !$node->is_default) {
                ProxmoxNode::where('is_default', true)->update(['is_default' => false]);
            }
            
            $node->update($data);
            
            return redirect()
                ->route('admin.datacenter.index')
                ->with('success', "Node '{$node->display_name}' updated successfully!");
                
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update node: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete node
     */
    public function destroy(ProxmoxNode $node)
    {
        try {
            $name = $node->display_name;
            $node->delete();
            
            return redirect()
                ->route('admin.datacenter.index')
                ->with('success', "Node '{$name}' deleted successfully!");
                
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete node: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync node status with Proxmox API - fetch real hardware stats
     */
    public function sync(ProxmoxNode $node)
    {
        try {
            $apiToken = $node->getDecryptedApiToken();

            if (!$apiToken) {
                return back()->with('error', 'No API token configured for this node');
            }

            // Create Proxmox API service with node-specific credentials
            $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
            $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);

            // Fetch real-time stats from Proxmox API
            $apiStats = $proxmox->getNodeResourceUsage();

            if (!$apiStats['available']) {
                return back()->with('error', 'Failed to connect to Proxmox API. Check credentials and node status.');
            }

            // Update node with real hardware stats from API
            $updateData = [
                'last_synced_at' => now(),
                'status' => 'active',
                'used_cpu_percent' => $apiStats['cpu_percent'],
                'used_memory_percent' => $apiStats['ram_percent'],
                'used_disk_percent' => $apiStats['disk_percent'],
            ];

            // Update hardware specs if API returned them
            if (!empty($apiStats['cpu_cores'])) {
                $updateData['total_cpu_cores'] = $apiStats['cpu_cores'];
            }
            if (!empty($apiStats['ram_total'])) {
                $updateData['total_memory_bytes'] = $apiStats['ram_total'];
            }
            if (!empty($apiStats['disk_total'])) {
                $updateData['total_disk_bytes'] = $apiStats['disk_total'];
            }

            $node->update($updateData);

            // Log successful sync
            Log::info('Datacenter node synced with Proxmox API', [
                'node_id' => $node->id,
                'node_name' => $node->name,
                'cpu_cores' => $apiStats['cpu_cores'] ?? null,
                'ram_gb' => isset($apiStats['ram_total']) ? round($apiStats['ram_total'] / 1024 / 1024 / 1024, 2) : null,
                'disk_gb' => isset($apiStats['disk_total']) ? round($apiStats['disk_total'] / 1024 / 1024 / 1024 / 1024, 2) : null,
                'cpu_usage' => $apiStats['cpu_percent'] . '%',
                'ram_usage' => $apiStats['ram_percent'] . '%',
            ]);

            return back()->with('success', sprintf(
                'Node synced successfully! Real stats: %d cores, %s RAM, CPU: %.1f%%, RAM: %.1f%%',
                $apiStats['cpu_cores'] ?? $node->total_cpu_cores,
                isset($apiStats['ram_total']) ? round($apiStats['ram_total'] / 1024 / 1024 / 1024, 1) . ' GB' : 'N/A',
                $apiStats['cpu_percent'],
                $apiStats['ram_percent']
            ));

        } catch (\Exception $e) {
            Log::error('Node sync failed', [
                'node_id' => $node->id,
                'node_name' => $node->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk update status (Coming Soon → Active)
     */
    public function bulkActivate(Request $request)
    {
        $ids = $request->input('node_ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'No nodes selected');
        }
        
        $count = ProxmoxNode::whereIn('id', $ids)
            ->where('status', 'coming_soon')
            ->update(['status' => 'active']);
        
        return back()->with('success', "{$count} nodes activated successfully!");
    }
    
    /**
     * Get flag emoji for country
     */
    private function getFlagEmoji(string $countryCode): string
    {
        $flags = [
            'IN' => '🇮🇳', 'SG' => '🇸🇬', 'US' => '🇺🇸', 'DE' => '🇩🇪',
            'UK' => '🇬🇧', 'FR' => '🇫🇷', 'CA' => '🇨🇦', 'AU' => '🇦🇺',
            'JP' => '🇯🇵', 'BR' => '🇧🇷', 'NL' => '🇳🇱', 'SG' => '🇸🇬',
        ];
        
        return $flags[strtoupper($countryCode)] ?? '🌍';
    }
    
    /**
     * Get node stats from Proxmox API in real-time
     */
    private function getNodeStatsFromApi(ProxmoxNode $node): array
    {
        try {
            $apiToken = $node->getDecryptedApiToken();

            if (!$apiToken) {
                Log::warning('No API token for node', ['node_id' => $node->id]);
                return $this->getFallbackStats($node);
            }

            // Create Proxmox API service with node-specific credentials
            $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
            $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);

            // Fetch real-time resource usage
            $apiStats = $proxmox->getNodeResourceUsage();

            if (!$apiStats['available']) {
                Log::warning('Proxmox API not available for node', ['node_id' => $node->id]);
                return $this->getFallbackStats($node);
            }

            return [
                'online' => true,
                'cpu_percent' => $apiStats['cpu_percent'],
                'ram_percent' => $apiStats['ram_percent'],
                'disk_percent' => $apiStats['disk_percent'],
                'cpu_cores' => $apiStats['cpu_cores'] ?? $node->total_cpu_cores,
                'ram_used' => $apiStats['ram_used'] ?? 0,
                'ram_total' => $apiStats['ram_total'] ?? $node->total_memory_bytes,
                'disk_used' => $apiStats['disk_used'] ?? 0,
                'disk_total' => $apiStats['disk_total'] ?? $node->total_disk_bytes,
                'uptime' => $apiStats['uptime'] ?? 0,
                'loadavg' => $apiStats['loadavg'] ?? [],
                'active_vms' => $node->vms()->where('status', 'running')->count(),
                'synced_at' => $node->last_synced_at?->diffForHumans() ?? 'Never',
            ];

        } catch (\Exception $e) {
            Log::error('Failed to fetch node stats from API', [
                'node_id' => $node->id,
                'error' => $e->getMessage(),
            ]);

            return $this->getFallbackStats($node);
        }
    }

    /**
     * Get fallback stats from database when API is unavailable
     */
    private function getFallbackStats(ProxmoxNode $node): array
    {
        return [
            'online' => false,
            'cpu_percent' => $node->used_cpu_percent,
            'ram_percent' => $node->used_memory_percent,
            'disk_percent' => $node->used_disk_percent,
            'cpu_cores' => $node->total_cpu_cores,
            'ram_used' => 0,
            'ram_total' => $node->total_memory_bytes,
            'disk_used' => 0,
            'disk_total' => $node->total_disk_bytes,
            'uptime' => 0,
            'loadavg' => [],
            'active_vms' => $node->vms()->where('status', 'running')->count(),
            'synced_at' => $node->last_synced_at?->diffForHumans() ?? 'Never',
            'fallback' => true,
        ];
    }
}
