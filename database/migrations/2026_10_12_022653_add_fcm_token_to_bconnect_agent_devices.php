<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_agent_devices', function (Blueprint $table) {
            $table->string('fcm_token', 300)->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_agent_devices', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};
