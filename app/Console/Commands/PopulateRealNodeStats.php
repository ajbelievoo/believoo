<?php

namespace App\Console\Commands;

use App\Models\ProxmoxNode;
use App\Services\ProxmoxApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PopulateRealNodeStats extends Command
{
    protected $signature = 'datacenter:populate-real {--node= : Specific node ID to populate} {--all : Populate all active nodes} {--force : Skip confirmation}';
    protected $description = 'Delete placeholder values and populate REAL hardware stats from Proxmox API';

    public function handle()
    {
        $this->warn("╔══════════════════════════════════════════════════════════╗");
        $this->warn("║  POPULATE REAL NODE STATS FROM PROXMOX API               ║");
        $this->warn("╚══════════════════════════════════════════════════════════╝");
        $this->newLine();

        $nodeId = $this->option('node');
        $all = $this->option('all');
        $force = $this->option('force');

        if (!$force && !$this->confirm('This will OVERWRITE all placeholder values with REAL data from Proxmox API. Continue?')) {
            $this->info('Cancelled.');
            return 0;
        }

        // Get nodes to process
        if ($nodeId) {
            $nodes = ProxmoxNode::where('id', $nodeId)->get();
        } elseif ($all) {
            $nodes = ProxmoxNode::where('status', 'active')->get();
        } else {
            // Default: sync all nodes
            $nodes = ProxmoxNode::all();
        }

        if ($nodes->isEmpty()) {
            $this->error('No nodes found to sync!');
            return 1;
        }

        $this->info("Found {$nodes->count()} node(s) to sync...");
        $this->newLine();

        $bar = $this->output->createProgressBar($nodes->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($nodes as $node) {
            $result = $this->populateNode($node);
            if ($result) {
                $success++;
            } else {
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("                    SYNC SUMMARY                           ");
        $this->info("═══════════════════════════════════════════════════════════");
        $this->info("✓ Successful: {$success} nodes");
        if ($failed > 0) {
            $this->error("✗ Failed: {$failed} nodes");
        }
        $this->info("═══════════════════════════════════════════════════════════");

        return $failed > 0 ? 1 : 0;
    }

    private function populateNode(ProxmoxNode $node): bool
    {
        try {
            $this->newLine();
            $this->info("→ Processing: {$node->display_name} ({$node->name})");

            // Show current (placeholder) values
            $this->line("  Current DB values:");
            $this->line("    CPU Cores: {$node->total_cpu_cores}");
            $this->line("    RAM: " . round($node->total_memory_bytes / 1024 / 1024 / 1024) . " GB");
            $this->line("    Disk: " . round($node->total_disk_bytes / 1024 / 1024 / 1024 / 1024, 2) . " TB");

            $apiToken = $node->getDecryptedApiToken();

            if (!$apiToken) {
                $this->warn("  ⚠ No API token configured - using fallback values");
                Log::warning('PopulateReal: No API token', ['node' => $node->name]);
                return false;
            }

            // Create Proxmox API service
            $proxmoxUrl = 'https://' . $node->hostname . ':' . $node->port;
            $proxmox = ProxmoxApiService::forNode($proxmoxUrl, $apiToken, $node->name);

            // Fetch REAL stats from API
            $apiStats = $proxmox->getNodeResourceUsage();

            if (!$apiStats['available']) {
                $this->error("  ✗ Failed to connect to Proxmox API");
                return false;
            }

            // Prepare update data with REAL values
            $updateData = [
                'last_synced_at' => now(),
                'status' => 'active',
                'used_cpu_percent' => $apiStats['cpu_percent'],
                'used_memory_percent' => $apiStats['ram_percent'],
                'used_disk_percent' => $apiStats['disk_percent'],
            ];

            // CPU Cores - REAL from API
            if (!empty($apiStats['cpu_cores'])) {
                $updateData['total_cpu_cores'] = $apiStats['cpu_cores'];
            }

            // RAM - REAL from API (in bytes)
            if (!empty($apiStats['ram_total'])) {
                $updateData['total_memory_bytes'] = $apiStats['ram_total'];
            }

            // Disk - REAL from API (in bytes)
            if (!empty($apiStats['disk_total'])) {
                $updateData['total_disk_bytes'] = $apiStats['disk_total'];
            }

            // UPDATE DATABASE with REAL values
            $node->update($updateData);

            // Show NEW real values
            $ramGb = isset($apiStats['ram_total']) ? round($apiStats['ram_total'] / 1024 / 1024 / 1024, 1) : null;
            $diskTb = isset($apiStats['disk_total']) ? round($apiStats['disk_total'] / 1024 / 1024 / 1024 / 1024, 2) : null;

            $this->newLine();
            $this->info("  ✓ UPDATED with REAL values from Proxmox API:");
            $this->line("    CPU Cores: {$apiStats['cpu_cores']} cores");
            $this->line("    RAM: {$ramGb} GB");
            $this->line("    Disk: {$diskTb} TB");
            $this->line("    Current Usage: CPU {$apiStats['cpu_percent']}%, RAM {$apiStats['ram_percent']}%");
            $this->newLine();

            Log::info('PopulateReal: Node updated with real stats', [
                'node' => $node->name,
                'cpu_cores' => $apiStats['cpu_cores'],
                'ram_gb' => $ramGb,
                'disk_tb' => $diskTb,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->error("  ✗ Error: {$e->getMessage()}");
            Log::error('PopulateReal: Exception', [
                'node' => $node->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
