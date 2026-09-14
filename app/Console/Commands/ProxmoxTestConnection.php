<?php

namespace App\Console\Commands;

use App\Services\ProxmoxApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProxmoxTestConnection extends Command
{
    protected $signature = 'proxmox:test-connection {--debug : Show detailed debug output}';
    protected $description = 'Test Proxmox VE API connection';

    public function handle()
    {
        $this->info('Testing Proxmox VE API connection...');
        $this->info('API URL: ' . config('proxmox.api_url', 'Not set'));
        $this->info('Node: ' . config('proxmox.node', 'Not set'));
        
        // Get settings from DB
        $settings = $this->getSettings();
        $this->info('Settings source: ' . (empty($settings) ? 'Config/Env' : 'Database'));
        
        $token = $settings['proxmox_api_token'] ?? config('proxmox.api_token');
        if ($token) {
            $this->info('API Token: ' . substr($token, 0, 25) . '...');
        } else {
            $this->error('API Token: NOT SET!');
        }
        
        $this->newLine();
        
        // Direct API test
        $this->info('1. Testing direct API connection to /version endpoint...');
        $this->testVersionEndpoint($token);
        
        $this->newLine();
        
        // Test getting next VM ID
        $this->info('2. Testing getNextVmId...');
        $proxmox = app(ProxmoxApiService::class);
        $nextId = $proxmox->getNextVmId();
        if ($nextId) {
            $this->info("   ✓ Success! Next available VM ID: {$nextId}");
        } else {
            $this->error('   ✗ Failed to get next VM ID');
            $this->warn('   Check logs: tail -f storage/logs/laravel.log');
        }
        
        $this->newLine();
        
        // Test listing VMs
        $this->info('3. Testing listVms...');
        $vms = $proxmox->listVms();
        $this->info("   ✓ Found " . count($vms) . " VMs");
        
        if (count($vms) > 0) {
            $this->table(
                ['VM ID', 'Name', 'Status'],
                collect($vms)->map(fn($vm) => [
                    $vm['vmid'],
                    $vm['name'] ?? 'N/A',
                    $vm['status'] ?? 'unknown'
                ])->toArray()
            );
        }
        
        $this->newLine();
        $this->info('Proxmox connection test complete!');
        
        if ($this->option('debug')) {
            $this->showRecentLogs();
        }
        
        return 0;
    }
    
    protected function getSettings(): array
    {
        try {
            if (\Schema::hasTable('settings')) {
                return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        return [];
    }
    
    protected function testVersionEndpoint(?string $token): void
    {
        $baseUrl = config('proxmox.api_url', 'https://139.99.122.47:8006');
        $url = "{$baseUrl}/api2/json/version";
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'PVEAPIToken=' . $token,
            ])->withoutVerifying()->timeout(10)->get($url);
            
            if ($response->successful()) {
                $data = $response->json('data');
                $this->info('   ✓ Connection successful!');
                $this->info('   Proxmox Version: ' . ($data['version'] ?? 'Unknown'));
                $this->info('   Release: ' . ($data['release'] ?? 'Unknown'));
            } else {
                $this->error('   ✗ Failed with status: ' . $response->status());
                $this->error('   Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('   ✗ Exception: ' . $e->getMessage());
        }
    }
    
    protected function showRecentLogs(): void
    {
        $this->newLine();
        $this->info('Recent Proxmox logs:');
        
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = shell_exec('tail -20 ' . escapeshellarg($logFile) . ' 2>/dev/null | grep -i "proxmox" || echo "No Proxmox logs found"');
            $this->line($logs);
        }
    }
}
