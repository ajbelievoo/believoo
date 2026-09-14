<?php

namespace App\Jobs;

use App\Models\VpsLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class InstallLicenseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public VpsLicense $license;
    public int $tries = 3;
    public array $backoff = [30, 60, 120]; // Retry after 30s, 60s, 120s

    /**
     * Create a new job instance.
     */
    public function __construct(VpsLicense $license)
    {
        $this->license = $license;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting license installation", [
            'license_id' => $this->license->id,
            'type' => $this->license->type,
            'vm_id' => $this->license->proxmox_vm_id,
        ]);

        $vm = $this->license->proxmoxVm;
        
        if (!$vm) {
            $this->fail("VM not found for license {$this->license->id}");
            return;
        }

        $this->license->update(['status' => 'activating']);

        try {
            $result = match ($this->license->type) {
                'aapanel' => $this->installAaPanelLicense($vm),
                'cpanel', 'cpanel_plus', 'cpanel_pro' => $this->installCpanelLicense($vm),
                'plesk' => $this->installPleskLicense($vm),
                'imunify' => $this->installImunifyLicense($vm),
                'kernelcare' => $this->installKernelcareLicense($vm),
                default => throw new \Exception("Unknown license type: {$this->license->type}"),
            };

            if ($result['success']) {
                $this->license->update([
                    'status' => 'active',
                    'last_verified_at' => now(),
                    'provider_response' => $result,
                ]);

                Log::info("License installed successfully", [
                    'license_id' => $this->license->id,
                    'vm_id' => $vm->id,
                ]);
            } else {
                throw new \Exception($result['message'] ?? 'Installation failed');
            }

        } catch (\Exception $e) {
            Log::error("License installation failed", [
                'license_id' => $this->license->id,
                'error' => $e->getMessage(),
            ]);

            $this->license->update(['status' => 'failed']);
            
            // Notify admins
            $this->notifyAdminsOfFailure($e->getMessage());

            throw $e; // Will trigger retry
        }
    }

    /**
     * Install aaPanel license via SSH
     */
    private function installAaPanelLicense($vm): array
    {
        $licenseKey = decrypt($this->license->license_key);
        
        // SSH command to install aaPanel license
        $command = sprintf(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@%s "bash /www/server/panel/install/install_soft.sh 0 license %s 2>&1"',
            $vm->ip_address,
            $licenseKey
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'message' => $result->output(),
            'exit_code' => $result->exitCode(),
        ];
    }

    /**
     * Install cPanel license via API
     */
    private function installCpanelLicense($vm): array
    {
        $licenseKey = decrypt($this->license->license_key);
        
        // Call cPanel partner API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.cpanel.api_token'),
        ])->post('https://store.cpanel.net/api/v1/licenses', [
            'ip' => $vm->ip_address,
            'package' => $this->getCpanelPackage(),
            'license_key' => $licenseKey,
        ]);

        return [
            'success' => $response->successful(),
            'message' => $response->json('message'),
            'api_response' => $response->json(),
        ];
    }

    /**
     * Install Plesk license
     */
    private function installPleskLicense($vm): array
    {
        $activationCode = $this->license->activation_code;
        
        // SSH to activate Plesk
        $command = sprintf(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@%s "plesk bin license --install %s 2>&1"',
            $vm->ip_address,
            $activationCode
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'message' => $result->output(),
        ];
    }

    /**
     * Install Imunify360 license
     */
    private function installImunifyLicense($vm): array
    {
        $command = sprintf(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@%s "imunify360-agent register --key %s 2>&1"',
            $vm->ip_address,
            decrypt($this->license->license_key)
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'message' => $result->output(),
        ];
    }

    /**
     * Install KernelCare license
     */
    private function installKernelcareLicense($vm): array
    {
        $command = sprintf(
            'ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 root@%s "kcarectl --register %s 2>&1"',
            $vm->ip_address,
            decrypt($this->license->license_key)
        );

        $result = Process::run($command);

        return [
            'success' => $result->successful(),
            'message' => $result->output(),
        ];
    }

    /**
     * Get cPanel package based on license type
     */
    private function getCpanelPackage(): string
    {
        return match ($this->license->type) {
            'cpanel' => 'solo',
            'cpanel_plus' => 'admin',
            'cpanel_pro' => 'pro',
            default => 'solo',
        };
    }

    /**
     * Notify admins of installation failure
     */
    private function notifyAdminsOfFailure(string $error): void
    {
        $admins = \App\Models\User::where('is_admin', true)->get();
        
        $order = \App\Models\LicenseOrder::where('vps_license_id', $this->license->id)->first();
        
        if ($order) {
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\AdminLicenseNotification('failed', $order));
            }
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("License installation job failed permanently", [
            'license_id' => $this->license->id,
            'error' => $exception->getMessage(),
        ]);

        $this->license->update(['status' => 'failed']);
    }
}
