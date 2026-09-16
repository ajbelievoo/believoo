<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_meetings', function (Blueprint $table) {
            $table->json('recording_file_list')->nullable()->after('recording_status');
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_meetings', function (Blueprint $table) {
            $table->dropColumn('recording_file_list');
        });
    }
};
