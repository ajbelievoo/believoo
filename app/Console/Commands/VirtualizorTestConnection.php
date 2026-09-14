<?php

namespace App\Console\Commands;

use App\Services\VirtualizorApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class VirtualizorTestConnection extends Command
{
    protected $signature = 'virtualizor:test-connection {--debug : Show detailed debug output}';
    protected $description = 'Test Virtualizor API connection';

    public function handle()
    {
        $this->info('Testing Virtualizor API connection...');
        
        // Get settings
        $baseUrl = Setting::getValue('virtualizor_base_url') ?: config('server-management.virtualizor.base_url');
        $apiKey = Setting::getValue('virtualizor_api_key') ?: config('server-management.virtualizor.api_key');
        $apiPass = Setting::getValue('virtualizor_api_pass') ?: config('server-management.virtualizor.api_pass');
        $port = Setting::getValue('virtualizor_port') ?: config('server-management.virtualizor.port', 4085);
        
        $this->info('Virtualizor Base URL: ' . ($baseUrl ?: 'NOT SET!'));
        $this->info('API Port: ' . $port);
        $this->info('API Key: ' . ($apiKey ? substr($apiKey, 0, 15) . '...' : 'NOT SET!'));
        $this->info('API Pass: ' . ($apiPass ? '********' : 'NOT SET!'));
        $this->info('Settings source: ' . (Setting::getValue('virtualizor_base_url') ? 'Database' : 'Config/Env'));
        
        $this->newLine();
        
        // Check if configured
        $virtualizor = app(VirtualizorApiService::class);
        
        if (!$virtualizor->isConfigured()) {
            $this->error('Virtualizor is NOT configured!');
            $this->warn('Please fill in all Virtualizor settings in Admin > Settings > Server Management');
            return 1;
        }
        
        $this->info('Configuration: OK (API Key and Pass are set)');
        $this->newLine();
        
        // Test API connection by listing VPS
        $this->info('Testing API connection (listing VPS)...');
        
        try {
            $vpsList = $virtualizor->getVpsList();
            
            if ($vpsList === null) {
                $this->error('Connection failed! Check logs for details.');
                $this->warn('Common issues:');
                $this->warn('- Wrong API credentials');
                $this->warn('- Wrong port (default: 4085)');
                $this->warn('- Firewall blocking connection');
                $this->warn('- SSL certificate issues (self-signed certs are ignored)');
                return 1;
            }
            
            $this->info('Connection: SUCCESS!');
            
            // Count VPS
            $vpsCount = isset($vpsList['vs']) ? count($vpsList['vs']) : 0;
            $this->info('Total VPS found: ' . $vpsCount);
            
            if ($vpsCount > 0 && $this->option('debug')) {
                $this->newLine();
                $this->info('First few VPS:');
                $counter = 0;
                foreach ($vpsList['vs'] as $vpsId => $vps) {
                    if ($counter >= 5) break;
                    $this->line("  - VPS ID {$vpsId}: " . ($vps['hostname'] ?? 'N/A') . ' (' . ($vps['status'] ?? 'unknown') . ')');
                    $counter++;
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            Log::error('Virtualizor test connection failed', ['error' => $e->getMessage()]);
            return 1;
        }
        
        $this->newLine();
        $this->info('Virtualizor connection test complete!');
        
        if ($this->option('debug')) {
            $this->showRecentLogs();
        }
        
        return 0;
    }
    
    protected function showRecentLogs(): void
    {
        $this->newLine();
        $this->info('Recent Virtualizor logs:');
        
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = shell_exec('tail -20 ' . escapeshellarg($logFile) . ' 2>/dev/null | grep -i "virtualizor" || echo "No Virtualizor logs found"');
            $this->line($logs);
        }
    }
}
