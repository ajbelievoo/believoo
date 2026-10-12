<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $table) {
            $table->unsignedTinyInteger('guest_rating')->nullable()->after('ended_at');
            $table->string('guest_feedback', 500)->nullable()->after('guest_rating');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_remote_sessions', function (Blueprint $table) {
            $table->dropColumn(['guest_rating', 'guest_feedback']);
        });
    }
};
