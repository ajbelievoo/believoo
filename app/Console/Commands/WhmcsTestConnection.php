<?php

namespace App\Console\Commands;

use App\Services\WhmcsApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

class WhmcsTestConnection extends Command
{
    protected $signature = 'whmcs:test-connection {--debug : Show detailed debug output}';
    protected $description = 'Test WHMCS API connection';

    public function handle()
    {
        $this->info('Testing WHMCS API connection...');
        
        // Get settings
        $baseUrl = Setting::getValue('whmcs_base_url') ?: config('server-management.whmcs.base_url');
        $apiIdentifier = Setting::getValue('whmcs_api_identifier') ?: config('server-management.whmcs.api_identifier');
        $apiSecret = Setting::getValue('whmcs_api_secret') ?: config('server-management.whmcs.api_secret');
        
        $this->info('WHMCS Base URL: ' . ($baseUrl ?: 'NOT SET!'));
        $this->info('API Identifier: ' . ($apiIdentifier ? substr($apiIdentifier, 0, 15) . '...' : 'NOT SET!'));
        $this->info('API Secret: ' . ($apiSecret ? '********' . substr($apiSecret, -4) : 'NOT SET!'));
        $this->info('Settings source: ' . (Setting::getValue('whmcs_base_url') ? 'Database' : 'Config/Env'));
        
        $this->newLine();
        
        // Check if configured
        if (empty($baseUrl) || empty($apiIdentifier) || empty($apiSecret)) {
            $this->error('WHMCS is NOT fully configured!');
            $this->warn('Please fill in all WHMCS settings in Admin > Settings > Server Management');
            return 1;
        }
        
        $this->info('Configuration: OK (all fields filled)');
        $this->newLine();
        
        // Test API connection
        $this->info('Testing API connection...');
        
        try {
            $whmcs = app(WhmcsApiService::class);
            
            // Try to get stats (lightweight call)
            $response = \Illuminate\Support\Facades\Http::asForm()
                ->timeout(30)
                ->post(rtrim($baseUrl, '/') . '/includes/api.php', [
                    'action' => 'GetStats',
                    'identifier' => $apiIdentifier,
                    'secret' => $apiSecret,
                    'responsetype' => 'json',
                ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['result']) && $data['result'] === 'error') {
                    $this->error('WHMCS API returned error: ' . ($data['message'] ?? 'Unknown error'));
                    $this->warn('Check your API credentials in WHMCS Admin > Setup > Staff Management > Manage API Credentials');
                    return 1;
                }
                
                $this->info('Connection: SUCCESS!');
                
                if (isset($data['income'])) {
                    $this->info('Total Income: ' . ($data['income']['totalincome'] ?? 'N/A'));
                    $this->info('Total Clients: ' . ($data['totalclients'] ?? 'N/A'));
                    $this->info('Active Orders: ' . ($data['activeorders'] ?? 'N/A'));
                }
            } else {
                $this->error('Connection failed with status: ' . $response->status());
                $this->error('Response: ' . $response->body());
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            Log::error('WHMCS test connection failed', ['error' => $e->getMessage()]);
            return 1;
        }
        
        $this->newLine();
        $this->info('WHMCS connection test complete!');
        
        if ($this->option('debug')) {
            $this->showRecentLogs();
        }
        
        return 0;
    }
    
    protected function showRecentLogs(): void
    {
        $this->newLine();
        $this->info('Recent WHMCS logs:');
        
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = shell_exec('tail -20 ' . escapeshellarg($logFile) . ' 2>/dev/null | grep -i "whmcs" || echo "No WHMCS logs found"');
            $this->line($logs);
        }
    }
}
