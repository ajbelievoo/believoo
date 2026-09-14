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
            $table->string('control_panel')->nullable()->after('mac_address');
            $table->string('bandwidth')->nullable()->after('control_panel'); // e.g., "1.5 Gbps"
            $table->string('plan_name')->nullable()->after('bandwidth'); // e.g., "VPS-4"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proxmox_vms', function (Blueprint $table) {
            $table->dropColumn(['control_panel', 'bandwidth', 'plan_name']);
        });
    }
};
