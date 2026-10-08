<?php

namespace App\Console\Commands;

use App\Models\ProxmoxNode;
use App\Services\ProxmoxApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDatacenters extends Command
{
    protected $signature = 'datacenter:sync {--node= : Specific node ID to sync} {--all : Sync all active nodes}';
    protected $description = 'Sync datacenter nodes with Proxmox API to get real-time hardware stats';

    public function handle()
    {
        $nodeId = $this->option('node');
        $syncAll = $this->option('all');

        if ($nodeId) {
            // Sync specific node
            $node = ProxmoxNode::find($nodeId);
            if (!$node) {
                $this->error("Node with ID {$nodeId} not found");
                return 1;
            }
            return $this->syncNode($node);
        }

        if ($syncAll || (!$nodeId && !$syncAll)) {
            // Sync all active nodes
            $nodes = ProxmoxNode::where('status', 'active')->get();
            
            $this->info("Syncing {$nodes->count()} active datacenter nodes...");
            $bar = $this->output->createProgressBar($nodes->count());
            
            $success = 0;
            $failed = 0;
            
            foreach ($nodes as $node) {
                $result = $this->syncNode($node, true);
                if ($result === 0) {
                    $success++;
                } else {
                    $failed++;
                }
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("Sync complete: {$success} succeeded, {$failed} failed");
            
            return $failed > 0 ? 1 : 0;
        }

        return 0;
    }

    private function syncNode(ProxmoxNode $node, bool $silent = false): int
    {
        try {
            $apiToken = $node->getDecryptedApiToken();
            $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;

            if ($apiToken) {
                // Create Proxmox API service with node-specific credentials
                $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);
            } else {
                // Fall back to ticket auth with configured username/password
                $proxmox = new ProxmoxApiService($proxmoxUrl, null, $node->name);
                Log::info('Auto-sync: using password auth fallback', ['node' => $node->name]);
            }

            // Fetch real-time stats from Proxmox API
            $apiStats = $proxmox->getNodeResourceUsage();

            if (!$apiStats['available']) {
                if (!$silent) {
                    $this->error("Failed to connect to {$node->display_name}");
                }
                Log::warning('Auto-sync: API not available', ['node' => $node->name]);
                return 1;
            }

            // Update node with real hardware stats from API
            $updateData = [
                'last_synced_at' => now(),
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

            $ramGb = isset($apiStats['ram_total']) ? round($apiStats['ram_total'] / 1024 / 1024 / 1024, 1) : null;
            $diskTb = isset($apiStats['disk_total']) ? round($apiStats['disk_total'] / 1024 / 1024 / 1024 / 1024, 2) : null;

            if (!$silent) {
                $this->info("✓ {$node->display_name}: {$apiStats['cpu_cores']} cores, {$ramGb}GB RAM, CPU: {$apiStats['cpu_percent']}%, RAM: {$apiStats['ram_percent']}%");
            }

            Log::info('Auto-sync: Node synced successfully', [
                'node' => $node->name,
                'cores' => $apiStats['cpu_cores'],
                'ram_gb' => $ramGb,
                'disk_tb' => $diskTb,
                'cpu_usage' => $apiStats['cpu_percent'],
                'ram_usage' => $apiStats['ram_percent'],
            ]);

            return 0;

        } catch (\Exception $e) {
            if (!$silent) {
                $this->error("Failed to sync {$node->display_name}: {$e->getMessage()}");
            }
            Log::error('Auto-sync: Failed to sync node', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }
}
