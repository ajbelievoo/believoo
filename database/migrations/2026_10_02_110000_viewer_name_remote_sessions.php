<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Display name for viewers who join via the guest/public page — guests have
// no member record, so the host's request box shows this label instead of
// "Someone".
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->string('viewer_name', 120)->nullable()->after('viewer_token');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->dropColumn('viewer_name');
        });
    }
};
