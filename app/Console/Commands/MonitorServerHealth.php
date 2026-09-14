<?php

namespace App\Console\Commands;

use App\Services\HealthMonitorService;
use Illuminate\Console\Command;

class MonitorServerHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:health 
                            {--check= : Run specific check (vm_status|node_resources|api_health|all)}
                            {--vm= : Check specific VM ID}
                            {--once : Run once and exit (don\'t loop)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor server health and send alerts';

    /**
     * Execute the console command.
     */
    public function handle(HealthMonitorService $monitor): int
    {
        $checkType = $this->option('check') ?? 'all';
        $vmId = $this->option('vm');
        $runOnce = $this->option('once');

        if ($runOnce) {
            $this->runCheck($monitor, $checkType, $vmId);
            return self::SUCCESS;
        }

        // Run continuously every 60 seconds
        $this->info('Starting health monitor (Press Ctrl+C to stop)...');
        
        while (true) {
            $this->runCheck($monitor, $checkType, $vmId);
            sleep(60);
        }
    }

    private function runCheck(HealthMonitorService $monitor, string $checkType, ?string $vmId): void
    {
        $this->info('[' . now()->format('Y-m-d H:i:s') . '] Running health checks...');

        match ($checkType) {
            'vm_status' => $this->runVmCheck($monitor, $vmId),
            'node_resources' => $monitor->checkNodeResources(),
            'api_health' => $monitor->checkApiHealth(),
            'all' => $monitor->runAllChecks(),
            default => $this->error("Unknown check type: {$checkType}"),
        };

        // Show summary
        $summary = $monitor->getHealthSummary();
        $statusIcon = match ($summary['status']) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '🚨',
        };
        
        $this->line("{$statusIcon} Status: {$summary['status']}");
        $this->line("   Critical: {$summary['checks']['critical']}, Warning: {$summary['checks']['warning']}, Healthy: {$summary['checks']['healthy']}");
    }

    private function runVmCheck(HealthMonitorService $monitor, ?string $vmId): void
    {
        if ($vmId) {
            $vm = \App\Models\ProxmoxVm::findOrFail($vmId);
            $monitor->checkVmHealth($vm);
        } else {
            // Check all VMs
            foreach (\App\Models\ProxmoxVm::all() as $vm) {
                $monitor->checkVmHealth($vm);
            }
        }
    }
}
