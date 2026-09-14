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
        Schema::table('proxmox_vms', function (Blueprint $table) {
            // Hostname & DNS
            $table->string('hostname')->nullable()->after('mac_address');
            $table->json('nameservers')->nullable()->after('hostname'); // ['ns1.believoo.com', 'ns2.believoo.com']
            
            // Panel Installation Status
            $table->string('panel_status')->default('pending')->after('nameservers'); // pending, installing, installed, failed
            $table->timestamp('panel_installed_at')->nullable()->after('panel_status');
            
            // Panel Access Info
            $table->string('panel_login_url')->nullable()->after('panel_installed_at');
            $table->string('panel_username')->nullable()->after('panel_login_url');
            $table->string('panel_password')->nullable()->after('panel_username');
            $table->string('panel_port')->nullable()->after('panel_password');
            
            // Cloud-init tracking
            $table->text('cloud_init_script')->nullable()->after('panel_port');
            $table->string('cloud_init_status')->nullable()->after('cloud_init_script'); // pending, running, completed, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxmox_vms', function (Blueprint $table) {
            $table->dropColumn([
                'hostname',
                'nameservers',
                'panel_status',
                'panel_installed_at',
                'panel_login_url',
                'panel_username',
                'panel_password',
                'panel_port',
                'cloud_init_script',
                'cloud_init_status',
            ]);
        });
    }
};
