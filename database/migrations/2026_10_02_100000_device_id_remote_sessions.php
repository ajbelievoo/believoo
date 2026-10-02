<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Persistent device identity — lets an agent keep ONE stable session code
// (like an AnyDesk ID). The device generates a UUID once, sends it on
// register, and the server reuses/revives its session row.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->string('device_id', 64)->nullable()->index()->after('host_kind');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->dropIndex(['device_id']);
            $t->dropColumn('device_id');
        });
    }
};
