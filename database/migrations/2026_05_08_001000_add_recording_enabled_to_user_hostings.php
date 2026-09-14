<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->boolean('recording_enabled')->default(false)->after('has_streaming_addon');
        });
    }

    public function down(): void
    {
        Schema::table('user_hostings', function (Blueprint $table) {
            $table->dropColumn('recording_enabled');
        });
    }
};
