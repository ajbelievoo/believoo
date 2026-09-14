<?php

namespace App\Console\Commands;

use App\Models\ProxmoxVm;
use App\Models\UserHosting;
use App\Services\ProxmoxApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetupVmWithCloudImage extends Command
{
    protected $signature = 'proxmox:setup-cloud-vm {vmid} {--password=} {--ip=dhcp} {--gateway=}';
    protected $description = 'Setup a VM with Ubuntu cloud image (pre-installed OS)';

    public function handle(): int
    {
        $vmid     = (int) $this->argument('vmid');
        $password = $this->option('password') ?? 'BelieVoo@' . rand(1000, 9999);
        $ip       = $this->option('ip') ?? 'dhcp';
        $gateway  = $this->option('gateway') ?? '139.99.122.1';

        $proxmox = app(ProxmoxApiService::class);

        $this->info("Setting up VM {$vmid} with Ubuntu cloud image...");

        // Step 1: Stop VM
        $this->info('Stopping VM...');
        $proxmox->stopVm($vmid);
        sleep(3);

        // Step 2: Download cloud image on Proxmox server
        $this->info('Downloading Ubuntu 22.04 cloud image...');
        $this->info('Run this on Proxmox server:');
        $this->line('');
        $this->line('  wget -O /var/lib/vz/template/iso/ubuntu-22.04-cloud.img \\');
        $this->line('    https://cloud-images.ubuntu.com/jammy/current/jammy-server-cloudimg-amd64.img');
        $this->line('');
        $this->line('  # Then import disk:');
        $this->line("  qm importdisk {$vmid} /var/lib/vz/template/iso/ubuntu-22.04-cloud.img local --format qcow2");
        $this->line('');
        $this->line('  # Attach new disk as scsi1:');
        $this->line("  qm set {$vmid} --scsi1 local:{$vmid}/vm-{$vmid}-disk-1.qcow2");
        $this->line('');
        $this->line('  # Set boot from new disk:');
        $this->line("  qm set {$vmid} --boot order=scsi1 --ide2 none,media=cdrom");
        $this->line('');
        $this->line('  # Set cloud-init:');
        $this->line("  qm set {$vmid} --cipassword '{$password}' --ciuser root --ipconfig0 ip={$ip}" . ($ip !== 'dhcp' ? "/24,gw={$gateway}" : ''));
        $this->line('');
        $this->line('  # Start VM:');
        $this->line("  qm start {$vmid}");
        $this->line('');

        // Step 3: Update config via API (what we can do remotely)
        $this->info('Updating VM config via API...');
        $ipConfig = $ip === 'dhcp' ? 'ip=dhcp' : "ip={$ip}/24,gw={$gateway}";
        
        $result = $proxmox->updateVmConfig($vmid, [
            'boot'       => 'order=scsi0;net0',
            'ide2'       => 'none,media=cdrom',
            'cipassword' => $password,
            'ciuser'     => 'root',
            'ipconfig0'  => $ipConfig,
        ]);

        if ($result) {
            $this->info('VM config updated successfully');
        }

        // Update DB
        $vm = ProxmoxVm::where('vmid', $vmid)->first();
        if ($vm) {
            $vm->update(['status' => 'stopped']);
        }

        $hosting = UserHosting::where('vps_id', $vmid)->first();
        if ($hosting) {
            $hosting->update(['root_password' => $password]);
            $this->info("Password saved: {$password}");
        }

        $this->info('');
        $this->warn('IMPORTANT: Run the commands above on Proxmox server to complete setup.');
        $this->info('After running commands, VM will boot with Ubuntu pre-installed.');
        $this->info("SSH: ssh root@{$ip} (password: {$password})");

        return self::SUCCESS;
    }
}
