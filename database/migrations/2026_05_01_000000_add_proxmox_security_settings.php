<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new settings for Proxmox Enterprise Architecture
        $settings = [
            // SSH Isolation Settings
            [
                'key' => 'proxmox_mgmt_ssh_port',
                'value' => '2200',
                'description' => 'SSH management port for Proxmox host (non-standard for security)',
                'type' => 'string',
            ],
            [
                'key' => 'proxmox_admin_ips',
                'value' => '',
                'description' => 'Comma-separated list of admin IPs allowed to access management SSH',
                'type' => 'string',
            ],
            [
                'key' => 'proxmox_ssh_isolation_enabled',
                'value' => '0',
                'description' => 'Enable SSH isolation (port 22 to VMs, 2200 for host)',
                'type' => 'boolean',
            ],
            
            // Console Proxy Settings
            [
                'key' => 'proxmox_console_domain',
                'value' => 'console.believoo.com',
                'description' => 'Domain for masked console access (hides Proxmox IP)',
                'type' => 'string',
            ],
            [
                'key' => 'proxmox_console_proxy_enabled',
                'value' => '1',
                'description' => 'Enable console reverse proxy masking',
                'type' => 'boolean',
            ],
            [
                'key' => 'proxmox_console_session_timeout',
                'value' => '300',
                'description' => 'Console session timeout in seconds (default: 5 minutes)',
                'type' => 'integer',
            ],
            
            // Client Isolation Settings
            [
                'key' => 'proxmox_client_isolation',
                'value' => '1',
                'description' => 'Enable client VM isolation firewall rules',
                'type' => 'boolean',
            ],
            [
                'key' => 'proxmox_blocked_internal_networks',
                'value' => '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16',
                'description' => 'Internal networks blocked from client VM access',
                'type' => 'string',
            ],
            
            // API Security Settings
            [
                'key' => 'proxmox_api_verify_ssl',
                'value' => '0',
                'description' => 'Verify SSL certificate for Proxmox API connections',
                'type' => 'boolean',
            ],
            [
                'key' => 'proxmox_ticket_refresh_interval',
                'value' => '240',
                'description' => 'PVEAuthCookie refresh interval in seconds (default: 4 minutes)',
                'type' => 'integer',
            ],
            
            // Cloud-Init Settings
            [
                'key' => 'proxmox_cloud_init_force_reboot',
                'value' => '1',
                'description' => 'Force reboot VMs after Cloud-Init configuration',
                'type' => 'boolean',
            ],
            [
                'key' => 'proxmox_cloud_init_password_length',
                'value' => '16',
                'description' => 'Length of auto-generated client passwords',
                'type' => 'integer',
            ],
            [
                'key' => 'proxmox_cloud_init_default_dns',
                'value' => '8.8.8.8,8.8.4.4',
                'description' => 'Default DNS servers for client VMs',
                'type' => 'string',
            ],
            
            // WebSocket Settings
            [
                'key' => 'websocket_max_reconnect_attempts',
                'value' => '5',
                'description' => 'Maximum WebSocket reconnection attempts',
                'type' => 'integer',
            ],
            [
                'key' => 'websocket_base_reconnect_delay',
                'value' => '1000',
                'description' => 'Base delay for WebSocket reconnection in milliseconds',
                'type' => 'integer',
            ],
        ];

        foreach ($settings as $setting) {
            // Check if setting already exists
            $exists = DB::table('settings')->where('key', $setting['key'])->exists();
            
            if (!$exists) {
                DB::table('settings')->insert([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the settings we added
        $keys = [
            'proxmox_mgmt_ssh_port',
            'proxmox_admin_ips',
            'proxmox_ssh_isolation_enabled',
            'proxmox_console_domain',
            'proxmox_console_proxy_enabled',
            'proxmox_console_session_timeout',
            'proxmox_client_isolation',
            'proxmox_blocked_internal_networks',
            'proxmox_api_verify_ssl',
            'proxmox_ticket_refresh_interval',
            'proxmox_cloud_init_force_reboot',
            'proxmox_cloud_init_password_length',
            'proxmox_cloud_init_default_dns',
            'websocket_max_reconnect_attempts',
            'websocket_base_reconnect_delay',
        ];

        DB::table('settings')->whereIn('key', $keys)->delete();
    }
};
