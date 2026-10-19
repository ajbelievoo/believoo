<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// App-experience ratings — the "How was the app?" popup shown when an agent
// quits. Distinct from bconnect_remote_sessions.guest_rating, which is the
// per-session CSAT a viewer leaves after a remote session ends.
return new class extends Migration {
    public function up(): void
    {
        Schema::create('bmydesk_app_ratings', function (Blueprint $t) {
            $t->id();
            $t->string('device_id', 64)->nullable()->index();
            $t->string('session_code', 16)->index();
            $t->string('platform', 20)->nullable();   // android | win32 | darwin | linux
            $t->string('version', 20)->nullable();
            $t->unsignedTinyInteger('rating');
            $t->string('feedback', 500)->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bmydesk_app_ratings');
    }
};
