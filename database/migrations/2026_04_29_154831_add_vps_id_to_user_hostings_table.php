<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->unsignedInteger('vps_id')->nullable()->after('admin_notes')
                  ->comment('Proxmox/Virtualizor VM ID for power actions');
            $table->string('virtualizor_vps_id')->nullable()->after('vps_id')
                  ->comment('Virtualizor VPS ID if using Virtualizor');
        });
    }

    public function down(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->dropColumn(['vps_id', 'virtualizor_vps_id']);
        });
    }
};
