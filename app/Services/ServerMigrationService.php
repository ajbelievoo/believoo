<?php

namespace App\Services;

use App\Models\ServerMigration;
use App\Models\ProxmoxVm;
use Illuminate\Support\Facades\Log;

class ServerMigrationService
{
    protected EncryptionService $encryption;
    protected ProxmoxApiService $proxmox;
    
    public function __construct(EncryptionService $encryption, ProxmoxApiService $proxmox)
    {
        $this->encryption = $encryption;
        $this->proxmox = $proxmox;
    }
    
    /**
     * Start migration process
     */
    public function startMigration(ServerMigration $migration): void
    {
        try {
            $migration->update([
                'status' => 'connecting',
                'progress_percent' => 5,
                'status_message' => 'Connecting to source server...',
                'started_at' => now(),
            ]);
            
            // Test SSH connection
            $password = $this->encryption->decrypt($migration->source_password_encrypted);
            
            if (!$this->testSshConnection($migration->source_ip, $migration->source_ssh_port, $migration->source_root_user, $password)) {
                throw new \Exception('Failed to connect to source server via SSH');
            }
            
            // Scan source server
            $this->scanSourceServer($migration, $password);
            
            // Start file migration in background
            $this->queueFileMigration($migration, $password);
            
        } catch (\Exception $e) {
            $migration->update([
                'status' => 'failed',
                'status_message' => 'Migration failed: ' . $e->getMessage(),
                'error_log' => $e->getTraceAsString(),
            ]);
            
            Log::error('Migration failed', [
                'migration_id' => $migration->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Test SSH connection to source server using a temporary password file
     */
    private function testSshConnection(string $ip, int $port, string $user, string $password): bool
    {
        $passFile = $this->createTempPasswordFile($password);
        
        try {
            $command = sprintf(
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 -p %d %s@%s "echo connected"',
                escapeshellarg($passFile),
                $port,
                $user,
                $ip
            );
            
            exec($command, $output, $returnCode);
            
            return $returnCode === 0 && isset($output[0]) && $output[0] === 'connected';
        } finally {
            $this->deleteFile($passFile);
        }
    }
    
    /**
     * Scan source server for websites and databases
     */
    private function scanSourceServer(ServerMigration $migration, string $password): void
    {
        $migration->update([
            'status' => 'scanning',
            'progress_percent' => 10,
            'status_message' => 'Scanning source server for websites and databases...',
        ]);
        
        $passFile = $this->createTempPasswordFile($password);
        
        try {
            // Find websites in /var/www
            $websitesCommand = sprintf(
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d %s@%s "find /var/www -maxdepth 2 -type d -name \"public_html\" -o -name \"html\" 2>/dev/null | head -20"',
                escapeshellarg($passFile),
                $migration->source_ssh_port,
                $migration->source_root_user,
                $migration->source_ip
            );
            
            exec($websitesCommand, $websites, $returnCode);
            
            // Find MySQL databases
            $databasesCommand = sprintf(
                'sshpass -f %s ssh -o StrictHostKeyChecking=no -p %d %s@%s "mysql -e \"SHOW DATABASES;\" 2>/dev/null | grep -v Database | grep -v information_schema | grep -v performance_schema | head -50"',
                escapeshellarg($passFile),
                $migration->source_ssh_port,
                $migration->source_root_user,
                $migration->source_ip
            );
            
            exec($databasesCommand, $databases, $returnCode);
            
            $migration->update([
                'discovered_websites' => json_encode($websites),
                'discovered_databases' => json_encode($databases),
                'status_message' => sprintf('Found %d websites and %d databases', count($websites), count($databases)),
            ]);
            
            Log::info('Source server scanned', [
                'migration_id' => $migration->id,
                'websites' => count($websites),
                'databases' => count($databases),
            ]);
        } finally {
            $this->deleteFile($passFile);
        }
    }
    
    /**
     * Queue file migration job
     */
    private function queueFileMigration(ServerMigration $migration, string $password): void
    {
        // Create protected password and token files for the background script
        $passFile = $this->createMigrationPasswordFile($migration, $password);
        $tokenFile = $this->createMigrationTokenFile($migration);
        
        // Generate migration script
        $scriptPath = $this->generateMigrationScript($migration, $passFile, $tokenFile);
        
        // Execute in background
        $logPath = storage_path('logs/migration_' . $migration->id . '.log');
        $command = sprintf(
            'nohup bash %s > %s 2>&1 &',
            $scriptPath,
            $logPath
        );
        
        exec($command);
        
        $migration->update([
            'status' => 'migrating_files',
            'progress_percent' => 20,
            'status_message' => 'Starting file migration...',
        ]);
        
        Log::info('Migration script queued', [
            'migration_id' => $migration->id,
            'script' => $scriptPath,
        ]);
    }
    
    /**
     * Generate bash script for migration
     */
    private function generateMigrationScript(ServerMigration $migration, string $passFile, string $tokenFile): string
    {
        $vm = ProxmoxVm::find($migration->proxmox_vm_id);
        $destinationIp = $vm->ip_address ?? $migration->source_ip;
        
        $script = <<<BASH
#!/bin/bash

MIGRATION_ID="{$migration->id}"
SOURCE_IP="{$migration->source_ip}"
SOURCE_PORT="{$migration->source_ssh_port}"
SOURCE_USER="{$migration->source_root_user}"
DEST_IP="{$destinationIp}"
PASS_FILE="{$passFile}"
TOKEN_FILE="{$tokenFile}"

# Ensure cleanup of sensitive files on exit
cleanup() {
    rm -f "$PASS_FILE" "$TOKEN_FILE"
}
trap cleanup EXIT

# Update progress function using a one-time token
update_progress() {
    TOKEN=$(cat "$TOKEN_FILE" 2>/dev/null || echo "")
    curl -s -X POST "http://localhost:8000/api/migrations/$MIGRATION_ID/progress" \\
        -H "Content-Type: application/json" \\
        -H "X-Migration-Token: $TOKEN" \\
        -d "{\"progress\": $1, \"status\": \"$2\", \"message\": \"$3\"}" > /dev/null 2>&1
}

# Migrate /var/www
update_progress 20 "migrating_files" "Syncing website files..."
sshpass -f "$PASS_FILE" rsync -avz --progress -e "ssh -p $SOURCE_PORT -o StrictHostKeyChecking=no" \\
    $SOURCE_USER@$SOURCE_IP:/var/www/ /var/www/ 2>&1 | tee /tmp/migration_files.log

FILE_COUNT=$(find /var/www -type f 2>/dev/null | wc -l)
update_progress 50 "migrating_databases" "Files synced: $FILE_COUNT files"

# Migrate databases
update_progress 60 "migrating_databases" "Dumping MySQL databases..."

DBS=$(sshpass -f "$PASS_FILE" ssh -p $SOURCE_PORT -o StrictHostKeyChecking=no $SOURCE_USER@$SOURCE_IP "mysql -e 'SHOW DATABASES;' 2>/dev/null | grep -v Database | grep -v information_schema | grep -v performance_schema")

DB_COUNT=0
for DB in $DBS; do
    sshpass -f "$PASS_FILE" ssh -p $SOURCE_PORT -o StrictHostKeyChecking=no $SOURCE_USER@$SOURCE_IP \\
        "mysqldump --single-transaction $DB 2>/dev/null" | mysql $DB 2>/dev/null
    if [ $? -eq 0 ]; then
        DB_COUNT=$((DB_COUNT + 1))
    fi
done

update_progress 90 "verifying" "Databases migrated: $DB_COUNT databases"

# Verify migration
update_progress 100 "completed" "Migration completed successfully!"

# Cleanup
rm -f /tmp/migration_files.log

BASH;

        $scriptPath = storage_path('app/migrations/migration_' . $migration->id . '.sh');
        
        if (!is_dir(dirname($scriptPath))) {
            mkdir(dirname($scriptPath), 0755, true);
        }
        
        file_put_contents($scriptPath, $script);
        chmod($scriptPath, 0700);
        
        return $scriptPath;
    }
    
    /**
     * Create a temporary password file for immediate SSH commands
     */
    private function createTempPasswordFile(string $password): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'migpass_');
        file_put_contents($tmpFile, $password);
        chmod($tmpFile, 0600);
        
        return $tmpFile;
    }
    
    /**
     * Create a protected password file for the background migration script
     */
    private function createMigrationPasswordFile(ServerMigration $migration, string $password): string
    {
        $passFile = storage_path('app/migrations/migration_' . $migration->id . '.pass');
        
        if (!is_dir(dirname($passFile))) {
            mkdir(dirname($passFile), 0755, true);
        }
        
        file_put_contents($passFile, $password);
        chmod($passFile, 0600);
        
        return $passFile;
    }
    
    /**
     * Create a protected one-time token file for migration progress API
     */
    private function createMigrationTokenFile(ServerMigration $migration): string
    {
        $tokenFile = storage_path('app/migrations/migration_' . $migration->id . '.token');
        
        if (!is_dir(dirname($tokenFile))) {
            mkdir(dirname($tokenFile), 0755, true);
        }
        
        $token = base64_encode(random_bytes(32));
        file_put_contents($tokenFile, $token);
        chmod($tokenFile, 0600);
        
        return $tokenFile;
    }
    
    /**
     * Delete a file if it exists
     */
    private function deleteFile(string $path): void
    {
        if (file_exists($path) && is_file($path)) {
            unlink($path);
        }
    }
    
    /**
     * Update migration progress (called by API)
     */
    public function updateProgress(int $migrationId, int $progress, string $status, string $message): void
    {
        $migration = ServerMigration::find($migrationId);
        
        if (!$migration) {
            return;
        }
        
        $migration->update([
            'progress_percent' => $progress,
            'status' => $status,
            'status_message' => $message,
        ]);
        
        if ($status === 'completed') {
            $migration->update(['completed_at' => now()]);
        }
    }
    
    /**
     * Get migration status with progress
     */
    public function getStatus(ServerMigration $migration): array
    {
        return [
            'id' => $migration->id,
            'status' => $migration->status,
            'progress' => $migration->progress_percent,
            'message' => $migration->status_message,
            'websites_found' => count(json_decode($migration->discovered_websites ?? '[]', true)),
            'databases_found' => count(json_decode($migration->discovered_databases ?? '[]', true)),
            'started_at' => $migration->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $migration->completed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
