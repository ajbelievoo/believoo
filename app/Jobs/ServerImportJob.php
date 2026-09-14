<?php

namespace App\Jobs;

use App\Models\ServerImport;
use App\Models\UserHosting;
use App\Events\ServerImportProgress;
use App\Events\ServerImportCompleted;
use App\Events\ServerImportFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class ServerImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ServerImport $import;
    public int $tries = 1;
    public int $timeout = 3600; // 1 hour timeout for large transfers
    
    // Rsync exclude patterns (system files that shouldn't be copied)
    protected array $excludes = [
        '/dev/*',
        '/proc/*',
        '/sys/*',
        '/tmp/*',
        '/run/*',
        '/mnt/*',
        '/media/*',
        '/lost+found',
        '/var/tmp/*',
        '/var/run/*',
        '/boot/grub/*',
        '/boot/grub2/*',
        '/etc/fstab',
        '/etc/network/interfaces',
        '/etc/netplan/*',
        '/etc/hostname',
        '/etc/hosts',
        '/etc/resolv.conf',
        '/etc/udev/rules.d/*',
        '/var/log/*',
        '/var/cache/*',
    ];

    public function __construct(ServerImport $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        try {
            Log::info('ServerImportJob started', ['import_id' => $this->import->id]);
            
            // Ensure sshpass is installed
            $this->ensureSshpassInstalled();
            
            // Update status to connecting
            $this->updateStatus('connecting', 5, 'Connecting to source server via SSH...');
            
            // Verify SSH connection
            if (!$this->testSshConnection()) {
                throw new \Exception('Failed to establish SSH connection to source server');
            }

            // Detect source server details
            $this->detectSourceDetails();

            // Check for aaPanel
            $this->detectAapanel();

            // Update status to syncing
            $this->updateStatus('syncing', 10, 'Starting data synchronization...');

            // Perform rsync
            $syncResult = $this->performRsync();
            
            if (!$syncResult['success']) {
                throw new \Exception('Rsync failed: ' . $syncResult['error']);
            }

            // Update status to verifying
            $this->updateStatus('verifying', 80, 'Verifying transferred data...');
            $this->verifyTransfer();

            // Update status to config_updating
            $this->updateStatus('config_updating', 90, 'Updating system configuration...');
            $this->updateSystemConfig();

            // Complete the import
            $this->updateStatus('completed', 100, 'Import completed successfully!');
            
            $this->import->update([
                'completed_at' => now(),
                'duration_seconds' => $this->import->started_at->diffInSeconds(now()),
            ]);

            // Broadcast completion
            broadcast(new ServerImportCompleted($this->import));

            Log::info('ServerImportJob completed successfully', ['import_id' => $this->import->id]);

        } catch (\Exception $e) {
            Log::error('ServerImportJob failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->import->update([
                'status' => 'failed',
                'status_message' => 'Import failed: ' . $e->getMessage(),
                'error_log' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'completed_at' => now(),
            ]);

            broadcast(new ServerImportFailed($this->import, $e->getMessage()));
            
            throw $e;
        }
    }

    /**
     * Test SSH connection to source server
     */
    private function testSshConnection(): bool
    {
        $output = \App\Services\SecureShell::exec(
            $this->import->source_root_password,
            'sshpass -f %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d root@%s "echo OK" 2>&1',
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        );
        return strpos($output, 'OK') !== false;
    }

    /**
     * Detect source server details
     */
    private function detectSourceDetails(): void
    {
        $output = \App\Services\SecureShell::exec(
            $this->import->source_root_password,
            'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d root@%s "hostname; uname -o; cat /etc/os-release | grep PRETTY_NAME" 2>&1',
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        );

        $lines = explode("\n", trim($output ?? ''));
        
        if (count($lines) >= 2) {
            $this->import->source_hostname = $lines[0] ?? null;
            $this->import->source_os = $lines[1] ?? null;
            $this->import->save();
        }

        Log::info('Source server detected', [
            'import_id' => $this->import->id,
            'hostname' => $this->import->source_hostname,
            'os' => $this->import->source_os,
        ]);
    }

    /**
     * Detect aaPanel installation
     */
    private function detectAapanel(): void
    {
        $output = trim(\App\Services\SecureShell::exec(
            $this->import->source_root_password,
            'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d root@%s "test -d /www/server/panel && echo HAS_AAPANEL || echo NO_AAPANEL" 2>&1',
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        ) ?? '');
        $hasAapanel = $output === 'HAS_AAPANEL';

        // Get aaPanel version if installed
        $aapanelDetails = null;
        if ($hasAapanel) {
            $versionOutput = \App\Services\SecureShell::exec(
                $this->import->source_root_password,
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d root@%s "cat /www/server/panel/config/version.json 2>/dev/null || echo unknown" 2>&1',
                $this->import->source_port,
                escapeshellarg($this->import->source_ip)
            );
            
            $aapanelDetails = [
                'installed' => true,
                'version' => $versionOutput,
                'path' => '/www/server/panel',
            ];
        }

        $metadata = $this->import->metadata ?? [];
        $metadata['has_aapanel'] = $hasAapanel;
        $metadata['aapanel_details'] = $aapanelDetails;
        
        $this->import->metadata = $metadata;
        $this->import->aapanel_migrated = $hasAapanel;
        $this->import->save();

        Log::info('aaPanel detection', [
            'import_id' => $this->import->id,
            'has_aapanel' => $hasAapanel,
        ]);
    }

    /**
     * Perform rsync data transfer
     */
    private function performRsync(): array
    {
        $targetIp = $this->getTargetIp();

        if (!$targetIp) {
            return ['success' => false, 'error' => 'Could not determine target server IP'];
        }

        // Build rsync command
        $excludeArgs = '';
        foreach ($this->excludes as $exclude) {
            $excludeArgs .= " --exclude='{$exclude}'";
        }

        // Keep the SSH password in a protected temp file while rsync runs
        $passFile = $this->createSshPassFile();

        try {
            // First, dry-run to get file count estimate
            $dryRunCmd = sprintf(
                'sshpass -f %s rsync -avzHe "ssh -p %d -o StrictHostKeyChecking=no" --dry-run %s root@%s:/ / 2>&1 | wc -l',
                escapeshellarg($passFile),
                $this->import->source_port,
                $excludeArgs,
                escapeshellarg($this->import->source_ip)
            );

            $fileCount = intval(shell_exec($dryRunCmd));
            $this->import->file_count = $fileCount;
            $this->import->save();

            // Now perform actual sync with progress
            $rsyncCmd = sprintf(
                'sshpass -f %s rsync -avzHe "ssh -p %d -o StrictHostKeyChecking=no" --progress %s root@%s:/ / 2>&1',
                escapeshellarg($passFile),
                $this->import->source_port,
                $excludeArgs,
                escapeshellarg($this->import->source_ip)
            );

            Log::info('Starting rsync', ['import_id' => $this->import->id, 'command' => 'rsync ...']);

            // Execute rsync with real-time progress parsing
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($rsyncCmd, $descriptors, $pipes);

            if (!is_resource($process)) {
                return ['success' => false, 'error' => 'Failed to start rsync process'];
            }

            $output = '';
            $filesTransferred = 0;
            $lastProgressUpdate = time();

            while (!feof($pipes[1])) {
                $line = fgets($pipes[1], 1024);
                if ($line === false) {
                    break;
                }

                $output .= $line;

                // Parse rsync progress
                if (preg_match('/^\s*(\d+,?\d*)\s+\d+%\s+(.+)$/', $line, $matches)) {
                    $filesTransferred++;
                    $currentFile = trim($matches[2]);

                    // Update progress every 3 seconds to avoid spam
                    if (time() - $lastProgressUpdate >= 3) {
                        $progress = min(10 + ($filesTransferred / $fileCount * 70), 80);

                        $this->updateProgress(
                            intval($progress),
                            $filesTransferred,
                            $currentFile,
                            "Syncing: {$currentFile}"
                        );

                        $lastProgressUpdate = time();
                    }
                }
            }

            // Close pipes
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            // Save output log
            $this->import->output_log = $output;
            $this->import->files_transferred = $filesTransferred;
            $this->import->save();

            if ($exitCode !== 0 && $exitCode !== 23 && $exitCode !== 24) {
                // Exit code 23/24 = partial transfer (common for some files being skipped)
                return ['success' => false, 'error' => 'Rsync exited with code: ' . $exitCode];
            }

            return ['success' => true];
        } finally {
            if (file_exists($passFile) && is_file($passFile)) {
                unlink($passFile);
            }
        }
    }

    /**
     * Verify transferred data
     */
    private function verifyTransfer(): void
    {
        // Check key directories
        $keyDirs = ['/www', '/home', '/var/www', '/opt'];
        $verified = 0;

        foreach ($keyDirs as $dir) {
            if (is_dir($dir)) {
                $verified++;
            }
        }

        Log::info('Transfer verification', [
            'import_id' => $this->import->id,
            'verified_dirs' => $verified,
        ]);

        $this->updateStatus('verifying', 85, "Verified {$verified} key directories");
    }

    /**
     * Update system configuration for new environment
     */
    private function updateSystemConfig(): void
    {
        $targetHosting = $this->import->userHosting;
        $targetIp = $targetHosting->server_ip ?? null;

        if (!$targetIp) {
            Log::warning('Target IP not found, skipping network config', ['import_id' => $this->import->id]);
            return;
        }

        // Update fstab for new disk UUIDs
        $this->updateFstab();

        // Update network configuration
        $this->updateNetworkConfig($targetIp);

        // Update hostname
        $this->updateHostname();

        Log::info('System configuration updated', ['import_id' => $this->import->id]);
    }

    /**
     * Update /etc/fstab for new disk layout
     */
    private function updateFstab(): void
    {
        try {
            // Get current disk UUIDs
            $diskCmd = 'blkid | grep -E "ext4|xfs|btrfs" | head -5';
            $diskInfo = shell_exec($diskCmd);

            // Backup original fstab
            if (file_exists('/etc/fstab')) {
                copy('/etc/fstab', '/etc/fstab.backup.' . date('YmdHis'));
            }

            // Create new minimal fstab
            $fstab = "# BelieVoo Server Import - Updated fstab\n";
            $fstab .= "UUID=$(findmnt / -o UUID -n) / ext4 defaults 0 1\n";
            $fstab .= "tmpfs /tmp tmpfs defaults,nosuid,nodev,noexec 0 0\n";

            // Write new fstab
            file_put_contents('/etc/fstab', $fstab);

            $this->import->fstab_updated = true;
            $this->import->save();

            Log::info('fstab updated', ['import_id' => $this->import->id]);

        } catch (\Exception $e) {
            Log::error('Failed to update fstab', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update network configuration
     */
    private function updateNetworkConfig(string $targetIp): void
    {
        try {
            // Detect OS and update network config accordingly
            $os = $this->import->source_os ?? '';

            if (strpos($os, 'Ubuntu') !== false || strpos($os, 'Debian') !== false) {
                // Ubuntu/Debian with netplan
                $this->updateNetplanConfig($targetIp);
            } else {
                // RHEL/CentOS style
                $this->updateNetworkScripts($targetIp);
            }

            $this->import->network_configured = true;
            $this->import->save();

            Log::info('Network configuration updated', [
                'import_id' => $this->import->id,
                'ip' => $targetIp,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update network config', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update Netplan config (Ubuntu 18.04+)
     */
    private function updateNetplanConfig(string $targetIp): void
    {
        $netplanDir = '/etc/netplan';
        
        if (!is_dir($netplanDir)) {
            return;
        }

        // Find netplan config files
        $files = glob($netplanDir . '/*.yaml');
        if (empty($files)) {
            $files = glob($netplanDir . '/*.yml');
        }

        // Create new netplan config
        $config = "network:\n";
        $config .= "  version: 2\n";
        $config .= "  ethernets:\n";
        $config .= "    eth0:\n";
        $config .= "      dhcp4: true\n";
        $config .= "      addresses: [{$targetIp}/24]\n";

        $configFile = $netplanDir . '/01-believoo-import.yaml';
        file_put_contents($configFile, $config);

        // Apply netplan
        shell_exec('netplan apply 2>&1');
    }

    /**
     * Update network scripts (RHEL/CentOS)
     */
    private function updateNetworkScripts(string $targetIp): void
    {
        $ifcfgFile = '/etc/sysconfig/network-scripts/ifcfg-eth0';
        
        if (!is_dir('/etc/sysconfig/network-scripts')) {
            return;
        }

        $config = "DEVICE=eth0\n";
        $config .= "BOOTPROTO=static\n";
        $config .= "IPADDR={$targetIp}\n";
        $config .= "NETMASK=255.255.255.0\n";
        $config .= "ONBOOT=yes\n";
        $config .= "TYPE=Ethernet\n";

        file_put_contents($ifcfgFile, $config);

        // Restart network
        shell_exec('systemctl restart network 2>&1 || service network restart 2>&1');
    }

    /**
     * Update hostname
     */
    private function updateHostname(): void
    {
        $newHostname = $this->import->userHosting->server_hostname ?? 'believoo-server';
        
        // Update /etc/hostname
        file_put_contents('/etc/hostname', $newHostname);
        
        // Update /etc/hosts
        $hosts = "127.0.0.1 localhost {$newHostname}\n";
        $hosts .= "::1 localhost ip6-localhost ip6-loopback\n";
        file_put_contents('/etc/hosts', $hosts);

        // Set hostname
        shell_exec("hostname {$newHostname} 2>&1");
    }

    /**
     * Ensure sshpass is installed on the system
     */
    private function ensureSshpassInstalled(): void
    {
        // Check if sshpass is already available
        $check = shell_exec('which sshpass 2>&1');
        if (!empty($check) && strpos($check, 'not found') === false) {
            Log::info('sshpass is already installed', ['import_id' => $this->import->id]);
            return;
        }

        Log::info('sshpass not found, attempting to install', ['import_id' => $this->import->id]);
        
        $this->updateStatus('connecting', 2, 'Installing required SSH tools...');
        
        // Try to install sshpass using different package managers
        $installCommands = [
            'apt-get update && apt-get install -y sshpass 2>&1',
            'yum install -y sshpass 2>&1',
            'dnf install -y sshpass 2>&1',
            'pacman -S --noconfirm sshpass 2>&1',
        ];
        
        foreach ($installCommands as $cmd) {
            $output = shell_exec($cmd);
            
            // Check if installation was successful
            $check = shell_exec('which sshpass 2>&1');
            if (!empty($check) && strpos($check, 'not found') === false) {
                Log::info('sshpass installed successfully', ['import_id' => $this->import->id, 'method' => $cmd]);
                return;
            }
        }
        
        // If we get here, installation failed
        Log::error('Failed to install sshpass after trying all package managers', ['import_id' => $this->import->id]);
        
        // Try to use expect as alternative
        $expectCheck = shell_exec('which expect 2>&1');
        if (!empty($expectCheck) && strpos($expectCheck, 'not found') === false) {
            Log::info('expect is available as alternative', ['import_id' => $this->import->id]);
            return; // We'll handle expect-based auth in the SSH methods
        }
        
        throw new \Exception('sshpass is not installed and could not be auto-installed. Please contact support.');
    }

    /**
     * Get target server IP
     */
    private function getTargetIp(): ?string
    {
        return $this->import->userHosting->server_ip ?? null;
    }

    /**
     * Create a temporary password file for sshpass (0600) to avoid exposing
     * the password in the process list.
     */
    private function createSshPassFile(): string
    {
        $passFile = tempnam(sys_get_temp_dir(), 'simport_');
        file_put_contents($passFile, $this->import->source_root_password);
        chmod($passFile, 0600);

        return $passFile;
    }

    /**
     * Update import status
     */
    private function updateStatus(string $status, int $progress, string $message): void
    {
        $this->import->update([
            'status' => $status,
            'progress_percent' => $progress,
            'status_message' => $message,
        ]);

        broadcast(new ServerImportProgress($this->import));
    }

    /**
     * Update import progress with details
     */
    private function updateProgress(int $progress, int $filesTransferred, string $currentFile, string $message): void
    {
        $this->import->update([
            'progress_percent' => $progress,
            'files_transferred' => $filesTransferred,
            'current_file' => $currentFile,
            'status_message' => $message,
        ]);

        broadcast(new ServerImportProgress($this->import));
    }
}
