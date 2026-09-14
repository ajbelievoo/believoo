<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveKitProvisioningService
{
    protected ProxmoxApiService $proxmox;

    public function __construct(ProxmoxApiService $proxmox)
    {
        $this->proxmox = $proxmox;
    }

    /**
     * Provision a new LiveKit VM on Proxmox
     *
     * @param string $name VM name suffix
     * @param string $ip Static IP to assign (CIDR format, e.g. 139.99.43.204/24)
     * @param string $gateway Gateway IP
     * @return array|null VM details or null on failure
     */
    public function provisionLiveKitVm(string $name, string $ip, string $gateway): ?array
    {
        try {
            $vmName = 'livekit-' . $name;

            $cloudInit = $this->buildLiveKitCloudInit($ip, $gateway);

            $config = [
                'name'     => $vmName,
                'cpu'      => 4,
                'memory'   => 8192,
                'disk'     => 100,
                'storage'  => 'local',
                'iso'      => 'ubuntu-22.04-live-server-amd64.iso',
                'cipassword' => $this->generateSecurePassword(),
                'ciuser'     => 'livekit',
                'ipconfig0'  => 'ip=' . $ip . ',gw=' . $gateway,
                'cloud_init' => $cloudInit,
            ];

            $result = $this->proxmox->createAndStartVm($config);

            if (!$result) {
                Log::error('LiveKit: Failed to create VM');
                return null;
            }

            $vmid = $result['vmid'];

            // Wait for VM to get IP via QEMU guest agent
            $vmIp = $this->waitForVmIp($vmid);

            if (!$vmIp) {
                Log::warning('LiveKit: Could not detect VM IP, using configured IP');
                $vmIp = explode('/', $ip)[0];
            }

            // Deploy LiveKit stack via SSH
            $deployResult = $this->deployLiveKitStack($vmIp, $config['cipassword']);

            if (!$deployResult) {
                Log::error('LiveKit: Failed to deploy stack on VM ' . $vmid);
                return null;
            }

            return [
                'vmid'     => $vmid,
                'name'     => $vmName,
                'ip'       => $vmIp,
                'password' => $config['cipassword'],
                'status'   => 'active',
            ];
        } catch (\Throwable $e) {
            Log::error('LiveKit provisioning failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build cloud-init for LiveKit VM
     */
    protected function buildLiveKitCloudInit(string $ip, string $gateway): string
    {
        $ipOnly = explode('/', $ip)[0];

        return "#cloud-config\n" .
            "package_update: true\n" .
            "packages:\n" .
            "  - qemu-guest-agent\n" .
            "  - docker.io\n" .
            "  - docker-compose\n" .
            "  - nginx\n" .
            "  - certbot\n" .
            "  - python3-certbot-nginx\n" .
            "  - ufw\n" .
            "runcmd:\n" .
            "  - systemctl enable qemu-guest-agent\n" .
            "  - systemctl start qemu-guest-agent\n" .
            "  - usermod -aG docker livekit\n" .
            "  - systemctl enable docker\n" .
            "  - systemctl start docker\n" .
            "  - mkdir -p /opt/livekit\n" .
            "  - chown livekit:livekit /opt/livekit\n" .
            "  - ufw allow 443/tcp\n" .
            "  - ufw allow 7880/tcp\n" .
            "  - ufw allow 7881/tcp\n" .
            "  - ufw allow 7882/udp\n" .
            "  - ufw allow 50000:60000/udp\n" .
            "  - ufw --force enable\n" .
            "  - echo 'LiveKit VM ready at {$ipOnly}' > /var/lib/cloud/instance/boot-finished\n";
    }

    /**
     * Wait for VM to report IP via guest agent
     */
    protected function waitForVmIp(int $vmid, int $timeout = 300): ?string
    {
        $start = time();
        while (time() - $start < $timeout) {
            try {
                $interfaces = $this->proxmox->getVmNetworkInterfaces($vmid);
                if (!empty($interfaces)) {
                    foreach ($interfaces as $iface) {
                        if (!empty($iface['ip-addresses'])) {
                            foreach ($iface['ip-addresses'] as $addr) {
                                if ($addr['ip-address-type'] === 'ipv4' && $addr['ip-address'] !== '127.0.0.1') {
                                    return $addr['ip-address'];
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Guest agent not ready yet
            }
            sleep(10);
        }
        return null;
    }

    /**
     * Deploy LiveKit Docker stack via SSH
     */
    protected function deployLiveKitStack(string $ip, string $password): bool
    {
        $stackContent = file_get_contents(base_path('scripts/livekit-docker-compose.yml'));
        $envContent = file_get_contents(base_path('scripts/livekit.env'));

        $remotePath = '/opt/livekit';

        // Write files via SCP or SSH
        $sshCmds = [
            "mkdir -p {$remotePath}",
            "echo '{$stackContent}' | base64 -d > {$remotePath}/docker-compose.yml",
            "echo '{$envContent}' | base64 -d > {$remotePath}/.env",
            "cd {$remotePath} && docker compose up -d",
        ];

        foreach ($sshCmds as $cmd) {
            $result = $this->runSshCommand($ip, 'livekit', $password, $cmd);
            if (!$result) {
                Log::error('LiveKit: SSH command failed', ['cmd' => $cmd]);
                return false;
            }
        }

        return true;
    }

    /**
     * Run SSH command on remote VM
     */
    protected function runSshCommand(string $ip, string $user, string $password, string $command): bool
    {
        $output = \App\Services\SecureShell::execWithCode(
            $password,
            'sshpass -f %s ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null %s@%s "%s" 2>&1',
            [escapeshellarg($user), escapeshellarg($ip), str_replace('"', '\"', $command)],
            $output
        );

        return $output === 0;
    }

    /**
     * Generate secure random password
     */
    protected function generateSecurePassword(): string
    {
        return bin2hex(random_bytes(16));
    }
}
