<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-device agent settings keyed by the persistent device_id — survives
// session-code regeneration. Currently holds the unattended-access PIN
// (bcrypt) that lets a trusted viewer connect without host approval.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('bconnect_agent_devices', function (Blueprint $t) {
            $t->id();
            $t->string('device_id', 64)->unique();
            $t->string('pin_hash', 255)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_agent_devices');
    }
};
