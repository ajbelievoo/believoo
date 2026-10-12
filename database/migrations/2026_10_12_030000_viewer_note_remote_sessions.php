<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Optional "purpose" line a viewer types when joining — the host sees
// "Rahul — fixing printer" instead of a bare name, so they know who and why
// before pressing Accept.
return new class extends Migration {
    public function up(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->string('viewer_note', 140)->nullable()->after('viewer_name');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->dropColumn('viewer_note');
        });
    }
};
