<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Trusted-device pairs let a host mark "always allow this viewer" at accept
// time — future joins from that device skip the approval prompt (audit log
// still records the session, and trust can be revoked via /untrust).
// viewer_ip + viewer_device give the audit trail a real identity per join.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('bconnect_agent_trust', function (Blueprint $t) {
            $t->id();
            $t->string('host_device_id', 64)->index();
            $t->string('viewer_device_id', 64)->index();
            $t->string('viewer_name', 80)->nullable();
            $t->timestamps();
            $t->unique(['host_device_id', 'viewer_device_id']);
        });
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->string('viewer_ip', 45)->nullable()->after('viewer_name');
            $t->string('viewer_device', 64)->nullable()->after('viewer_ip');
        });
        Schema::table('bconnect_agent_devices', function (Blueprint $t) {
            $t->timestamp('pin_expires_at')->nullable()->after('pin_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_agent_trust');
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->dropColumn(['viewer_ip', 'viewer_device']);
        });
        Schema::table('bconnect_agent_devices', function (Blueprint $t) {
            $t->dropColumn('pin_expires_at');
        });
    }
};
