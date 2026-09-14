<?php

namespace App\Console\Commands;

use App\Models\ProxmoxNode;
use Illuminate\Console\Command;

class CheckProxmoxNodes extends Command
{
    protected $signature = 'check:nodes';
    protected $description = 'Check Proxmox nodes configuration';

    public function handle()
    {
        $this->info('=== Checking Proxmox Nodes ===');
        
        $nodes = ProxmoxNode::all();
        
        if ($nodes->isEmpty()) {
            $this->error('❌ NO PROXMOX NODES FOUND!');
            $this->info('Creating a default node...');
            
            // Create default Singapore node
            ProxmoxNode::create([
                'name' => 'sg1-proxmox',
                'display_name' => 'Singapore DC-1',
                'hostname' => '139.99.122.47',
                'port' => 8006,
                'status' => 'active',
                'location' => 'Singapore',
                'country_code' => 'SG',
                'flag_emoji' => '🇸🇬',
                'total_cpu_cores' => 16,
                'total_memory_bytes' => 34359738368, // 32 GB
                'total_disk_bytes' => 2104533975040, // 1.95 TB
                'max_vms' => 50,
                'current_vms' => 0,
                'is_default' => true,
            ]);
            
            $this->info('✅ Default node created: sg1-proxmox');
            $this->warn('⚠️  IMPORTANT: You need to add API token in admin panel!');
            return 1;
        }
        
        foreach ($nodes as $node) {
            $this->newLine();
            $this->info("Node: {$node->display_name} ({$node->name})");
            $this->line("  ID: {$node->id}");
            $this->line("  Status: {$node->status}");
            $this->line("  Hostname: {$node->hostname}:{$node->port}");
            $this->line("  VMs: {$node->current_vms} / {$node->max_vms}");
            
            $apiToken = $node->getDecryptedApiToken();
            if ($apiToken) {
                $this->info("  API Token: ✅ Set");
            } else {
                $this->error("  API Token: ❌ MISSING!");
                $this->warn("  → Add API token via Admin > Datacenter > Edit");
            }
            
            // Check if node can be used for auto-provisioning
            if ($node->status !== 'active') {
                $this->error("  → Node not active! Change status to 'active'");
            }
            
            if ($node->current_vms >= $node->max_vms * 0.8) {
                $this->warn("  → Node at {$node->current_vms}/{$node->max_vms} capacity (>80%)");
            }
        }
        
        $this->newLine();
        $this->info('=== Summary ===');
        
        $activeNodes = $nodes->where('status', 'active')->count();
        $nodesWithApi = $nodes->filter(function($n) {
            return $n->getDecryptedApiToken() !== null;
        })->count();
        
        $this->info("Total Nodes: {$nodes->count()}");
        $this->info("Active Nodes: {$activeNodes}");
        $this->info("Nodes with API Token: {$nodesWithApi}");
        
        if ($activeNodes === 0) {
            $this->error('❌ No active nodes! VM auto-creation will fail.');
        }
        
        if ($nodesWithApi === 0) {
            $this->error('❌ No nodes with API token! VM auto-creation will fail.');
        }
        
        return 0;
    }
}
