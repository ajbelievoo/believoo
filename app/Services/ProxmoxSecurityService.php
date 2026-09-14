<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Proxmox Security Service
 * 
 * Handles SSH isolation, firewall rules, and security configuration
 * for Proxmox VE infrastructure.
 */
class ProxmoxSecurityService
{
    protected string $baseUrl;
    protected string $apiToken;
    protected string $node;
    protected bool $verifySsl;

    /**
     * Admin IP whitelist for management access
     */
    protected array $adminIpWhitelist;

    /**
     * Management SSH port (non-standard)
     */
    protected int $managementSshPort;

    public function __construct(?string $baseUrl = null, ?string $apiToken = null, ?string $node = null)
    {
        $settings = $this->getSettings();

        $this->baseUrl = $baseUrl ?? $settings['proxmox_api_url'] ?? config('proxmox.api_url', 'https://127.0.0.1:8006');
        $this->apiToken = (string) ($apiToken ?? $settings['proxmox_api_token'] ?? config('proxmox.api_token', ''));
        $this->node = $node ?? $settings['proxmox_node'] ?? config('proxmox.node', 'pve');
        $this->verifySsl = ($settings['proxmox_verify_ssl'] ?? '0') === '1';
        
        $this->adminIpWhitelist = $settings['proxmox_admin_ips'] ?? config('proxmox.admin_ips', ['127.0.0.1']);
        $this->managementSshPort = (int) ($settings['proxmox_mgmt_ssh_port'] ?? config('proxmox.mgmt_ssh_port', 2200));
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

    protected function httpClient()
    {
        return Http::timeout(60)
            ->withOptions([
                'verify' => $this->verifySsl,
            ])
            ->withHeaders([
                'Authorization' => 'PVEAPIToken=' . $this->apiToken,
            ]);
    }

    /**
     * Apply SSH isolation configuration
     * - Move SSH to management port (2200)
     - Restrict root login to admin IPs only
     - Disable password authentication on public port
     */
    public function applySshIsolation(): array
    {
        $results = [];

        try {
            // 1. Configure management SSH (port 2200) - Admin access only
            $mgmtConfig = $this->generateSshdConfig(
                port: $this->managementSshPort,
                permitRootLogin: 'yes',
                passwordAuthentication: 'yes',
                listenAddress: $this->getAdminIps(),
                matchBlocks: []
            );

            // Upload SSH config via Proxmox API
            $results['mgmt_ssh'] = $this->uploadFileToNode(
                '/etc/ssh/sshd_config.d/management.conf',
                $mgmtConfig
            );

            // 2. Configure public SSH (port 22) - VM routing only, no host access
            $publicConfig = $this->generateSshdConfig(
                port: 22,
                permitRootLogin: 'no',
                passwordAuthentication: 'no',
                listenAddress: ['0.0.0.0'],
                matchBlocks: [
                    // Allow VM console access only via specific interface
                    'User *' => [
                        'ForceCommand' => '/usr/bin/pveconsole',
                        'AllowUsers' => 'root@127.0.0.1',
                    ],
                ]
            );

            $results['public_ssh'] = $this->uploadFileToNode(
                '/etc/ssh/sshd_config.d/public.conf',
                $publicConfig
            );

            // 3. Restart SSH service
            $results['ssh_restart'] = $this->executeNodeCommand('systemctl restart sshd');

            Log::info('Proxmox SSH isolation applied', [
                'node' => $this->node,
                'mgmt_port' => $this->managementSshPort,
                'admin_ips' => $this->adminIpWhitelist,
            ]);

            return [
                'success' => true,
                'results' => $results,
                'message' => 'SSH isolation configured. Management access on port ' . $this->managementSshPort,
            ];

        } catch (\Exception $e) {
            Log::error('Proxmox SSH isolation failed', [
                'node' => $this->node,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'results' => $results,
            ];
        }
    }

    /**
     * Generate SSH daemon configuration
     */
    protected function generateSshdConfig(
        int $port,
        string $permitRootLogin,
        string $passwordAuthentication,
        array $listenAddress,
        array $matchBlocks
    ): string {
        $config = "# BelieVoo Proxmox SSH Isolation Configuration\n";
        $config .= "# Generated: " . now()->toDateTimeString() . "\n\n";
        
        $config .= "Port {$port}\n";
        
        foreach ($listenAddress as $addr) {
            $config .= "ListenAddress {$addr}\n";
        }
        
        $config .= "PermitRootLogin {$permitRootLogin}\n";
        $config .= "PasswordAuthentication {$passwordAuthentication}\n";
        $config .= "PubkeyAuthentication yes\n";
        $config .= "ChallengeResponseAuthentication no\n";
        $config .= "UsePAM yes\n";
        $config .= "X11Forwarding no\n";
        $config .= "PrintMotd no\n";
        $config .= "AcceptEnv LANG LC_*\n";
        $config .= "Subsystem sftp /usr/lib/openssh/sftp-server\n";

        // Add Match blocks
        foreach ($matchBlocks as $match => $directives) {
            $config .= "\nMatch {$match}\n";
            foreach ($directives as $key => $value) {
                $config .= "    {$key} {$value}\n";
            }
        }

        return $config;
    }

    /**
     * Get list of admin IPs for management access
     */
    protected function getAdminIps(): array
    {
        if (is_string($this->adminIpWhitelist)) {
            return array_map('trim', explode(',', $this->adminIpWhitelist));
        }
        return $this->adminIpWhitelist;
    }

    /**
     * Apply Proxmox Datacenter Firewall Rules
     * - Client Isolation: Block inter-VM communication
     * - Admin Access: Allow management traffic
     * - Public Services: Allow HTTP/HTTPS
     */
    public function applyClientIsolationFirewall(): array
    {
        try {
            $rules = [
                // Rule 1: Allow established connections
                [
                    'type' => 'in',
                    'action' => 'ACCEPT',
                    'macro' => 'established',
                    'comment' => 'Allow established connections',
                    'enable' => 1,
                ],
                // Rule 2: Allow loopback
                [
                    'type' => 'in',
                    'action' => 'ACCEPT',
                    'iface' => 'lo',
                    'comment' => 'Allow loopback',
                    'enable' => 1,
                ],
                // Rule 3: Allow admin IPs full access
                [
                    'type' => 'in',
                    'action' => 'ACCEPT',
                    'source' => implode(',', $this->getAdminIps()),
                    'comment' => 'Admin IPs full access',
                    'enable' => 1,
                ],
                // Rule 4: Allow management SSH port from admin IPs
                [
                    'type' => 'in',
                    'action' => 'ACCEPT',
                    'dport' => $this->managementSshPort,
                    'source' => implode(',', $this->getAdminIps()),
                    'proto' => 'tcp',
                    'comment' => 'Management SSH from admin IPs only',
                    'enable' => 1,
                ],
                // Rule 5: Block public SSH to host (port 22) - VMs should handle this
                [
                    'type' => 'in',
                    'action' => 'DROP',
                    'dport' => 22,
                    'proto' => 'tcp',
                    'comment' => 'Block direct SSH to host - route to VMs',
                    'enable' => 1,
                ],
                // Rule 6: Allow Proxmox API/Web (8006) from admin IPs only
                [
                    'type' => 'in',
                    'action' => 'ACCEPT',
                    'dport' => 8006,
                    'source' => implode(',', $this->getAdminIps()),
                    'proto' => 'tcp',
                    'comment' => 'Proxmox API/Web admin only',
                    'enable' => 1,
                ],
                // Rule 7: Block inter-VM traffic (Client Isolation)
                [
                    'type' => 'in',
                    'action' => 'DROP',
                    'macro' => 'TCP',
                    'source' => '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16',
                    'dest' => '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16',
                    'comment' => 'Client Isolation - Block inter-VM traffic',
                    'enable' => 1,
                ],
                // Rule 8: Allow DNS
                [
                    'type' => 'in',
                    'action' => 'ACCEPT',
                    'dport' => 53,
                    'proto' => 'udp',
                    'comment' => 'Allow DNS',
                    'enable' => 1,
                ],
                // Rule 9: Allow HTTP/HTTPS outbound
                [
                    'type' => 'out',
                    'action' => 'ACCEPT',
                    'dport' => '80,443',
                    'proto' => 'tcp',
                    'comment' => 'Allow HTTP/HTTPS outbound',
                    'enable' => 1,
                ],
                // Rule 10: Default deny
                [
                    'type' => 'in',
                    'action' => 'DROP',
                    'comment' => 'Default deny',
                    'enable' => 1,
                ],
            ];

            // Apply rules to datacenter level
            $results = [];
            foreach ($rules as $index => $rule) {
                $response = $this->httpClient()
                    ->post("{$this->baseUrl}/api2/json/cluster/firewall/rules", $rule);

                $results[] = [
                    'rule' => $rule['comment'] ?? 'Rule ' . ($index + 1),
                    'success' => $response->successful(),
                    'status' => $response->status(),
                ];
            }

            // Enable firewall at datacenter level
            $this->httpClient()
                ->put("{$this->baseUrl}/api2/json/cluster/firewall/options", [
                    'enable' => 1,
                    'nosmurfs' => 1,
                    'tcpflags' => 1,
                    'ndp' => 1,
                ]);

            Log::info('Proxmox client isolation firewall applied', [
                'node' => $this->node,
                'rules_count' => count($rules),
            ]);

            return [
                'success' => true,
                'results' => $results,
                'message' => 'Client isolation firewall configured with ' . count($rules) . ' rules',
            ];

        } catch (\Exception $e) {
            Log::error('Proxmox firewall configuration failed', [
                'node' => $this->node,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create VM-specific firewall group for allowed communication
     */
    public function createVmSecurityGroup(int $vmid, array $allowedVms = []): array
    {
        try {
            $groupName = 'vm-' . $vmid . '-security-group';
            
            // Create security group
            $this->httpClient()
                ->post("{$this->baseUrl}/api2/json/cluster/firewall/groups", [
                    'group' => $groupName,
                    'comment' => 'Security group for VM ' . $vmid,
                ]);

            // Add rules to allow communication with specific VMs
            foreach ($allowedVms as $allowedVmid) {
                $this->httpClient()
                    ->post("{$this->baseUrl}/api2/json/cluster/firewall/groups/{$groupName}/rules", [
                        'type' => 'in',
                        'action' => 'ACCEPT',
                        'source' => '+vm-' . $allowedVmid . '-security-group',
                        'comment' => 'Allow from VM ' . $allowedVmid,
                        'enable' => 1,
                    ]);
            }

            // Apply group to VM
            $this->httpClient()
                ->put("{$this->baseUrl}/api2/json/nodes/{$this->node}/qemu/{$vmid}/firewall/options", [
                    'enable' => 1,
                    'groups' => $groupName,
                ]);

            return [
                'success' => true,
                'group' => $groupName,
                'message' => 'VM security group created and applied',
            ];

        } catch (\Exception $e) {
            Log::error('VM security group creation failed', [
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
     * Upload file to Proxmox node via API
     */
    protected function uploadFileToNode(string $path, string $content): array
    {
        try {
            // Use exec API to write file
            $encodedContent = base64_encode($content);
            $command = "echo '{$encodedContent}' | base64 -d > {$path} && chmod 644 {$path}";
            
            $response = $this->httpClient()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/execute", [
                    'command' => $command,
                ]);

            return [
                'success' => $response->successful(),
                'path' => $path,
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'path' => $path,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Execute command on Proxmox node
     */
    protected function executeNodeCommand(string $command): array
    {
        try {
            $response = $this->httpClient()
                ->post("{$this->baseUrl}/api2/json/nodes/{$this->node}/execute", [
                    'command' => $command,
                ]);

            return [
                'success' => $response->successful(),
                'command' => $command,
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'command' => $command,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SSH configuration script for manual application
     */
    public function getSshIsolationScript(): string
    {
        $adminIps = implode(' ', $this->getAdminIps());
        
        return <<<SCRIPT
#!/bin/bash
# BelieVoo Proxmox SSH Isolation Script
# Run this on your Proxmox host as root

set -e

echo "=== BelieVoo SSH Isolation Configuration ==="

# Backup original SSH config
cp /etc/ssh/sshd_config /etc/ssh/sshd_config.backup.$(date +%Y%m%d_%H%M%S)

# Create management SSH config (port 2200)
cat > /etc/ssh/sshd_config.d/management.conf << 'EOF'
# Management SSH - Admin Access Only
Port {$this->managementSshPort}
PermitRootLogin yes
PasswordAuthentication yes
PubkeyAuthentication yes
ListenAddress 127.0.0.1
EOF

# Add admin IPs to management config
for ip in {$adminIps}; do
    echo "ListenAddress \$ip" >> /etc/ssh/sshd_config.d/management.conf
done

# Create public SSH config (port 22) - Restricted
cat > /etc/ssh/sshd_config.d/public.conf << 'EOF'
# Public SSH - VM Console Only
Port 22
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication no
ChallengeResponseAuthentication no
X11Forwarding no
EOF

# Restart SSH
echo "Restarting SSH service..."
systemctl restart sshd

echo "=== SSH Isolation Complete ==="
echo "Management SSH: port {$this->managementSshPort} (admin IPs only)"
echo "Public SSH: port 22 (disabled for host, routed to VMs)"
echo ""
echo "IMPORTANT: Test management SSH before closing current session!"
echo "ssh -p {$this->managementSshPort} root@<your-proxmox-ip>"
SCRIPT;
    }

    /**
     * Get firewall configuration commands for manual application
     */
    public function getFirewallCommands(): string
    {
        $adminIps = implode(',', $this->getAdminIps());
        
        return <<<COMMANDS
# BelieVoo Proxmox Firewall Configuration
# Run these commands on your Proxmox host as root

# Enable firewall at datacenter level
echo "Enabling datacenter firewall..."
pvesh set /cluster/firewall/options --enable 1

# Add firewall rules
echo "Adding firewall rules..."

# Allow admin IPs
pvesh create /cluster/firewall/rules --type in --action ACCEPT --source "{$adminIps}" --comment "Admin IPs full access"

# Allow management SSH from admin IPs only
pvesh create /cluster/firewall/rules --type in --action ACCEPT --dport {$this->managementSshPort} --source "{$adminIps}" --proto tcp --comment "Management SSH admin only"

# Block direct SSH to host on port 22
pvesh create /cluster/firewall/rules --type in --action DROP --dport 22 --proto tcp --comment "Block SSH to host - route to VMs"

# Block Proxmox Web UI from public
pvesh create /cluster/firewall/rules --type in --action DROP --dport 8006 --proto tcp --comment "Proxmox Web UI - use reverse proxy"

# Client Isolation - Block inter-VM traffic
pvesh create /cluster/firewall/rules --type in --action DROP --source "10.0.0.0/8,172.16.0.0/12,192.168.0.0/16" --dest "10.0.0.0/8,172.16.0.0/12,192.168.0.0/16" --comment "Client Isolation - Block inter-VM"

# Allow DNS
pvesh create /cluster/firewall/rules --type in --action ACCEPT --dport 53 --proto udp --comment "Allow DNS"

# Allow HTTP/HTTPS outbound
pvesh create /cluster/firewall/rules --type out --action ACCEPT --dport 80,443 --proto tcp --comment "Allow HTTP/HTTPS outbound"

# Default deny
pvesh create /cluster/firewall/rules --type in --action DROP --comment "Default deny"

echo "=== Firewall Configuration Complete ==="
echo "Admin IPs: {$adminIps}"
echo "Management Port: {$this->managementSshPort}"
COMMANDS;
    }

    /**
     * Generate iptables rules for port 22 routing to VMs
     */
    public function generatePort22RoutingRules(): string
    {
        return <<<RULES
# BelieVoo Port 22 Routing to VMs
# Add these iptables rules to route port 22 to specific VMs

# Clear existing rules for port 22
iptables -t nat -F PREROUTING 2>/dev/null || true

# Example: Route port 22 to VM 101 based on destination IP
# Replace VM_IP with actual VM IP and VM_ID with actual VM ID

# NAT table rules for port forwarding
# Format: iptables -t nat -A PREROUTING -p tcp --dport 22 -d VM_PUBLIC_IP -j DNAT --to-destination VM_INTERNAL_IP:22

# Add your VM routing rules here:
# iptables -t nat -A PREROUTING -p tcp --dport 22 -d 203.0.113.10 -j DNAT --to-destination 10.0.0.101:22
# iptables -t nat -A PREROUTING -p tcp --dport 22 -d 203.0.113.11 -j DNAT --to-destination 10.0.0.102:22

# Masquerade outbound traffic from VMs
iptables -t nat -A POSTROUTING -s 10.0.0.0/8 -o vmbr0 -j MASQUERADE

# Save rules
iptables-save > /etc/iptables/rules.v4 2>/dev/null || iptables-save > /root/iptables-rules.v4

echo "Port 22 routing rules configured"
echo "Edit this script to add your specific VM IP mappings"
RULES;
    }
}
