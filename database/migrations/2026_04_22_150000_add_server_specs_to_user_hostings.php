<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            // Server Specifications
            $table->integer('cpu_cores')->nullable()->after('hosting_type');
            $table->string('ram_size')->nullable()->after('cpu_cores'); // e.g., "12 GB"
            $table->string('storage_size')->nullable()->after('ram_size'); // e.g., "100 GB NVMe"
            $table->string('storage_used')->nullable()->after('storage_size'); // e.g., "45 GB"
            $table->string('bandwidth_used')->nullable()->after('bandwidth'); // e.g., "250 GB"
            
            // OS & Location
            $table->string('os_name')->nullable()->after('ssl_expiry'); // e.g., "Ubuntu 22.04 LTS"
            $table->string('datacenter_location')->nullable()->after('os_name'); // e.g., "Singapore (SGP)"
            $table->string('server_hostname')->nullable()->after('datacenter_location');
            
            // Additional Network Info
            $table->string('ipv6')->nullable()->after('server_ip');
            $table->string('gateway')->nullable()->after('ipv6');
            $table->string('root_password')->nullable()->after('control_panel_password');
            
            // Boot & Backup
            $table->string('boot_mode')->nullable()->default('LOCAL'); // LOCAL, RESCUE
            $table->boolean('automated_backup')->nullable()->default(false);
            $table->string('backup_status')->nullable()->default('Disabled');
            
            // Monitoring
            $table->decimal('uptime_percentage', 5, 2)->nullable()->default(99.99);
            $table->timestamp('last_reboot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->dropColumn([
                'cpu_cores',
                'ram_size',
                'storage_size',
                'storage_used',
                'bandwidth_used',
                'os_name',
                'datacenter_location',
                'server_hostname',
                'ipv6',
                'gateway',
                'root_password',
                'boot_mode',
                'automated_backup',
                'backup_status',
                'uptime_percentage',
                'last_reboot',
            ]);
        });
    }
};
