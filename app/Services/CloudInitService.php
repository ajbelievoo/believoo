<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cloud-Init Service
 * 
 * Handles automatic Cloud-Init configuration injection for instant-on VPS provisioning.
 * Ensures VMs are configured with client-specific credentials and network settings.
 */
class CloudInitService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected string $node;
    protected bool $verifySsl;
    protected ProxmoxApiService $proxmox;

    public function __construct(?string $baseUrl = null, ?string $apiToken = null, ?string $node = null, ?ProxmoxApiService $proxmox = null)
    {
        $settings = $this->getSettings();

        $this->baseUrl = $baseUrl ?? $settings['proxmox_api_url'] ?? config('proxmox.api_url', 'https://127.0.0.1:8006');
        $this->apiToken = (string) ($apiToken ?? $settings['proxmox_api_token'] ?? config('proxmox.api_token', ''));
        $this->node = $node ?? $settings['proxmox_node'] ?? config('proxmox.node', 'pve');
        $this->verifySsl = ($settings['proxmox_verify_ssl'] ?? '0') === '1';
        $this->proxmox = $proxmox ?? new ProxmoxApiService($baseUrl, $apiToken, $node);
    }

    /**
     * Create service instance for a specific node
     */
    public static function forNode(string $baseUrl, string $apiToken, string $node): self
    {
        return new self($baseUrl, $apiToken, $node);
    }

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
     * Auto-inject Cloud-Init configuration during VM provisioning
     * 
     * @param int $vmid VM ID
     * @param array $config Configuration array:
     *   - hostname: Server hostname
     *   - username: Root username (default: root)
     *   - password: Root password (auto-generated if not provided)
     *   - ssh_authorized_keys: Array of SSH public keys
     *   - ip_address: Static IPv4 address
     *   - gateway: Default gateway
     *   - dns_servers: Array of DNS servers
     *   - packages: Array of packages to install
     *   - runcmd: Array of commands to run on first boot
     * @return array Result with success status
     */
    public function autoInjectCloudInit(int $vmid, array $config): array
    {
        try {
            Log::info('CloudInit: Starting auto-injection', ['vmid' => $vmid]);

            // Step 1: Generate or use provided password
            $password = $config['password'] ?? $this->generateSecurePassword();
            $username = $config['username'] ?? 'root';
            $hostname = $config['hostname'] ?? 'vps-' . $vmid;

            // Step 2: Build Cloud-Init configuration
            $cloudInitConfig = $this->buildCloudInitConfig($vmid, array_merge($config, [
                'password' => $password,
                'username' => $username,
                'hostname' => $hostname,
            ]));

            // Step 3: Upload Cloud-Init user-data snippet
            $snippetUploaded = $this->uploadCloudInitSnippet($vmid, $cloudInitConfig['user_data']);

            if (!$snippetUploaded) {
                Log::warning('CloudInit: Snippet upload failed, falling back to direct config', ['vmid' => $vmid]);
            }

            // Step 4: Apply Cloud-Init configuration to VM
            $vmConfig = [
                'ciuser' => $username,
                'cipassword' => $password,
                'searchdomain' => $config['searchdomain'] ?? 'believoo.com',
            ];

            // Add network configuration if IP is provided
            if (!empty($config['ip_address'])) {
                $ipconfig = $this->buildIpConfig($config);
                if ($ipconfig) {
                    $vmConfig['ipconfig0'] = $ipconfig;
                }
            }

            // Add DNS servers
            $dnsServers = $config['dns_servers'] ?? ['8.8.8.8', '8.8.4.4'];
            $vmConfig['nameserver'] = implode(',', $dnsServers);

            // Add snippet reference if uploaded
            if ($snippetUploaded) {
                $vmConfig['cicustom'] = 'user=local:snippets/cloud-init-' . $vmid . '.yaml';
            }

            // Apply config to VM
            $configApplied = $this->proxmox->updateVmConfig($vmid, $vmConfig);

            if (!$configApplied) {
                throw new \Exception('Failed to apply Cloud-Init configuration to VM');
            }

            Log::info('CloudInit: Configuration applied', [
                'vmid' => $vmid,
                'snippet_uploaded' => $snippetUploaded,
            ]);

            // Step 5: Force reboot to activate Cloud-Init
            $rebootResult = $this->forceCloudInitReboot($vmid);

            return [
                'success' => true,
                'vmid' => $vmid,
                'password' => $password,
                'username' => $username,
                'hostname' => $hostname,
                'config_applied' => true,
                'snippet_uploaded' => $snippetUploaded,
                'reboot_initiated' => $rebootResult['success'],
                'reboot_method' => $rebootResult['method'] ?? null,
                'message' => 'Cloud-Init configured and reboot initiated for instant-on',
            ];

        } catch (\Exception $e) {
            Log::error('CloudInit: Auto-injection failed', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'vmid' => $vmid,
                'error' => $e->getMessage(),
                'message' => 'Cloud-Init configuration failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build complete Cloud-Init configuration
     */
    protected function buildCloudInitConfig(int $vmid, array $config): array
    {
        $hostname = $config['hostname'] ?? 'vps-' . $vmid;
        $username = $config['username'] ?? 'root';
        $password = $config['password'];
        $sshKeys = $config['ssh_authorized_keys'] ?? [];
        $packages = $config['packages'] ?? ['qemu-guest-agent', 'curl', 'wget', 'nano'];
        $customCommands = $config['runcmd'] ?? [];

        // Generate user-data YAML
        $userData = [
            '#cloud-config',
            'hostname: ' . $hostname,
            'manage_etc_hosts: true',
            'users:',
            '  - name: ' . $username,
            '    gecos: BelieVoo Administrator',
            '    groups: sudo',
            '    shell: /bin/bash',
            '    sudo: ["ALL=(ALL) NOPASSWD:ALL"]',
        ];

        // Add SSH authorized keys if provided
        if (!empty($sshKeys)) {
            $userData[] = '    ssh_authorized_keys:';
            foreach ($sshKeys as $key) {
                $userData[] = '      - ' . trim($key);
            }
        }

        // Add chpasswd for password
        $userData[] = 'chpasswd:';
        $userData[] = '  list: |';
        $userData[] = '    ' . $username . ':' . $password;
        $userData[] = '  expire: False';

        // SSH configuration
        $userData[] = 'ssh_pwauth: true';
        $userData[] = 'disable_root: false';

        // Package installation
        $userData[] = 'package_update: true';
        $userData[] = 'package_upgrade: true';
        $userData[] = 'packages:';
        foreach ($packages as $package) {
            $userData[] = '  - ' . $package;
        }

        // Write files (BelieVoo branding)
        $userData[] = 'write_files:';
        $userData[] = '  - path: /etc/believoo-provisioned';
        $userData[] = '    content: |';
        $userData[] = '      Provisioned by BelieVoo on ' . now()->toDateTimeString();
        $userData[] = '      VM ID: ' . $vmid;
        $userData[] = '    permissions: "0644"';

        // MOTD branding
        $userData[] = '  - path: /etc/update-motd.d/99-believoo';
        $userData[] = '    content: |';
        $userData[] = '      #!/bin/bash';
        $userData[] = '      echo ""';
        $userData[] = '      echo "================================================"';
        $userData[] = '      echo "  Welcome to BelieVoo VPS"';
        $userData[] = '      echo "  Hostname: ' . $hostname . '"';
        $userData[] = '      echo "================================================"';
        $userData[] = '    permissions: "0755"';

        // Boot commands
        $userData[] = 'bootcmd:';
        $userData[] = '  - systemctl enable qemu-guest-agent || true';
        $userData[] = '  - systemctl start qemu-guest-agent || true';

        // Run commands
        $userData[] = 'runcmd:';
        $userData[] = '  - systemctl enable qemu-guest-agent';
        $userData[] = '  - systemctl start qemu-guest-agent';
        $userData[] = '  - sed -i "s/#PermitRootLogin.*/PermitRootLogin yes/" /etc/ssh/sshd_config';
        $userData[] = '  - sed -i "s/PasswordAuthentication no/PasswordAuthentication yes/" /etc/ssh/sshd_config';
        $userData[] = '  - systemctl restart sshd || systemctl restart ssh';

        // Add custom commands
        foreach ($customCommands as $command) {
            $userData[] = '  - ' . $command;
        }

        // Final reboot to ensure all changes take effect
        $userData[] = '  - echo "BelieVoo provisioning complete" > /var/log/believoo-provision.log';
        $userData[] = '  - shutdown -r +1 "Rebooting after BelieVoo Cloud-Init configuration"';

        $userDataYaml = implode("\n", $userData);

        return [
            'user_data' => $userDataYaml,
            'meta_data' => $this->buildMetaData($vmid, $hostname),
        ];
    }

    /**
     * Build network IP configuration for Proxmox
     */
    protected function buildIpConfig(array $config): ?string
    {
        $ip = $config['ip_address'] ?? null;
        $gateway = $config['gateway'] ?? null;
        $subnet = $config['subnet'] ?? '24';

        if (empty($ip)) {
            return null;
        }

        // Format: ip=10.0.0.100/24,gw=10.0.0.1
        $ipconfig = "ip={$ip}/{$subnet}";
        
        if (!empty($gateway)) {
            $ipconfig .= ",gw={$gateway}";
        }

        return $ipconfig;
    }

    /**
     * Build Cloud-Init metadata
     */
    protected function buildMetaData(int $vmid, string $hostname): string
    {
        $metaData = [
            'instance-id: ' . $vmid,
            'local-hostname: ' . $hostname,
        ];

        return implode("\n", $metaData);
    }

    /**
     * Upload Cloud-Init snippet to Proxmox storage
     */
    protected function uploadCloudInitSnippet(int $vmid, string $content): bool
    {
        try {
            $filename = 'cloud-init-' . $vmid . '.yaml';
            
            // Create the snippets directory if it doesn't exist
            $mkdirResponse = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->withHeaders([
                    'Authorization' => 'PVEAPIToken=' . $this->apiToken,
                ])
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/execute", [
                    'command' => 'mkdir -p /var/lib/vz/snippets',
                ]);

            // Write content to file
            $encodedContent = base64_encode($content);
            $command = "echo '{$encodedContent}' | base64 -d > /var/lib/vz/snippets/{$filename} && chmod 644 /var/lib/vz/snippets/{$filename}";
            
            $response = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->withHeaders([
                    'Authorization' => 'PVEAPIToken=' . $this->apiToken,
                ])
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/execute", [
                    'command' => $command,
                ]);

            if ($response->successful()) {
                Log::info('CloudInit: Snippet uploaded', ['vmid' => $vmid, 'filename' => $filename]);
                return true;
            }

            Log::warning('CloudInit: Snippet upload failed', [
                'vmid' => $vmid,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::warning('CloudInit: Snippet upload exception', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Force VM reboot to activate Cloud-Init
     * Tries graceful reboot first, then force stop/start if needed
     */
    protected function forceCloudInitReboot(int $vmid): array
    {
        try {
            // Check VM status
            $vmStatus = $this->proxmox->getVmStatus($vmid);
            $isRunning = ($vmStatus['status'] ?? '') === 'running';

            Log::info('CloudInit: Initiating reboot', [
                'vmid' => $vmid,
                'current_status' => $vmStatus['status'] ?? 'unknown',
            ]);

            if (!$isRunning) {
                // VM is stopped, just start it
                Log::info('CloudInit: VM is stopped, starting', ['vmid' => $vmid]);
                $started = $this->proxmox->startVm($vmid);
                
                return [
                    'success' => $started,
                    'method' => 'start',
                    'message' => $started ? 'VM started with Cloud-Init' : 'Failed to start VM',
                ];
            }

            // Method 1: Try graceful reboot via agent if available
            $agentReboot = $this->rebootViaAgent($vmid);
            if ($agentReboot['success']) {
                return $agentReboot;
            }

            // Method 2: Try ACPI shutdown + start
            Log::info('CloudInit: Attempting ACPI reboot', ['vmid' => $vmid]);
            
            $shutdown = $this->proxmox->shutdownVm($vmid);
            if ($shutdown) {
                // Wait for shutdown with timeout
                $shutdownSuccess = $this->waitForShutdown($vmid, 30);
                
                if ($shutdownSuccess) {
                    sleep(2); // Brief pause
                    $started = $this->proxmox->startVm($vmid);
                    
                    return [
                        'success' => $started,
                        'method' => 'acpi_reboot',
                        'message' => $started ? 'VM rebooted via ACPI' : 'Shutdown succeeded but start failed',
                    ];
                }
            }

            // Method 3: Force stop + start (hard reboot)
            Log::info('CloudInit: Attempting force reboot', ['vmid' => $vmid]);
            
            $stopped = $this->proxmox->stopVm($vmid);
            if ($stopped) {
                sleep(3); // Wait for force stop
                $started = $this->proxmox->startVm($vmid);
                
                return [
                    'success' => $started,
                    'method' => 'force_reboot',
                    'message' => $started ? 'VM force rebooted successfully' : 'Force stop succeeded but start failed',
                ];
            }

            return [
                'success' => false,
                'method' => 'none',
                'message' => 'All reboot methods failed',
            ];

        } catch (\Exception $e) {
            Log::error('CloudInit: Reboot failed', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'method' => 'error',
                'message' => 'Reboot exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Attempt to reboot VM via QEMU guest agent
     */
    protected function rebootViaAgent(int $vmid): array
    {
        try {
            // Check if agent is running
            $pingResponse = Http::timeout(10)
                ->withOptions(['verify' => $this->verifySsl])
                ->withHeaders([
                    'Authorization' => 'PVEAPIToken=' . $this->apiToken,
                ])
                ->get("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/agent/ping");

            if (!$pingResponse->successful()) {
                return ['success' => false, 'method' => 'agent', 'message' => 'Agent not responding'];
            }

            // Send reboot command via agent
            $rebootResponse = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->withHeaders([
                    'Authorization' => 'PVEAPIToken=' . $this->apiToken,
                ])
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/agent/shutdown", [
                    'mode' => 'reboot',
                ]);

            if ($rebootResponse->successful()) {
                Log::info('CloudInit: Agent reboot successful', ['vmid' => $vmid]);
                return [
                    'success' => true,
                    'method' => 'agent',
                    'message' => 'VM rebooted via QEMU agent',
                ];
            }

            return ['success' => false, 'method' => 'agent', 'message' => 'Agent reboot command failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'method' => 'agent', 'message' => 'Agent error: ' . $e->getMessage()];
        }
    }

    /**
     * Wait for VM to shutdown
     */
    protected function waitForShutdown(int $vmid, int $timeout = 30): bool
    {
        $start = time();
        
        while (time() - $start < $timeout) {
            $status = $this->proxmox->getVmStatus($vmid);
            
            if (($status['status'] ?? '') === 'stopped') {
                return true;
            }
            
            sleep(1);
        }
        
        return false;
    }

    /**
     * Rebuild VM with Cloud-Init (for OS reinstall)
     */
    public function rebuildWithCloudInit(int $vmid, array $config): array
    {
        try {
            Log::info('CloudInit: Starting VM rebuild', ['vmid' => $vmid]);

            // Stop VM if running
            $status = $this->proxmox->getVmStatus($vmid);
            if (($status['status'] ?? '') === 'running') {
                $this->proxmox->stopVm($vmid);
                sleep(3);
            }

            // Reset Cloud-Init state (forces re-run on next boot)
            $this->resetCloudInitState($vmid);

            // Apply new Cloud-Init configuration
            $result = $this->autoInjectCloudInit($vmid, $config);

            if ($result['success']) {
                Log::info('CloudInit: Rebuild complete', ['vmid' => $vmid]);
            } else {
                Log::error('CloudInit: Rebuild failed', [
                    'vmid' => $vmid,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('CloudInit: Rebuild exception', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Reset Cloud-Init state to force re-run
     */
    protected function resetCloudInitState(int $vmid): bool
    {
        try {
            // Remove Cloud-Init semaphore files via QEMU agent or direct command
            $commands = [
                'rm -f /var/lib/cloud/instance/boot-finished',
                'rm -f /var/lib/cloud/seed/nocloud-net/meta-data',
                'rm -f /var/lib/cloud/seed/nocloud-net/user-data',
                'rm -rf /var/lib/cloud/instances/*',
                'rm -rf /var/lib/cloud/instance',
                'cloud-init clean --logs --seed',
            ];

            // Try to execute via API exec
            foreach ($commands as $command) {
                Http::timeout(10)
                    ->withOptions(['verify' => $this->verifySsl])
                    ->withHeaders([
                        'Authorization' => 'PVEAPIToken=' . $this->apiToken,
                    ])
                    ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/execute", [
                        'command' => $command,
                    ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::warning('CloudInit: State reset failed (non-critical)', [
                'vmid' => $vmid,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate secure random password
     */
    protected function generateSecurePassword(int $length = 16): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Get Cloud-Init status for a VM
     */
    public function getCloudInitStatus(int $vmid): array
    {
        try {
            $vmConfig = $this->proxmox->getVmConfig($vmid);
            
            $cloudInitKeys = ['ciuser', 'cipassword', 'ipconfig0', 'nameserver', 'cicustom', 'searchdomain'];
            $configured = false;
            
            foreach ($cloudInitKeys as $key) {
                if (!empty($vmConfig[$key])) {
                    $configured = true;
                    break;
                }
            }

            return [
                'configured' => $configured,
                'config' => array_intersect_key($vmConfig, array_flip($cloudInitKeys)),
            ];

        } catch (\Exception $e) {
            return [
                'configured' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
