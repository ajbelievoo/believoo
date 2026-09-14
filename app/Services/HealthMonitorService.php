<?php

namespace App\Services;

use App\Models\AdminAlertSetting;
use App\Models\ProxmoxNode;
use App\Models\ProxmoxVm;
use App\Models\ServerHealthCheck;
use Illuminate\Support\Facades\Log;

class HealthMonitorService
{
    protected ProxmoxApiService $proxmox;
    protected AlertService $alertService;

    public function __construct(ProxmoxApiService $proxmox, AlertService $alertService)
    {
        $this->proxmox = $proxmox;
        $this->alertService = $alertService;
    }

    /**
     * Run all health checks
     */
    public function runAllChecks(): void
    {
        Log::info('Starting health monitoring checks');
        
        // Get all VMs
        $vms = ProxmoxVm::all();
        
        foreach ($vms as $vm) {
            $this->checkVmHealth($vm);
        }
        
        // Check node resources
        $this->checkNodeResources();
        
        // Process unalerted critical checks
        $this->processAlerts();
        
        Log::info('Health monitoring checks completed');
    }

    /**
     * Check individual VM health
     */
    public function checkVmHealth(ProxmoxVm $vm): ServerHealthCheck
    {
        try {
            $vmStatus = $this->proxmox->getVmStatus($vm->vmid);
            
            $isRunning = ($vmStatus['status'] ?? '') === 'running';
            $uptime = $vmStatus['uptime'] ?? 0;
            
            // Determine status
            if (!$isRunning) {
                $status = 'critical';
                $message = "VM {$vm->hostname} ({$vm->vmid}) is DOWN";
            } else {
                $status = 'healthy';
                $message = "VM {$vm->hostname} is running normally";
            }
            
            $healthCheck = ServerHealthCheck::create([
                'proxmox_vm_id' => $vm->id,
                'check_type' => 'vm_status',
                'status' => $status,
                'metric_value' => $uptime,
                'metric_unit' => 's',
                'message' => $message,
                'details' => [
                    'vmid' => $vm->vmid,
                    'hostname' => $vm->hostname,
                    'status' => $vmStatus['status'] ?? 'unknown',
                    'cpu_usage' => $vmStatus['cpu'] ?? 0,
                    'memory_usage' => $vmStatus['mem'] ?? 0,
                    'max_memory' => $vmStatus['maxmem'] ?? 0,
                    'uptime' => $uptime,
                ],
            ]);
            
            // Update VM status in database
            $vm->update([
                'status' => $vmStatus['status'] ?? 'unknown',
                'uptime' => $uptime,
            ]);
            
            return $healthCheck;
            
        } catch (\Exception $e) {
            Log::error("Health check failed for VM {$vm->vmid}", ['error' => $e->getMessage()]);
            
            return ServerHealthCheck::create([
                'proxmox_vm_id' => $vm->id,
                'check_type' => 'vm_status',
                'status' => 'unknown',
                'message' => "Health check failed: {$e->getMessage()}",
                'details' => ['error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Check Proxmox node resources
     */
    public function checkNodeResources(): ServerHealthCheck
    {
        try {
            $resources = $this->proxmox->getNodeResourceUsage();
            
            $cpuPercent = $resources['cpu_percent'] ?? 0;
            $ramPercent = $resources['ram_percent'] ?? 0;
            
            // Determine status based on thresholds
            if ($cpuPercent > 90 || $ramPercent > 95) {
                $status = 'critical';
                $message = "Node resources critical: CPU {$cpuPercent}%, RAM {$ramPercent}%";
            } elseif ($cpuPercent > 75 || $ramPercent > 85) {
                $status = 'warning';
                $message = "Node resources high: CPU {$cpuPercent}%, RAM {$ramPercent}%";
            } else {
                $status = 'healthy';
                $message = "Node resources normal: CPU {$cpuPercent}%, RAM {$ramPercent}%";
            }
            
            return ServerHealthCheck::create([
                'proxmox_vm_id' => null,
                'check_type' => 'node_resources',
                'status' => $status,
                'metric_value' => max($cpuPercent, $ramPercent),
                'metric_unit' => '%',
                'message' => $message,
                'details' => $resources,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Node resource check failed', ['error' => $e->getMessage()]);
            
            return ServerHealthCheck::create([
                'proxmox_vm_id' => null,
                'check_type' => 'node_resources',
                'status' => 'unknown',
                'message' => "Node check failed: {$e->getMessage()}",
                'details' => ['error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Check API connectivity
     */
    public function checkApiHealth(): ServerHealthCheck
    {
        try {
            $start = microtime(true);
            $vms = $this->proxmox->listVms();
            $responseTime = (microtime(true) - $start) * 1000;
            
            $status = $responseTime > 5000 ? 'warning' : 'healthy';
            
            return ServerHealthCheck::create([
                'proxmox_vm_id' => null,
                'check_type' => 'api_health',
                'status' => $status,
                'metric_value' => $responseTime,
                'metric_unit' => 'ms',
                'message' => "API response time: {$responseTime}ms",
                'details' => [
                    'vm_count' => count($vms),
                    'response_time_ms' => $responseTime,
                ],
            ]);
            
        } catch (\Exception $e) {
            return ServerHealthCheck::create([
                'proxmox_vm_id' => null,
                'check_type' => 'api_health',
                'status' => 'critical',
                'message' => "API health check failed: {$e->getMessage()}",
                'details' => ['error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Process unalerted health checks and send alerts
     */
    public function processAlerts(): void
    {
        $settings = AdminAlertSetting::where(function ($query) {
            $query->where('telegram_enabled', true)
                  ->orWhere('email_enabled', true);
        })->get();
        
        if ($settings->isEmpty()) {
            Log::warning('No alert settings configured');
            return;
        }
        
        // Get unalerted critical/warning checks
        $checks = ServerHealthCheck::unalerted()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();
        
        foreach ($checks as $check) {
            foreach ($settings as $setting) {
                // Check thresholds
                if ($check->metric_value && !$this->shouldAlert($setting, $check)) {
                    continue;
                }
                
                $this->alertService->sendAlert($check, $setting);
            }
        }
    }

    /**
     * Get health status summary
     */
    public function getHealthSummary(): array
    {
        $latestChecks = ServerHealthCheck::where('created_at', '>=', now()->subHours(1))
            ->latest()
            ->get()
            ->unique('check_type');
        
        $critical = $latestChecks->where('status', 'critical')->count();
        $warning = $latestChecks->where('status', 'warning')->count();
        $healthy = $latestChecks->where('status', 'healthy')->count();
        
        $overall = $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'healthy');
        
        return [
            'status' => $overall,
            'checks' => [
                'critical' => $critical,
                'warning' => $warning,
                'healthy' => $healthy,
            ],
            'last_check' => $latestChecks->first()?->created_at,
        ];
    }

    /**
     * Determine if alert should be sent based on thresholds
     */
    private function shouldAlert(AdminAlertSetting $settings, ServerHealthCheck $check): bool
    {
        return match ($check->check_type) {
            'vm_status' => $settings->alert_vm_down && $check->status === 'critical',
            'node_resources' => $settings->alert_high_resource && 
                               $check->metric_value > $settings->cpu_threshold,
            default => true,
        };
    }

    /**
     * Get VM metrics history
     */
    public function getVmMetricsHistory(int $vmId, int $hours = 24): array
    {
        return ServerHealthCheck::where('proxmox_vm_id', $vmId)
            ->where('check_type', 'vm_status')
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at')
            ->get()
            ->map(fn ($check) => [
                'timestamp' => $check->created_at,
                'status' => $check->status,
                'cpu' => $check->details['cpu_usage'] ?? null,
                'memory' => $check->details['memory_usage'] ?? null,
            ])
            ->toArray();
    }
}
