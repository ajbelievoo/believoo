<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProxmoxVm;
use App\Models\User;
use App\Services\ProxmoxApiService;
use App\Services\ApplicationInstallerService;
use App\Http\Controllers\Client\DnsRecordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProxmoxVmController extends Controller
{
    protected ProxmoxApiService $proxmox;
    protected ApplicationInstallerService $appInstaller;

    public function __construct(ProxmoxApiService $proxmox, ApplicationInstallerService $appInstaller)
    {
        $this->proxmox = $proxmox;
        $this->appInstaller = $appInstaller;
    }

    /**
     * Display VM management page
     */
    public function index()
    {
        $proxmoxVms = $this->proxmox->listVms();
        
        // Get VM details from database
        $dbVms = ProxmoxVm::with('user')->get()->keyBy('vmid');
        
        // Merge database info with Proxmox VMs
        $proxmoxVmIds = collect($proxmoxVms)->pluck('vmid')->toArray();
        foreach ($proxmoxVms as &$vm) {
            $vmid = $vm['vmid'];
            if ($dbVms->has($vmid)) {
                $dbVm = $dbVms->get($vmid);
                $vm['ip_address'] = $dbVm->ip_address;
                $vm['mac_address'] = $dbVm->mac_address;
                $vm['user'] = $dbVm->user;
                $vm['plan_name'] = $dbVm->plan_name;
                $vm['control_panel'] = $dbVm->control_panel;
                $vm['bandwidth'] = $dbVm->bandwidth;
                $vm['panel_status'] = $dbVm->panel_status;
                $vm['hostname'] = $dbVm->hostname;
            }
        }

        // Also include DB-only VMs (exist in DB but not returned by Proxmox API)
        // These may be VMs on other nodes or stale records
        $dbOnlyVms = $dbVms->filter(fn($dbVm) => !in_array($dbVm->vmid, $proxmoxVmIds));
        foreach ($dbOnlyVms as $dbVm) {
            $proxmoxVms[] = [
                'vmid'    => $dbVm->vmid,
                'name'    => $dbVm->name,
                'status'  => $dbVm->status ?? 'unknown',
                'maxcpu'  => $dbVm->cpu_cores,
                'maxmem'  => ($dbVm->memory_mb ?? 0) * 1024 * 1024,
                'ip_address'   => $dbVm->ip_address,
                'mac_address'  => $dbVm->mac_address,
                'user'         => $dbVm->user,
                'plan_name'    => $dbVm->plan_name,
                'control_panel'=> $dbVm->control_panel,
                'bandwidth'    => $dbVm->bandwidth,
                'panel_status' => $dbVm->panel_status,
                'hostname'     => $dbVm->hostname,
                'db_only'      => true, // Flag to show warning in view
            ];
        }

        $vms = $proxmoxVms;
        
        return view('admin.proxmox.vms.index', compact('vms'));
    }

    /**
     * Show create VM form
     */
    public function create()
    {
        $nextVmid = $this->proxmox->getNextVmId();
        
        // Auto-generate Virtual MAC address
        $autoMac = '02:00:00:' . sprintf('%02x', mt_rand(0, 255)) . ':' . sprintf('%02x', mt_rand(0, 255)) . ':' . sprintf('%02x', mt_rand(0, 255));
        
        // Fetch available ISOs dynamically from Proxmox
        $isos = [];
        try {
            $storageContent = $this->proxmox->listStorageContent('local', 'iso');
            foreach ($storageContent as $item) {
                $volid = $item['volid'] ?? '';
                // Extract just the filename from "local:iso/filename.iso"
                $filename = basename(str_replace('local:iso/', '', $volid));
                if ($filename) {
                    $isos[$filename] = $filename;
                }
            }
        } catch (\Exception $e) {
            // Fallback to static list if API fails
            $isos = [
                'ubuntu-22.04-live-server-amd64.iso' => 'Ubuntu 22.04 LTS',
                'ubuntu-20.04-live-server-amd64.iso' => 'Ubuntu 20.04 LTS',
                'debian-12-genericcloud-amd64.iso' => 'Debian 12',
            ];
        }
        
        // Get VPS plans from database
        $vpsPlans = \App\Models\VpsPlan::where('is_active', true)
            ->where('is_sold_out', false)
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();
        
        return view('admin.proxmox.vms.create', compact('nextVmid', 'isos', 'vpsPlans', 'autoMac'));
    }

    /**
     * Get current Proxmox node info for AJAX
     */
    public function currentNode()
    {
        try {
            $node = \App\Models\ProxmoxNode::where('status', 'active')
                ->where('is_default', true)
                ->first();
            
            if (!$node) {
                $node = \App\Models\ProxmoxNode::where('status', 'active')->first();
            }
            
            if (!$node) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active Proxmox node found'
                ], 404);
            }
            
            // Test connection
            $connectionStatus = 'unknown';
            try {
                $apiToken = $node->getDecryptedApiToken();
                if ($apiToken) {
                    $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
                    $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);
                    $connectionTest = $proxmox->testConnection();
                    $connectionStatus = $connectionTest['success'] ? 'connected' : 'disconnected';
                } else {
                    $connectionStatus = 'no_token';
                }
            } catch (\Exception $connEx) {
                $connectionStatus = 'error';
            }
            
            return response()->json([
                'success' => true,
                'node' => [
                    'id' => $node->id,
                    'name' => $node->name,
                    'display_name' => $node->display_name,
                    'hostname' => $node->hostname,
                    'port' => $node->port,
                    'location' => $node->location,
                    'current_vms' => $node->current_vms,
                    'max_vms' => $node->max_vms,
                    'connection' => $connectionStatus,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store (create) new VM
     */
    public function store(Request $request)
    {
        Log::info('ProxmoxVmController::store - Request received', [
            'all_data' => $request->all(),
            'has_name' => $request->has('name'),
            'name_value' => $request->input('name'),
        ]);
        
        $validator = \Validator::make($request->all(), [
            'name' => 'required|string|max:100|regex:/^[a-zA-Z0-9][a-zA-Z0-9\s\-]*[a-zA-Z0-9]$/',
            'hostname' => 'nullable|string|max:255|regex:/^[a-zA-Z0-9][a-zA-Z0-9\-\.]{0,61}[a-zA-Z0-9\.]$/',
            'cpu' => 'required|integer|min:1|max:64',
            'memory' => 'required|integer|min:512|max:524288',
            'disk' => 'required|integer|min:5|max:2048',
            'iso' => 'required|string',
            'storage' => 'nullable|string',
            'iso_storage' => 'nullable|string',
            'vmid' => 'nullable|integer',
            'start_after_create' => 'boolean',
            'ipconfig0' => 'nullable|string',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
            'user_id' => 'nullable|exists:users,id',
            'control_panel' => 'nullable|string|in:cpanel,plesk,fastpanel,aapanel,webmin,cyberpanel,hestiacp,cloudpanel,docker',
            'node' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            Log::error('ProxmoxVmController::store - Validation failed', ['errors' => $validator->errors()->toArray()]);
            return back()->with('error', 'Validation failed: ' . json_encode($validator->errors()->toArray()));
        }
        
        $validated = $validator->validated();
        Log::info('ProxmoxVmController::store - Validation passed', ['validated' => $validated]);

        try {
            // Get node from form or use default
            $nodeName = $validated['node'] ?? config('proxmox.node', 'ns548195');
            
            // If node provided, create service instance for that node
            if (!empty($validated['node'])) {
                $node = \App\Models\ProxmoxNode::where('name', $validated['node'])
                    ->orWhere('name', 'ns548195')
                    ->first();
                    
                if ($node && $node->getDecryptedApiToken()) {
                    $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
                    $this->proxmox = ProxmoxApiService::forNode(
                        $proxmoxUrl,
                        $node->getDecryptedApiToken(),
                        $node->name
                    );
                    Log::info('Proxmox: Using node from form', ['node' => $node->name]);
                }
            }
            
            // Test connection before creating
            $connectionTest = $this->proxmox->testConnection();
            if (!$connectionTest['success']) {
                return back()->with('error', 'Cannot connect to Proxmox: ' . $connectionTest['message']);
            }
            
            // Sanitize VM name for Proxmox (DNS-compliant: no spaces, lowercase, alphanumeric + hyphens)
            $slugifiedName = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $validated['name']));
            $slugifiedName = preg_replace('/-+/', '-', $slugifiedName); // Remove consecutive hyphens
            $slugifiedName = trim($slugifiedName, '-'); // Remove leading/trailing hyphens
            
            $config = [
                'name' => $slugifiedName,
                'cpu' => $validated['cpu'],
                'memory' => $validated['memory'],
                'disk' => $validated['disk'],
                'iso' => $validated['iso'],
                'storage' => $validated['storage'] ?? 'local',
                'iso_storage' => $validated['iso_storage'] ?? 'local',
                // vmid will be auto-generated by Proxmox API
            ];

            // Add MAC address if provided
            if (!empty($validated['mac_address'])) {
                $config['mac_address'] = $validated['mac_address'];
            }

            if (!empty($validated['ipconfig0'])) {
                $config['ipconfig0'] = $validated['ipconfig0'];
            }

            // Generate cloud-init script if control panel is selected
            $cloudInitScript = null;
            $panelPassword = null;
            $panelPort = null;
            $panelUsername = null;
            
            if (!empty($validated['control_panel'])) {
                $appDetails = $this->appInstaller->getAppDetails($validated['control_panel']);
                
                if ($appDetails) {
                    // Generate secure password for panel
                    $panelPassword = $this->appInstaller->generateSecurePassword(16);
                    $panelPort = $appDetails['port'];
                    $panelUsername = $appDetails['username'];
                    
                    // Generate cloud-init script
                    $hostname = $validated['hostname'] ?? 'vps-' . time();
                    $cloudInitScript = $this->appInstaller->generateCloudInitScript(
                        $validated['control_panel'],
                        [
                            'hostname' => $hostname,
                            'password' => $panelPassword,
                            'email' => User::find($validated['user_id'])?->email ?? 'admin@' . $hostname,
                        ]
                    );
                    
                    // Add cloud-init to VM config
                    $config['cloud_init_userdata'] = $cloudInitScript;
                    
                    Log::info('Proxmox: Generated cloud-init script', [
                        'vm_name' => $slugifiedName,
                        'app' => $validated['control_panel'],
                        'hostname' => $hostname,
                    ]);
                }
            }

            // Create VM in Proxmox
            $result = $this->proxmox->createVm($config);

            if (!$result) {
                return back()->with('error', 'Failed to create VM. Check logs for details.');
            }

            $vmid = $result['vmid'];
            $status = 'stopped';

            // Start VM if requested (enabled by default)
            if ($request->has('start_after_create')) {
                sleep(2); // Brief pause for creation to process
                $startResult = $this->proxmox->startVm($vmid);
                if ($startResult) {
                    $status = 'running';
                }
            }

            // Determine plan name based on specs
            $planName = $this->getPlanName($validated['cpu'], $validated['memory'], $validated['disk']);
            $bandwidth = $this->getBandwidth($validated['cpu']);
            
            // Build panel URL if IP and panel are set
            $panelLoginUrl = null;
            if (!empty($validated['ip_address']) && !empty($panelPort)) {
                $panelLoginUrl = "https://{$validated['ip_address']}:{$panelPort}";
            }
            
            // Determine panel status
            $panelStatus = 'pending';
            if (!empty($validated['control_panel'])) {
                $panelStatus = 'installing'; // Will be installing when VM starts
            }

            // Check if VM with this vmid already exists in database (stale entry)
            $existingVm = ProxmoxVm::where('vmid', $vmid)->first();
            if ($existingVm) {
                Log::warning('Deleting stale VM entry from database', ['vmid' => $vmid]);
                $existingVm->delete();
            }

            // Save VM details to database
            $vm = ProxmoxVm::create([
                'user_id' => $validated['user_id'] ?? null,
                'vmid' => $vmid,
                'name' => $slugifiedName, // Use slugified name
                'hostname' => $validated['hostname'] ?? null,
                'node' => config('proxmox.node', 'ns548195'),
                'cpu_cores' => $validated['cpu'],
                'memory_mb' => $validated['memory'],
                'disk_gb' => $validated['disk'],
                'storage' => $validated['storage'] ?? 'local',
                'iso' => $validated['iso'],
                'iso_storage' => $validated['iso_storage'] ?? 'local',
                'bridge' => config('proxmox.bridge', 'vmbr0'),
                'ip_address' => $validated['ip_address'] ?? null,
                'mac_address' => $validated['mac_address'] ?? null,
                'nameservers' => ['ns1.believoo.com', 'ns2.believoo.com'],
                'control_panel' => $validated['control_panel'] ?? null,
                'bandwidth' => $bandwidth,
                'plan_name' => $planName,
                'panel_status' => $panelStatus,
                'panel_login_url' => $panelLoginUrl,
                'panel_username' => $panelUsername,
                'panel_password' => $panelPassword,
                'panel_port' => $panelPort,
                'cloud_init_script' => $cloudInitScript,
                'cloud_init_status' => $cloudInitScript ? 'pending' : null,
                'status' => $status,
                'created_at_proxmox' => now(),
                'started_at' => $status === 'running' ? now() : null,
                'notes' => $request->input('notes'),
                'config_snapshot' => [
                    'cpu' => $validated['cpu'],
                    'memory' => $validated['memory'],
                    'disk' => $validated['disk'],
                    'iso' => $validated['iso'],
                    'ip_address' => $validated['ip_address'] ?? null,
                    'mac_address' => $validated['mac_address'] ?? null,
                    'control_panel' => $validated['control_panel'] ?? null,
                    'hostname' => $validated['hostname'] ?? null,
                    'cloud_init' => $cloudInitScript ? true : false,
                ],
            ]);
            
            // Apply cloud-init configuration after VM creation
            if ($cloudInitScript && $result) {
                sleep(3); // Wait for VM to be fully created
                
                $cloudInitConfig = [
                    'user_data' => $cloudInitScript,
                    'ciuser' => 'root',
                    'cipassword' => $panelPassword,
                ];
                
                if (!empty($validated['ipconfig0'])) {
                    $cloudInitConfig['ipconfig0'] = $validated['ipconfig0'];
                }
                
                $cloudInitSet = $this->proxmox->setCloudInitConfig($vmid, $cloudInitConfig);
                
                if ($cloudInitSet) {
                    $vm->update(['cloud_init_status' => 'configured']);
                    Log::info('Proxmox: Cloud-init config applied successfully', ['vmid' => $vmid]);
                } else {
                    Log::warning('Proxmox: Failed to apply cloud-init config', ['vmid' => $vmid]);
                }
            }

            Log::info('Admin created Proxmox VM', [
                'vmid' => $vmid,
                'name' => $validated['name'],
                'ip' => $validated['ip_address'] ?? null,
                'mac' => $validated['mac_address'] ?? null,
                'admin_id' => auth()->id(),
            ]);

            // Auto-create UserHosting record so VM appears in client dashboard
            if (!empty($validated['user_id'])) {
                try {
                    \App\Models\UserHosting::create([
                        'user_id' => $validated['user_id'],
                        'hosting_type' => 'vps',
                        'plan_name' => $planName,
                        'status' => $status === 'running' ? 'active' : 'pending',
                        'price' => 0,
                        'billing_cycle' => 'monthly',
                        'start_date' => now(),
                        'expiry_date' => now()->addMonth(),
                        'server_ip' => $validated['ip_address'] ?? null,
                        'server_hostname' => $validated['hostname'] ?? $slugifiedName,
                        'cpu_cores' => $validated['cpu'],
                        'ram_size' => round($validated['memory'] / 1024) . ' GB',
                        'storage_size' => $validated['disk'] . ' GB',
                        'os_name' => str_replace('.iso', '', $validated['iso']),
                        'bandwidth' => $bandwidth,
                        'vps_id' => $vmid, // Proxmox VM ID for power actions
                        'admin_notes' => 'Proxmox VM ID: ' . $vmid,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create UserHosting for VM', ['vmid' => $vmid, 'error' => $e->getMessage()]);
                }
            }

            // Auto-create DNS A Record if IP is assigned and hostname is a domain
            if (!empty($validated['ip_address']) && !empty($validated['hostname'])) {
                try {
                    $hostname = $validated['hostname'];
                    $ip = $validated['ip_address'];
                    $userId = $validated['user_id'] ?? auth()->id();

                    // Check if hostname looks like a domain (contains dots)
                    if (str_contains($hostname, '.')) {
                        // Try to find user's domain
                        $domain = \App\Models\UserDomain::where('user_id', $userId)
                            ->where('status', 'active')
                            ->where(function($q) use ($hostname) {
                                $q->where('domain_name', $hostname)
                                  ->orWhereRaw("? LIKE CONCAT('%.', domain_name)", [$hostname]);
                            })
                            ->first();

                        if ($domain) {
                            // Extract subdomain from hostname
                            $domainName = $domain->domain_name;
                            $subdomain = '@';
                            
                            if ($hostname !== $domainName) {
                                // Remove domain part to get subdomain
                                $subdomain = str_replace('.' . $domainName, '', $hostname);
                            }

                            // Create A record
                            $dnsRecord = \App\Http\Controllers\Client\DnsRecordController::autoCreateVpsARecord(
                                $userId,
                                $domain->id,
                                $ip,
                                $subdomain
                            );

                            if ($dnsRecord) {
                                Log::info('Auto-created DNS A record for VPS', [
                                    'vmid' => $vmid,
                                    'domain' => $domainName,
                                    'subdomain' => $subdomain,
                                    'ip' => $ip,
                                    'dns_record_id' => $dnsRecord->id,
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to auto-create DNS A record for VPS', [
                        'vmid' => $vmid,
                        'hostname' => $validated['hostname'] ?? null,
                        'ip' => $validated['ip_address'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return redirect()->route('admin.proxmox.vms.show', $vmid)
                ->with('success', "VM created successfully with ID: {$vmid}");

        } catch (\Exception $e) {
            Log::error('Admin VM creation failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return back()->with('error', 'Error creating VM: ' . $e->getMessage());
        }
    }

    /**
     * Create VM via API (for programmatic use)
     */
    public function apiCreate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'cpu' => 'required|integer|min:1|max:64',
            'memory' => 'required|integer|min:512|max:262144',
            'disk' => 'required|integer|min:5|max:2048',
            'iso' => 'required|string',
            'start' => 'boolean',
            'user_id' => 'nullable|exists:users,id',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/',
        ]);

        try {
            $config = [
                'name' => $validated['name'],
                'cpu' => $validated['cpu'],
                'memory' => $validated['memory'],
                'disk' => $validated['disk'],
                'iso' => $validated['iso'],
            ];

            if (!empty($validated['mac_address'])) {
                $config['mac_address'] = $validated['mac_address'];
            }

            if (!empty($validated['start'])) {
                $result = $this->proxmox->createAndStartVm($config);
                $status = 'running';
            } else {
                $result = $this->proxmox->createVm($config);
                $status = 'stopped';
            }

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create VM',
                ], 500);
            }

            $vmid = $result['vmid'];

            // Save to database
            ProxmoxVm::create([
                'user_id' => $validated['user_id'] ?? null,
                'vmid' => $vmid,
                'name' => $validated['name'],
                'node' => config('proxmox.node', 'ns548195'),
                'cpu_cores' => $validated['cpu'],
                'memory_mb' => $validated['memory'],
                'disk_gb' => $validated['disk'],
                'iso' => $validated['iso'],
                'ip_address' => $validated['ip_address'] ?? null,
                'mac_address' => $validated['mac_address'] ?? null,
                'status' => $status,
                'created_at_proxmox' => now(),
                'started_at' => $status === 'running' ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'vmid' => $result['vmid'],
                    'upid' => $result['upid'] ?? null,
                    'started' => $result['started'] ?? false,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show VM details
     */
    public function show(int $vmid)
    {
        $status = $this->proxmox->getVmStatus($vmid);
        $config = $this->proxmox->getVmConfig($vmid);

        // Get VM details from database
        $vm = ProxmoxVm::where('vmid', $vmid)->first();

        if (!$status) {
            // VM not found on Proxmox - could be a stale DB record or API issue
            if ($vm) {
                // Show DB record with a warning that VM is not found on Proxmox
                return view('admin.proxmox.vms.show', compact('vmid', 'status', 'config', 'vm'))
                    ->with('warning', 'VM #' . $vmid . ' is in database but not found on Proxmox node. It may have been deleted directly from Proxmox.');
            }
            return redirect()->route('admin.proxmox.vms.index')
                ->with('error', 'VM #' . $vmid . ' not found on Proxmox node.');
        }

        return view('admin.proxmox.vms.show', compact('vmid', 'status', 'config', 'vm'));
    }

    /**
     * Return the panel password on demand instead of embedding it in the page.
     */
    public function panelPassword(int $vmid)
    {
        try {
            $vm = ProxmoxVm::where('vmid', $vmid)->first();

            if (!$vm) {
                return response()->json(['error' => 'VM not found'], 404);
            }

            $password = $vm->panel_password;

            if (!$password) {
                return response()->json(['error' => 'No panel password stored'], 404);
            }

            return response()->json([
                'success' => true,
                'panel_password' => $password,
            ]);
        } catch (\Exception $e) {
            Log::error('Panel password fetch failed', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to retrieve password'], 500);
        }
    }

    /**
     * Start VM
     */
    public function start(int $vmid)
    {
        $success = $this->proxmox->startVm($vmid);

        if ($success) {
            return back()->with('success', 'VM started successfully');
        }

        return back()->with('error', 'Failed to start VM');
    }

    /**
     * Stop VM
     */
    public function stop(int $vmid)
    {
        $success = $this->proxmox->stopVm($vmid);

        if ($success) {
            return back()->with('success', 'VM stopped successfully');
        }

        return back()->with('error', 'Failed to stop VM');
    }

    /**
     * Shutdown VM
     */
    public function shutdown(int $vmid)
    {
        $success = $this->proxmox->shutdownVm($vmid);

        if ($success) {
            return back()->with('success', 'VM shutdown initiated');
        }

        return back()->with('error', 'Failed to shutdown VM');
    }

    /**
     * Reboot VM
     */
    public function reboot(int $vmid)
    {
        $success = $this->proxmox->rebootVm($vmid);

        if ($success) {
            return back()->with('success', 'VM reboot initiated');
        }

        return back()->with('error', 'Failed to reboot VM');
    }

    /**
     * Delete VM
     */
    public function destroy(int $vmid)
    {
        // Look up VM in database to get the correct node and details
        $dbVm = ProxmoxVm::where('vmid', $vmid)->first();

        // Resolve the correct ProxmoxApiService for this VM's node
        $proxmox = $this->proxmox;
        if ($dbVm && $dbVm->node && $dbVm->node !== $this->proxmox->getNode()) {
            $node = \App\Models\ProxmoxNode::where('name', $dbVm->node)->first();
            if ($node && $node->getDecryptedApiToken()) {
                $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
                $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $node->getDecryptedApiToken(), $node->name);
                Log::info('Proxmox: Using node from DB for delete', ['node' => $node->name, 'vmid' => $vmid]);
            }
        }

        // Check if VM exists on Proxmox
        $status = $proxmox->getVmStatus($vmid);

        if ($status) {
            // VM exists on Proxmox — stop it first if running
            $vmStatus = $status['qmpstatus'] ?? $status['status'] ?? '';
            if ($vmStatus === 'running') {
                Log::info('Stopping VM before deletion', ['vmid' => $vmid]);
                $proxmox->stopVm($vmid);
                sleep(3); // Brief pause for stop to process
            }

            // Now delete from Proxmox
            $success = $proxmox->deleteVm($vmid, true);

            if (!$success) {
                return back()->with('error', 'Failed to delete VM from Proxmox. Check logs for details.');
            }
        } else {
            // VM not found on Proxmox — log and proceed with DB cleanup only
            Log::warning('VM not found on Proxmox during delete. Cleaning up database only.', ['vmid' => $vmid]);
        }

        // Clean up database records
        if ($dbVm) {
            try {
                // Release assigned IP
                if ($dbVm->ip_address) {
                    $ipRecord = \App\Models\IpAddress::where('ip_address', $dbVm->ip_address)->first();
                    if ($ipRecord) {
                        $ipRecord->update([
                            'status' => 'available',
                            'proxmox_vm_id' => null,
                            'user_hosting_id' => null,
                            'vmid' => null,
                        ]);
                    }
                }

                // Delete related UserHosting
                \App\Models\UserHosting::where('vps_id', $vmid)->delete();

                // Delete the VM record (cascades to snapshots, migrations)
                $dbVm->delete();
                Log::info('VM deleted from database', ['vmid' => $vmid]);
            } catch (\Exception $e) {
                Log::error('Failed to clean up database after VM deletion', [
                    'vmid' => $vmid,
                    'error' => $e->getMessage(),
                ]);
                return back()->with('error', 'VM deleted from Proxmox but database cleanup failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.proxmox.vms.index')
            ->with('success', 'VM deleted successfully');
    }

    /**
     * Get VM status via API
     */
    public function apiStatus(int $vmid)
    {
        $status = $this->proxmox->getVmStatus($vmid);

        if (!$status) {
            return response()->json([
                'success' => false,
                'message' => 'VM not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Quick create VM with predefined templates
     */
    public function quickCreate(Request $request)
    {
        $templates = [
            'basic' => ['cpu' => 1, 'memory' => 1024, 'disk' => 20],
            'standard' => ['cpu' => 2, 'memory' => 2048, 'disk' => 40],
            'premium' => ['cpu' => 4, 'memory' => 4096, 'disk' => 80],
            'enterprise' => ['cpu' => 8, 'memory' => 8192, 'disk' => 160],
        ];

        $validated = $request->validate([
            'template' => 'required|in:' . implode(',', array_keys($templates)),
            'name' => 'required|string|max:100',
            'iso' => 'required|string',
        ]);

        $template = $templates[$validated['template']];
        $config = [
            'name' => $validated['name'],
            'cpu' => $template['cpu'],
            'memory' => $template['memory'],
            'disk' => $template['disk'],
            'iso' => $validated['iso'],
        ];

        $result = $this->proxmox->createAndStartVm($config);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create VM',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'VM created successfully',
            'data' => [
                'vmid' => $result['vmid'],
                'template' => $validated['template'],
                'config' => $template,
            ],
        ]);
    }

    /**
     * Determine plan name based on specs
     */
    private function getPlanName(int $cpu, int $memory, int $disk): string
    {
        $memoryGb = $memory / 1024;
        
        // VPS Plans matching OVHCloud specs
        if ($cpu == 1 && $memoryGb == 1 && $disk == 20) {
            return 'VPS-1';
        } elseif ($cpu == 2 && $memoryGb == 2 && $disk == 40) {
            return 'VPS-2';
        } elseif ($cpu == 4 && $memoryGb == 4 && $disk == 80) {
            return 'VPS-3';
        } elseif ($cpu == 8 && $memoryGb == 24 && $disk == 200) {
            return 'VPS-4';
        } elseif ($cpu == 12 && $memoryGb == 48 && $disk == 300) {
            return 'VPS-5';
        } elseif ($cpu == 16 && $memoryGb == 64 && $disk == 350) {
            return 'VPS-6';
        } elseif ($cpu == 24 && $memoryGb == 96 && $disk == 400) {
            return 'VPS-7';
        } elseif ($cpu == 2 && $memoryGb == 4 && $disk == 80) {
            return 'Premium';
        } elseif ($cpu == 8 && $memoryGb == 8 && $disk == 160) {
            return 'Enterprise';
        }
        
        return 'Custom';
    }

    /**
     * Determine bandwidth based on CPU cores
     */
    private function getBandwidth(int $cpu): string
    {
        $bandwidthMap = [
            1 => '500 Mbps',
            2 => '1 Gbps',
            4 => '1 Gbps',
            8 => '1.5 Gbps',
            12 => '2 Gbps',
            16 => '2.5 Gbps',
            24 => '3 Gbps',
            32 => '4 Gbps',
        ];
        
        return $bandwidthMap[$cpu] ?? '1 Gbps';
    }

    /**
     * Manually assign IP to a VM (admin action)
     */
    public function assignIp(Request $request, int $vmid)
    {
        // Return JSON validation errors for AJAX requests
        $validator = \Validator::make($request->all(), [
            'ip_address' => 'required|ip',
            'gateway'    => 'nullable|ip',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => $validator->errors()->first(),
            ], 422);
        }

        $ip      = $request->input('ip_address');
        $gateway = $request->input('gateway', '139.99.122.1');
        $applyCloudInit = $request->has('apply_cloud_init');

        try {
            $vm = \App\Models\ProxmoxVm::where('vmid', $vmid)->first();
            if ($vm) {
                $vm->update(['ip_address' => $ip]);
            }

            $hosting = \App\Models\UserHosting::where('vps_id', $vmid)->first();
            if ($hosting) {
                $hosting->update(['server_ip' => $ip]);
            }

            $ipRecord = \App\Models\IpAddress::where('ip_address', $ip)->first();
            if ($ipRecord) {
                $ipRecord->assignToVm($vmid, $vm?->id, $hosting?->id);
            }

            if ($applyCloudInit) {
                $this->proxmox->updateVmConfig($vmid, [
                    'ipconfig0' => "ip={$ip}/24,gw={$gateway}",
                ]);
            }

            Log::info('Admin assigned IP to VM', ['vmid' => $vmid, 'ip' => $ip]);

            return response()->json([
                'success' => true,
                'message' => "IP {$ip} assigned to VM #{$vmid}." . ($applyCloudInit ? ' Cloud-init updated.' : ''),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign IP', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}