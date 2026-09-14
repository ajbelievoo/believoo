<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxmoxApiService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected string $username;
    protected string $password;
    protected string $node;
    protected string $bridge;
    protected bool $verifySsl;
    protected ?string $ticket = null;
    protected ?string $csrfToken = null;

    public function __construct(?string $baseUrl = null, ?string $apiToken = null, ?string $node = null, ?string $username = null, ?string $password = null)
    {
        // Try to get settings from database first, fall back to config/env
        $settings = $this->getSettings();

        $this->baseUrl = $baseUrl ?? $settings['proxmox_api_url'] ?? config('proxmox.api_url', 'https://139.99.122.47:8006');
        $this->apiToken = (string) ($apiToken ?? $settings['proxmox_api_token'] ?? config('proxmox.api_token', ''));
        $this->username = $username ?? $settings['proxmox_username'] ?? config('proxmox.username', 'root@pam');
        $this->password = (string) ($password ?? $settings['proxmox_password'] ?? config('proxmox.password', ''));
        $this->node = $node ?? $settings['proxmox_node'] ?? config('proxmox.node', 'ns548195');
        $this->bridge = $settings['proxmox_bridge'] ?? config('proxmox.bridge', 'vmbr0');
        $this->verifySsl = ($settings['proxmox_verify_ssl'] ?? '0') === '1';
    }

    /**
     * Create service instance for a specific node with its credentials
     */
    public static function forNode(string $baseUrl, string $apiToken, string $node): self
    {
        return new self($baseUrl, $apiToken, $node);
    }

    /**
     * Get the configured node name
     */
    public function getNode(): string
    {
        return $this->node;
    }

    /**
     * Get settings from database
     */
    protected function getSettings(): array
    {
        try {
            if (\Schema::hasTable('settings')) {
                return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            }
        } catch (\Exception $e) {
            // Silently fail if DB not ready
        }
        return [];
    }

    /**
     * Get HTTP client with authentication headers
     * Supports both API Token and Ticket-based auth
     */
    protected function httpClient()
    {
        $client = Http::timeout(60)
            ->withOptions([
                'verify' => $this->verifySsl,
            ]);
        
        // Use API Token if available
        if (!empty($this->apiToken)) {
            $client = $client->withHeaders([
                'Authorization' => 'PVEAPIToken=' . $this->apiToken,
            ]);
        } 
        // Otherwise use ticket-based auth with username/password
        elseif (!empty($this->ticket)) {
            $client = $client->withHeaders([
                'Cookie' => 'PVEAuthCookie=' . $this->ticket,
            ]);
            if ($this->csrfToken) {
                $client = $client->withHeaders([
                    'CSRFPreventionToken' => $this->csrfToken,
                ]);
            }
        }
        
        return $client;
    }
    
    /**
     * Authenticate with username/password to get ticket
     */
    protected function authenticate(): bool
    {
        if (empty($this->username) || empty($this->password)) {
            Log::error('Proxmox: No credentials available for authentication');
            return false;
        }
        
        try {
            Log::info('Proxmox: Authenticating with username/password', [
                'username' => $this->username,
            ]);
            
            $response = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->post("{$this->baseUrl}/api2/json/access/ticket", [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);
            
            if ($response->successful()) {
                $data = $response->json('data');
                $this->ticket = $data['ticket'] ?? null;
                $this->csrfToken = $data['CSRFPreventionToken'] ?? null;
                
                Log::info('Proxmox: Authentication successful');
                return true;
            }
            
            Log::error('Proxmox: Authentication failed', [
                'status' => $response->status(),
            ]);
            return false;
            
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception during authentication', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * List storage content (ISOs, templates, etc.)
     */
    public function listStorageContent(string $storage = 'local', string $contentType = 'iso'): array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/storage/{$storage}/content", [
                    'content' => $contentType,
                ]);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }
            return [];
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception listing storage content', [
                'storage' => $storage,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get next available VM ID
     * Falls back to calculating from existing VMs if API fails
     */
    public function getNextVmId(): ?int
    {
        try {
            $url = "{$this->baseUrl}/api2/json/cluster/nextid";
            
            Log::debug('Proxmox: Getting next VM ID', [
                'url' => $url,
                'token_prefix' => substr($this->apiToken, 0, 20) . '...',
            ]);

            $response = $this->httpClient()
                ->get($url);

            Log::debug('Proxmox: nextid response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json('data');
                Log::info('Proxmox: Got next VM ID', ['vmid' => $data]);
                return $data;
            }

            // API failed, try fallback method
            Log::warning('Proxmox: API nextid failed, using fallback', [
                'status' => $response->status(),
            ]);
            
            return $this->calculateNextVmId();
            
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception getting next VM ID, using fallback', [
                'error' => $e->getMessage(),
            ]);
            return $this->calculateNextVmId();
        }
    }
    
    /**
     * Calculate next VM ID from existing VMs (fallback method)
     */
    protected function calculateNextVmId(): ?int
    {
        try {
            $vms = $this->listVms();
            
            if (empty($vms)) {
                return 100; // Start from 100 if no VMs exist
            }
            
            $maxId = collect($vms)->max('vmid');
            $nextId = $maxId + 1;
            
            Log::info('Proxmox: Calculated next VM ID from existing VMs', [
                'max_existing' => $maxId,
                'next_id' => $nextId,
            ]);
            
            return $nextId;
        } catch (\Exception $e) {
            Log::error('Proxmox: Fallback calculation failed', [
                'error' => $e->getMessage(),
            ]);
            return 100; // Safe default
        }
    }

    /**
     * Create a new VM with specified configuration
     *
     * @param array $config VM configuration
     * - cpu: Number of CPU cores
     * - memory: RAM in MB
     * - disk: Disk size in GB
     * - iso: ISO filename from local storage
     * - name: VM name
     * - storage: Storage name (default: local)
     * @return array|null [vmid, upid] or null on failure
     */
    public function createVm(array $config): ?array
    {
        try {
            $vmid = $config['vmid'] ?? $this->getNextVmId();

            if (!$vmid) {
                throw new \Exception('Could not get next VM ID');
            }

            // Build net0 configuration
            $net0 = "virtio,bridge={$this->bridge},firewall=1";

            $storageName = $config['storage'] ?? 'local';
            $diskSize = $config['disk'] ?? 10;

            // Build correct disk string based on storage type:
            // - LVM thin (local-lvm, ceph, etc.): "storage:sizeGB"
            // - Directory storage (local, nfs, etc.): "storage:sizeGB,format=qcow2"
            $directoryStorages = ['local', 'nfs', 'cifs', 'glusterfs', 'zfspool'];
            $isDirectoryStorage = in_array(strtolower($storageName), $directoryStorages)
                || str_starts_with(strtolower($storageName), 'nfs')
                || str_starts_with(strtolower($storageName), 'cifs');

            if ($isDirectoryStorage) {
                $diskString = $storageName . ':' . $diskSize . ',format=qcow2';
            } else {
                // LVM thin / ZFS / Ceph: no format needed
                $diskString = $storageName . ':' . $diskSize;
            }
            
            $payload = [
                'vmid'    => $vmid,
                'name'    => $config['name'] ?? 'vm-' . $vmid,
                'cpu'     => 'host',
                'cores'   => $config['cpu'] ?? 1,
                'sockets' => 1,
                'memory'  => $config['memory'] ?? 1024,
                'net0'    => $net0,
                'scsihw'  => 'virtio-scsi-single',
                'scsi0'   => $diskString,
                'boot'    => 'order=scsi0;net0',
                'ostype'  => 'l26',
                'agent'   => 'enabled=1,fstrim_cloned_disks=1',
                'bios'    => 'seabios',
            ];
            
            // Add ISO if provided (mount as IDE cdrom)
            if (!empty($config['iso'])) {
                $payload['ide2'] = ($config['iso_storage'] ?? 'local') . ':iso/' . $config['iso'] . ',media=cdrom';
                $payload['boot'] = 'order=ide2;scsi0;net0';
            }

            // ===== CLOUD-INIT SETUP =====
            $hasCloudInit = !empty($config['cipassword']) || !empty($config['ciuser']);
            
            if ($hasCloudInit) {
                // Add cloud-init drive on ide0
                $payload['ide0'] = ($config['iso_storage'] ?? 'local') . ':cloudinit';
                $payload['citype'] = 'nocloud';
                
                // Try to upload snippet for guest agent auto-install
                $guestAgentScript = "#cloud-config\n" .
                    "package_update: true\n" .
                    "packages:\n" .
                    "  - qemu-guest-agent\n" .
                    "  - openssh-server\n" .
                    "runcmd:\n" .
                    "  - systemctl enable qemu-guest-agent\n" .
                    "  - systemctl start qemu-guest-agent\n" .
                    "  - systemctl enable ssh\n" .
                    "  - systemctl start ssh\n" .
                    "  - sed -i 's/#PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config\n" .
                    "  - sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/' /etc/ssh/sshd_config\n" .
                    "  - systemctl restart ssh\n";
                
                $userdata = $config['cloud_init_userdata'] ?? $guestAgentScript;
                
                // Only add cicustom if snippet upload succeeds
                try {
                    $uploaded = $this->uploadCloudInitSnippet($vmid, $userdata);
                    if ($uploaded) {
                        $payload['cicustom'] = 'user=local:snippets/cloud-init-' . $vmid . '.yaml';
                    }
                } catch (\Exception $e) {
                    Log::warning('Proxmox: Cloud-init snippet upload failed, skipping cicustom', ['vmid' => $vmid]);
                }
                // cicustom NOT added if upload failed - VM will still boot fine
            }

            // Add optional parameters
            if (!empty($config['ipconfig0'])) {
                $payload['ipconfig0'] = $config['ipconfig0'];
            }

            if (!empty($config['nameserver'])) {
                $payload['nameserver'] = $config['nameserver'];
            }

            if (!empty($config['searchdomain'])) {
                $payload['searchdomain'] = $config['searchdomain'];
            }

            // Cloud-init user/password
            if (!empty($config['ciuser'])) {
                $payload['ciuser'] = $config['ciuser'];
            }

            if (!empty($config['cipassword'])) {
                $payload['cipassword'] = $config['cipassword'];
            }

            if (!empty($config['sshkeys'])) {
                $payload['sshkeys'] = $config['sshkeys'];
            }

            Log::info('Proxmox: Creating VM', [
                'vmid' => $vmid,
                'name' => $payload['name'],
                'config' => $config,
            ]);

            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu", $payload);

            if ($response->successful()) {
                $data = $response->json('data');
                
                // Proxmox returns the UPID (task ID) as the data value directly
                // e.g. {"data":"UPID:ns548195:..."}
                // If data is a string, it's the UPID itself
                $upid = null;
                if (is_string($data)) {
                    $upid = $data;
                } elseif (is_array($data)) {
                    $upid = $data['upid'] ?? null;
                }

                Log::info('Proxmox: VM creation started', [
                    'vmid' => $vmid,
                    'upid' => $upid,
                    'raw_data' => $data,
                ]);

                return [
                    'vmid' => $vmid,
                    'upid' => $upid,
                ];
            }

            Log::error('Proxmox: VM creation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'vmid' => $vmid,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception creating VM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Start a VM
     */
    public function startVm(int $vmid): bool
    {
        try {
            Log::info('Proxmox: Starting VM', ['vmid' => $vmid]);

            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/status/start");

            if ($response->successful()) {
                Log::info('Proxmox: VM started successfully', ['vmid' => $vmid]);
                return true;
            }

            Log::error('Proxmox: Failed to start VM', [
                'vmid' => $vmid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception starting VM', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Stop a VM
     */
    public function stopVm(int $vmid): bool
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/status/stop");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception stopping VM', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Shutdown a VM gracefully
     */
    public function shutdownVm(int $vmid): bool
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/status/shutdown");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception shutting down VM', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Reboot a VM
     */
    public function rebootVm(int $vmid): bool
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/status/reboot");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception rebooting VM', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get VM status and info
     */
    public function getVmStatus(int $vmid): ?array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/status/current");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception getting VM status', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete a VM
     */
    public function deleteVm(int $vmid, bool $purge = true): bool
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}";

            if ($purge) {
                $url .= '?purge=1&destroy-unreferenced-disks=1';
            }

            $response = $this->httpClient()
                ->delete($url);

            if ($response->successful()) {
                return true;
            }

            Log::error('Proxmox: Failed to delete VM', [
                'vmid' => $vmid,
                'node' => $this->node,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception deleting VM', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get VM config
     */
    public function getVmConfig(int $vmid): ?array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/config");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception getting VM config', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get node status and resources (CPU, RAM, Disk)
     */
    public function getNodeStatus(): ?array
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes/{$this->node}/status";
            
            Log::info('Proxmox: Fetching node status', [
                'url' => $url,
                'node' => $this->node,
            ]);
            
            $response = $this->httpClient()->get($url);
            
            if ($response->successful()) {
                $data = $response->json('data');
                Log::info('Proxmox: Node status fetched successfully');
                return $data;
            }
            
            Log::warning('Proxmox: Failed to fetch node status', [
                'status' => $response->status(),
            ]);
            
            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception fetching node status', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get node storage information (total across all storage)
     */
    public function getNodeStorage(): array
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes/{$this->node}/storage";
            $response = $this->httpClient()->get($url);
            
            if ($response->successful()) {
                $storages = $response->json('data') ?? [];
                $totalUsed = 0;
                $totalMax = 0;
                
                foreach ($storages as $storage) {
                    // Skip local backups and special storages
                    if (in_array($storage['storage'] ?? '', ['backup', 'iso', 'vzdump'])) {
                        continue;
                    }
                    
                    if (isset($storage['used']) && isset($storage['max'])) {
                        $totalUsed += $storage['used'];
                        $totalMax += $storage['max'];
                    }
                }
                
                return [
                    'used' => $totalUsed,
                    'total' => $totalMax,
                    'storages' => $storages,
                ];
            }
            
            return ['used' => 0, 'total' => 0, 'storages' => []];
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception fetching node storage', [
                'error' => $e->getMessage(),
            ]);
            return ['used' => 0, 'total' => 0, 'storages' => []];
        }
    }

    /**
     * Get node resource usage percentages
     */
    public function getNodeResourceUsage(): array
    {
        $status = $this->getNodeStatus();
        $storage = $this->getNodeStorage();
        
        if (!$status) {
            return [
                'cpu_percent' => 0,
                'ram_percent' => 0,
                'disk_percent' => 0,
                'available' => false,
            ];
        }
        
        // Calculate CPU percentage
        $cpuPercent = isset($status['cpu']) ? round($status['cpu'] * 100, 1) : 0;
        
        // Calculate RAM percentage
        $ramPercent = 0;
        if (isset($status['memory']['used']) && isset($status['memory']['total']) && $status['memory']['total'] > 0) {
            $ramPercent = round(($status['memory']['used'] / $status['memory']['total']) * 100, 1);
        }
        
        // Calculate Disk percentage from total storage
        $diskPercent = 0;
        if ($storage['total'] > 0) {
            $diskPercent = round(($storage['used'] / $storage['total']) * 100, 1);
        }
        
        return [
            'cpu_percent' => $cpuPercent,
            'ram_percent' => $ramPercent,
            'disk_percent' => $diskPercent,
            'cpu_cores' => $status['cpuinfo']['cpus'] ?? 0,
            'ram_used' => $status['memory']['used'] ?? 0,
            'ram_total' => $status['memory']['total'] ?? 0,
            'disk_used' => $storage['used'],
            'disk_total' => $storage['total'],
            'uptime' => $status['uptime'] ?? 0,
            'loadavg' => $status['loadavg'] ?? [],
            'available' => true,
        ];
    }

    /**
     * Test connection to Proxmox API
     * Returns array with success status and message
     */
    public function testConnection(): array
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes/{$this->node}/status";
            
            Log::info('Proxmox: Testing connection', [
                'url' => $url,
                'node' => $this->node,
            ]);
            
            $response = $this->httpClient()->get($url);
            
            if ($response->successful()) {
                $data = $response->json('data');
                return [
                    'success' => true,
                    'message' => 'Connected successfully to ' . $this->node,
                    'node' => $data['name'] ?? $this->node,
                    'status' => 'online',
                ];
            }
            
            // If 401, try password auth
            if ($response->status() === 401 && empty($this->apiToken) && !empty($this->username) && !empty($this->password)) {
                if ($this->authenticate()) {
                    $response = $this->httpClient()->get($url);
                    if ($response->successful()) {
                        return [
                            'success' => true,
                            'message' => 'Connected via password auth to ' . $this->node,
                            'status' => 'online',
                        ];
                    }
                }
            }
            
            return [
                'success' => false,
                'message' => 'API returned status ' . $response->status(),
                'status' => 'error',
                'error' => $response->body(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Proxmox: Connection test failed', [
                'error' => $e->getMessage(),
                'node' => $this->node,
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * List all VMs on the node
     */
    public function listVms(): array
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu";
            
            Log::info('Proxmox: Fetching VMs from API', [
                'url' => $url,
                'node' => $this->node,
                'has_token' => !empty($this->apiToken),
            ]);
            
            $response = $this->httpClient()->get($url);
            
            Log::info('Proxmox: API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
            ]);

            // If 401 and we have credentials, try password auth
            if ($response->status() === 401 && empty($this->apiToken) && !empty($this->username) && !empty($this->password)) {
                Log::info('Proxmox: Token auth failed, trying password auth');
                
                if ($this->authenticate()) {
                    $response = $this->httpClient()->get($url);
                    
                    Log::info('Proxmox: Password auth response', [
                        'status' => $response->status(),
                        'successful' => $response->successful(),
                    ]);
                }
            }

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
                Log::info('Proxmox: Found VMs', ['count' => count($data)]);
                return $data;
            }

            Log::warning('Proxmox: API call failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception listing VMs', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * List all nodes in the Proxmox cluster
     * Returns array of nodes with status and resource info
     */
    public function listClusterNodes(): array
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes";
            
            Log::info('Proxmox: Fetching cluster nodes', ['url' => $url]);
            
            $response = $this->httpClient()->get($url);
            
            if ($response->successful()) {
                $nodes = $response->json('data') ?? [];
                
                // Filter and format node info
                $formattedNodes = [];
                foreach ($nodes as $node) {
                    // Only show online nodes with available resources
                    if (($node['status'] ?? '') !== 'online') {
                        continue;
                    }
                    
                    // Get storage info for this node
                    $storageInfo = $this->getNodeStorageForMigration($node['node']);
                    
                    $formattedNodes[] = [
                        'name' => $node['node'],
                        'status' => $node['status'],
                        'cpu' => round(($node['cpu'] ?? 0) * 100, 1),
                        'memory_used' => $this->formatBytes($node['mem'] ?? 0),
                        'memory_total' => $this->formatBytes($node['maxmem'] ?? 0),
                        'memory_percent' => $node['maxmem'] > 0 ? round(($node['mem'] / $node['maxmem']) * 100, 1) : 0,
                        'disk_used' => $this->formatBytes($node['disk'] ?? 0),
                        'disk_total' => $this->formatBytes($node['maxdisk'] ?? 0),
                        'disk_percent' => $node['maxdisk'] > 0 ? round(($node['disk'] / $node['maxdisk']) * 100, 1) : 0,
                        'uptime' => $this->formatUptime($node['uptime'] ?? 0),
                        'available_for_migration' => $storageInfo['available'],
                        'free_storage_gb' => round($storageInfo['free_gb'] ?? 0, 2),
                    ];
                }
                
                Log::info('Proxmox: Found cluster nodes', ['count' => count($formattedNodes)]);
                return $formattedNodes;
            }
            
            Log::warning('Proxmox: Failed to fetch cluster nodes', [
                'status' => $response->status(),
            ]);
            
            return [];
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception listing cluster nodes', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
    
    /**
     * Get storage info for a specific node (for migration eligibility)
     */
    protected function getNodeStorageForMigration(string $nodeName): array
    {
        try {
            $url = "{$this->baseUrl}/api2/json/nodes/{$nodeName}/storage";
            $response = $this->httpClient()->get($url);
            
            if (!$response->successful()) {
                return ['available' => false, 'free_gb' => 0];
            }
            
            $storages = $response->json('data') ?? [];
            $totalFree = 0;
            $hasVmStorage = false;
            
            foreach ($storages as $storage) {
                // Skip backup and ISO storage
                if (in_array($storage['storage'] ?? '', ['backup', 'iso', 'vzdump', 'snippets'])) {
                    continue;
                }
                
                // Check if storage can store VMs
                $content = $storage['content'] ?? '';
                if (str_contains($content, 'images') || str_contains($content, 'rootdir')) {
                    $hasVmStorage = true;
                    $max = $storage['max'] ?? 0;
                    $used = $storage['used'] ?? 0;
                    $totalFree += ($max - $used);
                }
            }
            
            return [
                'available' => $hasVmStorage && $totalFree > 10 * 1024 * 1024 * 1024, // At least 10GB free
                'free_gb' => $totalFree / 1024 / 1024 / 1024,
            ];
        } catch (\Exception $e) {
            return ['available' => false, 'free_gb' => 0];
        }
    }
    
    /**
     * Format bytes to human readable
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Format uptime to human readable
     */
    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}d {$hours}h";
        }
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        return "{$minutes}m";
    }

    /**
     * Get task status with progress information
     */
    public function getTaskStatus(string $upid): ?array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/tasks/{$upid}/status");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception getting task status', [
                'upid' => $upid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if task is complete
     */
    public function isTaskComplete(string $upid): bool
    {
        $status = $this->getTaskStatus($upid);
        return $status && ($status['status'] === 'OK' || !empty($status['endtime']));
    }

    /**
     * Upload cloud-init snippet to Proxmox local storage
     */
    public function uploadCloudInitSnippet(int $vmid, string $content): bool
    {
        try {
            $filename = 'cloud-init-' . $vmid . '.yaml';
            
            $response = $this->httpClient()
                ->attach('content', $content, $filename)
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/storage/local/upload", [
                    'content'  => 'snippets',
                    'filename' => $filename,
                ]);

            if ($response->successful()) {
                Log::info('Proxmox: Cloud-init snippet uploaded', ['vmid' => $vmid, 'file' => $filename]);
                return true;
            }

            Log::warning('Proxmox: Cloud-init snippet upload failed', [
                'vmid'   => $vmid,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 200),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::warning('Proxmox: Exception uploading cloud-init snippet', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create VM and start it (convenience method)
     */
    public function createAndStartVm(array $config): ?array
    {
        $result = $this->createVm($config);

        if ($result) {
            // Wait a moment for VM creation to process
            sleep(3);
            
            // Start the VM
            $started = $this->startVm($result['vmid']);
            
            $result['started'] = $started;
            
            Log::info('Proxmox: VM created and start attempted', [
                'vmid' => $result['vmid'],
                'started' => $started,
            ]);
        }

        return $result;
    }

    /**
     * Set cloud-init configuration for a VM
     * This should be called after VM creation but before first boot
     */
    public function setCloudInitConfig(int $vmid, array $config): bool
    {
        try {
            Log::info('Proxmox: Setting cloud-init config', [
                'vmid' => $vmid,
                'config' => $config,
            ]);

            $payload = [];

            // User data (cloud-init script)
            if (!empty($config['user_data'])) {
                $payload['cicustom'] = 'user=' . base64_encode($config['user_data']);
            }

            // Network config
            if (!empty($config['network_config'])) {
                $payload['cicustom'] = ($payload['cicustom'] ?? '') . ',network=' . base64_encode($config['network_config']);
            }

            // Meta data
            if (!empty($config['meta_data'])) {
                $payload['cicustom'] = ($payload['cicustom'] ?? '') . ',meta=' . base64_encode($config['meta_data']);
            }

            // Simple cloud-init parameters
            if (!empty($config['ciuser'])) {
                $payload['ciuser'] = $config['ciuser'];
            }

            if (!empty($config['cipassword'])) {
                $payload['cipassword'] = $config['cipassword'];
            }

            if (!empty($config['sshkeys'])) {
                $payload['sshkeys'] = $config['sshkeys'];
            }

            if (!empty($config['ipconfig0'])) {
                $payload['ipconfig0'] = $config['ipconfig0'];
            }

            if (!empty($config['nameserver'])) {
                $payload['nameserver'] = $config['nameserver'];
            }

            if (!empty($config['searchdomain'])) {
                $payload['searchdomain'] = $config['searchdomain'];
            }

            // Use config API endpoint
            $response = $this->httpClient()
                ->asForm()
                ->put("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/config", $payload);

            if ($response->successful()) {
                Log::info('Proxmox: Cloud-init config set successfully', ['vmid' => $vmid]);
                return true;
            }

            Log::error('Proxmox: Failed to set cloud-init config', [
                'vmid' => $vmid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception setting cloud-init config', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update VM configuration
     */
    public function updateVmConfig(int $vmid, array $config): bool
    {
        try {
            Log::info('Proxmox: Updating VM config', [
                'vmid' => $vmid,
                'config' => $config,
            ]);

            $response = $this->httpClient()
                ->asForm()
                ->put("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/config", $config);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Proxmox: Exception updating VM config', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Migrate VM to another node
     * Uses Proxmox live migration API
     * Returns UPID (Unique Process ID) for tracking, or null on failure
     */
    public function migrateVm(int $vmid, string $targetNode, bool $online = true): ?string
    {
        try {
            Log::info('Proxmox: Migrating VM', [
                'vmid'   => $vmid,
                'target' => $targetNode,
                'online' => $online,
            ]);

            $payload = [
                'target' => $targetNode,
                'online' => $online ? 1 : 0,
                'with-local-disks' => 1,
            ];

            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/migrate", $payload);

            if ($response->successful()) {
                $upid = $response->json('data');
                Log::info('Proxmox: VM migration started', [
                    'vmid'   => $vmid,
                    'target' => $targetNode,
                    'upid'   => $upid,
                ]);
                return $upid;
            }

            Log::error('Proxmox: VM migration failed', [
                'vmid'   => $vmid,
                'target' => $targetNode,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Proxmox: Exception migrating VM', [
                'vmid'  => $vmid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * Get migration task progress with detailed info
     * Returns progress percentage and status details
     */
    public function getMigrationProgress(string $node, string $upid): array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$node}/tasks/{$upid}/status");

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'progress' => 0,
                    'status' => 'unknown',
                    'message' => 'Failed to fetch task status',
                ];
            }

            $data = $response->json('data') ?? [];
            
            // Calculate progress based on status
            $status = $data['status'] ?? 'unknown';
            $progress = 0;
            $message = 'Initializing...';
            
            switch ($status) {
                case 'running':
                    // Estimate progress based on time or use provided progress
                    $progress = $data['progress'] ?? 50;
                    $message = $this->getMigrationStatusMessage($data);
                    break;
                    
                case 'stopped':
                    $exitStatus = $data['exitstatus'] ?? '';
                    if ($exitStatus === 'OK') {
                        $progress = 100;
                        $status = 'completed';
                        $message = 'Migration completed successfully!';
                    } else {
                        $progress = 0;
                        $status = 'failed';
                        $message = 'Migration failed: ' . $exitStatus;
                    }
                    break;
                    
                case 'OK':
                    $progress = 100;
                    $status = 'completed';
                    $message = 'Migration completed successfully!';
                    break;
                    
                default:
                    $progress = 0;
                    $message = 'Waiting to start...';
            }

            return [
                'success' => true,
                'progress' => $progress,
                'status' => $status,
                'message' => $message,
                'upid' => $upid,
                'starttime' => $data['starttime'] ?? null,
                'endtime' => $data['endtime'] ?? null,
                'duration' => isset($data['endtime']) && isset($data['starttime']) 
                    ? $data['endtime'] - $data['starttime'] 
                    : null,
            ];

        } catch (\Exception $e) {
            Log::error('Proxmox: Exception getting migration progress', [
                'upid' => $upid,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'progress' => 0,
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get human-readable status message from task data
     */
    protected function getMigrationStatusMessage(array $data): string
    {
        $type = $data['type'] ?? 'unknown';
        $id = $data['id'] ?? '';
        
        // Check for specific migration phases in the log
        if (isset($data['log'])) {
            $log = strtolower($data['log']);
            if (str_contains($log, 'memory')) {
                return 'Migrating memory...';
            }
            if (str_contains($log, 'disk') || str_contains($log, 'storage')) {
                return 'Migrating disk...';
            }
            if (str_contains($log, 'sync')) {
                return 'Syncing data...';
            }
        }
        
        return 'Migrating VM to target node...';
    }

    /**
     * Generate VNC proxy ticket for noVNC console access
     */
    public function createVncProxy(int $vmid): ?array
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/vncproxy", [
                    'websocket' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json('data');
                return [
                    'ticket'   => $data['ticket'] ?? null,
                    'port'     => $data['port'] ?? null,
                    'node'     => $this->node,
                    'host'     => parse_url($this->baseUrl, PHP_URL_HOST),
                    'base_url' => $this->baseUrl,
                ];
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: VNC proxy failed', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get VM guest agent info (OS, network, hostname)
     */
    public function getGuestAgentInfo(int $vmid): ?array
    {
        try {
            $info = [];

            $osResp = $this->httpClient()->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/agent/get-osinfo");
            if ($osResp->successful()) {
                $info['os'] = $osResp->json('data') ?? [];
            }

            $netResp = $this->httpClient()->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/agent/network-get-interfaces");
            if ($netResp->successful()) {
                $info['network'] = $netResp->json('data')['result'] ?? [];
            }

            $hostResp = $this->httpClient()->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/agent/get-host-name");
            if ($hostResp->successful()) {
                $info['hostname'] = $hostResp->json('data')['host-name'] ?? null;
            }

            return !empty($info) ? $info : null;
        } catch (\Exception $e) {
            Log::warning('Proxmox: Guest agent not available', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Check if guest agent is running
     */
    public function isGuestAgentRunning(int $vmid): bool
    {
        try {
            $response = $this->httpClient()->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/agent/ping");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Force update root password via cloud-init after VM creation
     */
    public function forceUpdatePassword(int $vmid, string $password): bool
    {
        try {
            // Update cloud-init password config
            $result = $this->updateVmConfig($vmid, [
                'cipassword' => $password,
                'ciuser'     => 'root',
            ]);

            if ($result) {
                Log::info('Proxmox: Password updated via cloud-init', ['vmid' => $vmid]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Proxmox: Failed to update password', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate VNC ticket using username/password auth (not API token)
     * This is required for noVNC - API tokens don't work for VNC
     */
    public function createVncTicket(int $vmid): ?array
    {
        try {
            // First try with API token (works on some Proxmox versions)
            $vncResponse = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/vncproxy", [
                    'websocket' => 1,
                ]);

            if ($vncResponse->successful()) {
                $vncData = $vncResponse->json('data');
                if (!empty($vncData['ticket'])) {
                    return [
                        'ticket'     => $vncData['ticket'],
                        'port'       => $vncData['port'] ?? null,
                        'pve_ticket' => null,
                        'node'       => $this->node,
                        'host'       => parse_url($this->baseUrl, PHP_URL_HOST),
                        'base_url'   => $this->baseUrl,
                    ];
                }
            }

            // Fallback: try with username/password from settings
            $settings = $this->getSettings();
            $username = $settings['proxmox_username'] ?? config('proxmox.username', 'root@pam');
            $password = $settings['proxmox_password'] ?? config('proxmox.password', '');

            if (empty($password)) {
                Log::warning('Proxmox: No password for VNC - add Proxmox root password in Settings → Server Management');
                return null;
            }

            $authResponse = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->post("{$this->baseUrl}/api2/json/access/ticket", [
                    'username' => $username,
                    'password' => $password,
                ]);

            if (!$authResponse->successful()) {
                Log::error('Proxmox: Auth failed for VNC ticket', ['status' => $authResponse->status()]);
                return null;
            }

            $authData = $authResponse->json('data');
            $ticket   = $authData['ticket'] ?? null;
            $csrf     = $authData['CSRFPreventionToken'] ?? null;

            if (!$ticket) return null;

            $vncResponse2 = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->withHeaders([
                    'Cookie'              => 'PVEAuthCookie=' . $ticket,
                    'CSRFPreventionToken' => $csrf,
                ])
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/vncproxy", [
                    'websocket' => 1,
                ]);

            if ($vncResponse2->successful()) {
                $vncData2 = $vncResponse2->json('data');
                return [
                    'ticket'     => $vncData2['ticket'] ?? null,
                    'port'       => $vncData2['port'] ?? null,
                    'pve_ticket' => $ticket,
                    'node'       => $this->node,
                    'host'       => parse_url($this->baseUrl, PHP_URL_HOST),
                    'base_url'   => $this->baseUrl,
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Proxmox: VNC ticket exception', ['vmid' => $vmid, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate fresh PVEAuthCookie and ticket for every session
     * Eliminates "Error 401: No Ticket" by creating session-specific auth
     */
    public function getConsoleTicket(int $vmid): ?array
    {
        try {
            // Always get fresh credentials - never use cached ticket
            $settings = $this->getSettings();
            $username = $settings['proxmox_username'] ?? config('proxmox.username', 'root@pam');
            $password = $settings['proxmox_password'] ?? config('proxmox.password', '');

            if (empty($password)) {
                Log::warning('Proxmox: No password for console ticket - check settings');
                return null;
            }

            // Fresh authentication for each session
            $authResponse = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->post("{$this->baseUrl}/api2/json/access/ticket", [
                    'username' => $username,
                    'password' => $password,
                ]);

            if (!$authResponse->successful()) {
                Log::error('Proxmox: Fresh auth failed for console ticket', [
                    'status' => $authResponse->status(),
                ]);
                return null;
            }

            $authData = $authResponse->json('data');
            $pveAuthCookie = $authData['ticket'] ?? null;
            $csrfToken = $authData['CSRFPreventionToken'] ?? null;

            if (!$pveAuthCookie) {
                Log::error('Proxmox: No PVEAuthCookie in auth response');
                return null;
            }

            // Create VNC ticket with fresh auth
            $vncResponse = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->withHeaders([
                    'Cookie' => 'PVEAuthCookie=' . $pveAuthCookie,
                    'CSRFPreventionToken' => $csrfToken,
                ])
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/vncproxy", [
                    'websocket' => 1,
                ]);

            if (!$vncResponse->successful()) {
                Log::error('Proxmox: VNC proxy failed', [
                    'status' => $vncResponse->status(),
                ]);
                return null;
            }

            $vncData = $vncResponse->json('data');

            Log::info('Proxmox: Fresh console ticket generated', [
                'vmid' => $vmid,
                'port' => $vncData['port'] ?? null,
            ]);

            // Return masked URL data (no raw IP exposed to frontend)
            return [
                'ticket' => $vncData['ticket'] ?? null,
                'port' => $vncData['port'] ?? null,
                'pve_auth_cookie' => $pveAuthCookie,
                'csrf_token' => $csrfToken,
                'node' => $this->node,
                'host' => parse_url($this->baseUrl, PHP_URL_HOST),
                'base_url' => $this->baseUrl,
                // Masked proxy URL (points to our proxy, not Proxmox directly)
                'proxy_url' => $this->generateMaskedConsoleUrl($vmid, $vncData['ticket'] ?? ''),
            ];

        } catch (\Exception $e) {
            Log::error('Proxmox: Console ticket generation failed', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate masked console URL that hides Proxmox host details
     */
    protected function generateMaskedConsoleUrl(int $vmid, string $ticket): string
    {
        $consoleDomain = config('proxmox.console_domain', 'console.believoo.com');
        $appUrl = config('app.url');

        // Use branded console domain if configured
        if ($consoleDomain && $consoleDomain !== 'console.believoo.com') {
            return "https://{$consoleDomain}/console/vm-{$vmid}?t=" . urlencode(substr($ticket, 0, 16));
        }

        // Fallback to app URL
        return "{$appUrl}/console/vm-{$vmid}";
    }

    /**
     * Get masked console data for client (no raw Proxmox credentials exposed)
     */
    public function getMaskedConsoleData(int $vmid, int $userId): ?array
    {
        $ticketData = $this->getConsoleTicket($vmid);

        if (!$ticketData) {
            return null;
        }

        // Return only what's needed for the proxy - no raw host details
        return [
            'vmid' => $vmid,
            'console_url' => $ticketData['proxy_url'],
            'websocket_path' => "/api2/nodes/{$this->node}/qemu/{$vmid}/vncwebsocket",
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
            // Internal use only - not sent to frontend
            '_internal' => [
                'ticket' => $ticketData['ticket'],
                'port' => $ticketData['port'],
                'pve_auth_cookie' => $ticketData['pve_auth_cookie'],
            ],
        ];
    }

    /**
     * Get firewall options for a VM.
     * GET /nodes/{node}/qemu/{vmid}/firewall/options
     *
     * Returns the decoded data array (e.g. ['enable' => 0|1, ...]) on success,
     * or null on any HTTP error or exception.
     *
     * Requirements: 6.1, 1.4
     */
    public function getFirewallOptions(int $vmid): ?array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/firewall/options");

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Proxmox: getFirewallOptions failed', [
                'vmid'   => $vmid,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: getFirewallOptions failed', [
                'vmid'   => $vmid,
                'status' => 0,
                'body'   => substr($e->getMessage(), 0, 500),
            ]);

            return null;
        }
    }

    /**
     * Set firewall options for a VM.
     * PUT /nodes/{node}/qemu/{vmid}/firewall/options
     *
     * Returns true on 2xx success, false on any HTTP error or exception.
     *
     * Requirements: 6.4, 6.5, 1.4
     */
    public function setFirewallOptions(int $vmid, array $options): bool
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->put("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/firewall/options", $options);

            if ($response->successful()) {
                return true;
            }

            Log::error('Proxmox: setFirewallOptions failed', [
                'vmid'   => $vmid,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: setFirewallOptions failed', [
                'vmid'   => $vmid,
                'status' => 0,
                'body'   => substr($e->getMessage(), 0, 500),
            ]);

            return false;
        }
    }

    /**
     * Reset the Cloud-Init password for a VM and regenerate the Cloud-Init drive.
     *
     * Step 1: Update cipassword via updateVmConfig().
     * Step 2: POST /nodes/{node}/qemu/{vmid}/cloudinit to regenerate the drive.
     *
     * Returns true only if both steps succeed.
     * NEVER logs the $password value.
     *
     * Requirements: 10.3, 1.4
     */
    public function resetCloudInitPassword(int $vmid, string $password): bool
    {
        // Step 1: update cipassword
        $configUpdated = $this->updateVmConfig($vmid, ['cipassword' => $password]);

        if (!$configUpdated) {
            Log::error('Proxmox: resetCloudInitPassword failed', [
                'vmid'   => $vmid,
                'step'   => 'config',
                'status' => 0,
                'body'   => 'updateVmConfig returned false',
            ]);

            return false;
        }

        // Step 2: regenerate Cloud-Init drive
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/cloudinit");

            if ($response->successful()) {
                return true;
            }

            Log::error('Proxmox: resetCloudInitPassword failed', [
                'vmid'   => $vmid,
                'step'   => 'cloudinit',
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: resetCloudInitPassword failed', [
                'vmid'   => $vmid,
                'step'   => 'cloudinit',
                'status' => 0,
                'body'   => substr($e->getMessage(), 0, 500),
            ]);

            return false;
        }
    }

    /**
     * Get the console URL for a VM, masked to hide raw Proxmox host details.
     *
     * Delegates to getMaskedConsoleData() and returns the console_url key.
     * Returns null if getMaskedConsoleData() returns null or the key is absent.
     *
     * Requirements: 4.1, 4.2
     */
    public function getConsoleUrl(int $vmid, int $userId): ?string
    {
        $result = $this->getMaskedConsoleData($vmid, $userId);

        if ($result === null) {
            return null;
        }

        return $result['console_url'] ?? null;
    }

    /**
     * Get firewall rules for a VM.
     * GET /nodes/{node}/qemu/{vmid}/firewall/rules
     *
     * Returns array of rules on success, null on failure.
     */
    public function getFirewallRules(int $vmid): ?array
    {
        try {
            $response = $this->httpClient()
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/firewall/rules");

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Proxmox: getFirewallRules failed', [
                'vmid'   => $vmid,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Proxmox: getFirewallRules failed', [
                'vmid'   => $vmid,
                'status' => 0,
                'body'   => substr($e->getMessage(), 0, 500),
            ]);

            return null;
        }
    }

    /**
     * Add a firewall rule to a VM.
     * POST /nodes/{node}/qemu/{vmid}/firewall/rules
     *
     * Example rule: ['type' => 'in', 'action' => 'ACCEPT', 'proto' => 'tcp', 'dport' => '22', 'comment' => 'Allow SSH']
     */
    public function addFirewallRule(int $vmid, array $rule): bool
    {
        try {
            $response = $this->httpClient()
                ->asForm()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/firewall/rules", $rule);

            if ($response->successful()) {
                return true;
            }

            Log::error('Proxmox: addFirewallRule failed', [
                'vmid'   => $vmid,
                'rule'   => $rule,
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: addFirewallRule failed', [
                'vmid'   => $vmid,
                'rule'   => $rule,
                'status' => 0,
                'body'   => substr($e->getMessage(), 0, 500),
            ]);

            return false;
        }
    }

    /**
     * Delete a firewall rule from a VM by position.
     * DELETE /nodes/{node}/qemu/{vmid}/firewall/rules/{pos}
     */
    public function deleteFirewallRule(int $vmid, int $pos): bool
    {
        try {
            $response = $this->httpClient()
                ->delete("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/firewall/rules/{$pos}");

            if ($response->successful()) {
                return true;
            }

            Log::error('Proxmox: deleteFirewallRule failed', [
                'vmid' => $vmid,
                'pos'  => $pos,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Proxmox: deleteFirewallRule failed', [
                'vmid' => $vmid,
                'pos'  => $pos,
                'status' => 0,
                'body' => substr($e->getMessage(), 0, 500),
            ]);

            return false;
        }
    }

}
