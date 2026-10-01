<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $table) {
            $table->string('viewer_token', 64)->nullable()->after('agent_token');
            $table->timestamp('viewer_joined_at')->nullable()->after('viewer_token');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $table) {
            $table->dropColumn(['viewer_token', 'viewer_joined_at']);
        });
    }
};
