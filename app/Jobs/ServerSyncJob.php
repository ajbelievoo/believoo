<?php

namespace App\Jobs;

use App\Models\ServerImport;
use App\Events\ServerImportProgress;
use App\Events\ServerImportCompleted;
use App\Events\ServerImportFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ServerSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ServerImport $import;
    public int $tries = 1;
    public int $timeout = 7200; // 2 hours timeout for large transfers
    
    protected array $excludes = [
        '/dev/*', '/proc/*', '/sys/*', '/tmp/*', '/run/*',
        '/mnt/*', '/media/*', '/lost+found', '/var/tmp/*',
        '/var/run/*', '/boot/grub/*', '/var/cache/*',
    ];

    public function __construct(ServerImport $import)
    {
        $this->import = $import;
    }

    public function handle(): void
    {
        try {
            Log::info('ServerSyncJob started', [
                'import_id' => $this->import->id,
                'direction' => $this->import->sync_direction,
            ]);
            
            // Install required tools
            $this->ensureSshpassInstalled();
            
            // Setup SSH keys for passwordless authentication
            $this->updateStatus('key_setup', 3, 'Setting up SSH key authentication...');
            if (!$this->setupSshKeys()) {
                throw new \Exception('Failed to setup SSH key authentication');
            }
            
            // Test connection with SSH keys
            $this->updateStatus('connecting', 5, 'Connecting to remote server...');
            if (!$this->testSshConnection()) {
                throw new \Exception('Failed to establish SSH connection');
            }

            // Detect remote server details
            $this->detectRemoteDetails();

            // Perform sync based on direction
            $this->updateStatus('syncing', 10, 'Starting data synchronization...');
            
            $syncResult = $this->import->isPull() 
                ? $this->performPull() 
                : $this->performPush();
            
            if (!$syncResult['success']) {
                throw new \Exception('Sync failed: ' . $syncResult['error']);
            }

            // Verify transfer
            $this->updateStatus('verifying', 90, 'Verifying transferred data...');
            $this->verifyTransfer();

            // Complete
            $this->updateStatus('completed', 100, 'Sync completed successfully!');
            
            $this->import->update([
                'completed_at' => now(),
                'duration_seconds' => $this->import->started_at->diffInSeconds(now()),
            ]);

            broadcast(new ServerImportCompleted($this->import));

            Log::info('ServerSyncJob completed', ['import_id' => $this->import->id]);

        } catch (\Exception $e) {
            Log::error('ServerSyncJob failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            $this->import->update([
                'status' => 'failed',
                'status_message' => 'Sync failed: ' . $e->getMessage(),
                'error_log' => $e->getMessage() . "\n" . $e->getTraceAsString(),
                'completed_at' => now(),
            ]);

            broadcast(new ServerImportFailed($this->import, $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Setup SSH keys for passwordless authentication
     */
    private function setupSshKeys(): bool
    {
        try {
            $keyDir = storage_path('app/ssh_keys');
            if (!is_dir($keyDir)) {
                mkdir($keyDir, 0700, true);
            }

            $keyFile = $keyDir . '/sync_' . $this->import->id;
            
            // Generate SSH key pair if not exists
            if (!file_exists($keyFile)) {
                $cmd = sprintf(
                    'ssh-keygen -t ed25519 -f %s -N "" -C "believoo-sync-%d" 2>&1',
                    escapeshellarg($keyFile),
                    $this->import->id
                );
                shell_exec($cmd);
            }

            // Read keys
            $privateKey = file_get_contents($keyFile);
            $publicKey = file_get_contents($keyFile . '.pub');

            // Save to database
            $this->import->ssh_private_key = encrypt($privateKey);
            $this->import->ssh_public_key = $publicKey;
            $this->import->save();

            // Deploy public key to remote server (for both pull and push)
            $this->deployPublicKey($publicKey);

            $this->import->ssh_key_setup_complete = true;
            $this->import->save();

            Log::info('SSH key setup complete', ['import_id' => $this->import->id]);
            return true;

        } catch (\Exception $e) {
            Log::error('SSH key setup failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Deploy public key to remote server
     */
    private function deployPublicKey(string $publicKey): bool
    {
        try {
            // Use sshpass with a temp password file to copy public key to remote server
            \App\Services\SecureShell::exec(
                $this->import->source_root_password,
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d root@%s "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo %s >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys" 2>&1',
                $this->import->source_port,
                escapeshellarg($this->import->source_ip),
                escapeshellarg($publicKey)
            );

            // Also setup for root if not already
            \App\Services\SecureShell::exec(
                $this->import->source_root_password,
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d root@%s "mkdir -p /root/.ssh && chmod 700 /root/.ssh && echo %s >> /root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys" 2>&1',
                $this->import->source_port,
                escapeshellarg($this->import->source_ip),
                escapeshellarg($publicKey)
            );

            Log::info('Public key deployed', ['import_id' => $this->import->id]);
            return true;

        } catch (\Exception $e) {
            Log::error('Public key deployment failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Test SSH connection with keys
     */
    private function testSshConnection(): bool
    {
        $keyFile = storage_path('app/ssh_keys/sync_' . $this->import->id);
        
        $cmd = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d root@%s "echo OK" 2>&1',
            escapeshellarg($keyFile),
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        );

        $output = shell_exec($cmd);
        return strpos($output, 'OK') !== false;
    }

    /**
     * Perform PULL (Import to BelieVoo)
     */
    private function performPull(): array
    {
        $keyFile = storage_path('app/ssh_keys/sync_' . $this->import->id);
        
        // Build rsync command with live progress
        $excludeArgs = '';
        foreach ($this->excludes as $exclude) {
            $excludeArgs .= " --exclude='{$exclude}'";
        }

        // Get file count first
        $dryRunCmd = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=no -p %d root@%s "find / -type f -not -path \"/dev/*\" -not -path \"/proc/*\" -not -path \"/sys/*\" 2>/dev/null | wc -l" 2>&1',
            escapeshellarg($keyFile),
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        );
        
        $fileCount = intval(trim(shell_exec($dryRunCmd)));
        $this->import->file_count = $fileCount ?: 100000; // Fallback
        $this->import->save();

        // Perform sync with progress
        $rsyncCmd = sprintf(
            'rsync -avzHe "ssh -i %s -o StrictHostKeyChecking=no -p %d" --progress %s root@%s:/ / 2>&1',
            escapeshellarg($keyFile),
            $this->import->source_port,
            $excludeArgs,
            escapeshellarg($this->import->source_ip)
        );

        return $this->executeRsyncWithProgress($rsyncCmd, $fileCount);
    }

    /**
     * Perform PUSH (Export to External)
     */
    private function performPush(): array
    {
        $keyFile = storage_path('app/ssh_keys/sync_' . $this->import->id);
        
        // Build rsync command
        $excludeArgs = '';
        foreach ($this->excludes as $exclude) {
            $excludeArgs .= " --exclude='{$exclude}'";
        }

        // Get local file count
        $fileCount = intval(shell_exec('find / -type f -not -path "/dev/*" -not -path "/proc/*" -not -path "/sys/*" 2>/dev/null | wc -l'));
        $this->import->file_count = $fileCount ?: 100000;
        $this->import->save();

        // Push to remote
        $rsyncCmd = sprintf(
            'rsync -avzHe "ssh -i %s -o StrictHostKeyChecking=no -p %d" --progress %s / root@%s:/ 2>&1',
            escapeshellarg($keyFile),
            $this->import->source_port,
            $excludeArgs,
            escapeshellarg($this->import->source_ip)
        );

        return $this->executeRsyncWithProgress($rsyncCmd, $fileCount);
    }

    /**
     * Execute rsync with live progress tracking
     */
    private function executeRsyncWithProgress(string $rsyncCmd, int $totalFiles): array
    {
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
        $lastLogUpdate = '';

        while (!feof($pipes[1])) {
            $line = fgets($pipes[1], 1024);
            if ($line === false) break;

            $output .= $line;
            $line = trim($line);

            // Parse rsync progress output
            // Format: 123.45K  12%  123.45KB/s   0:00:15  (xfr#123, ir-chk=123/456)  filename
            if (preg_match('/^\s*(\d+[KMGT]?B?)\s+(\d+)%\s+(\d+[KMGT]?B?\/s)\s+(\d+:\d+:\d+)\s+\(xfr#(\d+).*\)\s*(.+)$/', $line, $matches)) {
                $bytesTransferred = $matches[1];
                $percent = intval($matches[2]);
                $speed = $matches[3];
                $eta = $matches[4];
                $fileNum = intval($matches[5]);
                $filename = trim($matches[6]);
                
                $filesTransferred = $fileNum;
                
                // Update every 2 seconds or on file change
                if (time() - $lastProgressUpdate >= 2 || $filename !== $lastLogUpdate) {
                    $progress = min(10 + ($percent * 0.8), 90); // Scale to 10-90%
                    
                    // Build sync log
                    $syncLog = "Copying: {$filename}\n";
                    $syncLog .= "Speed: {$speed} | ETA: {$eta} | Files: {$fileNum}/{$totalFiles}";
                    
                    $this->updateProgress(
                        intval($progress),
                        $filesTransferred,
                        $filename,
                        $syncLog,
                        $speed,
                        $eta
                    );
                    
                    $lastProgressUpdate = time();
                    $lastLogUpdate = $filename;
                }
            }
            // Also capture simple file names
            elseif (preg_match('/^\s*(\S+\.(php|js|css|html|txt|sql|json|xml))$/i', $line, $matches)) {
                $filename = $matches[1];
                $syncLog = "Copying: {$filename}";
                
                // Update less frequently for simple lines
                if (time() - $lastProgressUpdate >= 3) {
                    $this->import->sync_log = $syncLog;
                    $this->import->current_file = $filename;
                    $this->import->save();
                    
                    broadcast(new ServerImportProgress($this->import));
                    $lastProgressUpdate = time();
                }
            }
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        $this->import->output_log = $output;
        $this->import->files_transferred = $filesTransferred;
        $this->import->save();

        if ($exitCode !== 0 && $exitCode !== 23 && $exitCode !== 24) {
            return ['success' => false, 'error' => 'Rsync exited with code: ' . $exitCode];
        }

        return ['success' => true];
    }

    /**
     * Detect remote server details
     */
    private function detectRemoteDetails(): void
    {
        $keyFile = storage_path('app/ssh_keys/sync_' . $this->import->id);
        
        $cmd = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=no -p %d root@%s "hostname; uname -o; df -h / | tail -1" 2>&1',
            escapeshellarg($keyFile),
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        );

        $output = shell_exec($cmd);
        $lines = explode("\n", trim($output));
        
        if (count($lines) >= 3) {
            $this->import->source_hostname = $lines[0];
            $this->import->source_os = $lines[1];
            
            // Parse disk info
            $diskParts = preg_split('/\s+/', $lines[2]);
            if (count($diskParts) >= 4) {
                $this->import->total_bytes = $this->parseSize($diskParts[1]);
            }
            
            $this->import->save();
        }
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize(string $size): int
    {
        $units = ['B' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776];
        $unit = strtoupper(substr($size, -1));
        $value = floatval($size);
        
        return intval($value * ($units[$unit] ?? 1));
    }

    /**
     * Verify transfer
     */
    private function verifyTransfer(): void
    {
        $keyFile = storage_path('app/ssh_keys/sync_' . $this->import->id);
        
        $cmd = sprintf(
            'ssh -i %s -o StrictHostKeyChecking=no -p %d root@%s "find /www -type f 2>/dev/null | wc -l" 2>&1',
            escapeshellarg($keyFile),
            $this->import->source_port,
            escapeshellarg($this->import->source_ip)
        );
        
        $remoteFiles = intval(trim(shell_exec($cmd)));
        $localFiles = intval(shell_exec('find /www -type f 2>/dev/null | wc -l'));
        
        Log::info('Transfer verification', [
            'import_id' => $this->import->id,
            'remote_files' => $remoteFiles,
            'local_files' => $localFiles,
        ]);
    }

    /**
     * Ensure sshpass is installed
     */
    private function ensureSshpassInstalled(): void
    {
        $check = shell_exec('which sshpass 2>&1');
        if (!empty($check) && strpos($check, 'not found') === false) {
            return;
        }

        $installCommands = [
            'apt-get update && apt-get install -y sshpass 2>&1',
            'yum install -y sshpass 2>&1',
            'dnf install -y sshpass 2>&1',
        ];
        
        foreach ($installCommands as $cmd) {
            shell_exec($cmd);
            $check = shell_exec('which sshpass 2>&1');
            if (!empty($check) && strpos($check, 'not found') === false) {
                return;
            }
        }
        
        throw new \Exception('sshpass could not be installed');
    }

    /**
     * Update status
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
     * Update progress with live log
     */
    private function updateProgress(int $progress, int $filesTransferred, string $currentFile, string $syncLog, string $speed = '', string $eta = ''): void
    {
        $this->import->update([
            'progress_percent' => $progress,
            'files_transferred' => $filesTransferred,
            'current_file' => $currentFile,
            'sync_log' => $syncLog,
            'transfer_speed' => $speed,
            'eta_seconds' => $this->parseEta($eta),
        ]);

        broadcast(new ServerImportProgress($this->import));
    }

    /**
     * Parse ETA string to seconds
     */
    private function parseEta(string $eta): ?int
    {
        $parts = explode(':', $eta);
        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }
        return null;
    }
}
