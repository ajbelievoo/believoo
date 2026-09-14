<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bconnect_meetings', function (Blueprint $t) {
            $t->string('recording_sid')->nullable();
            $t->string('recording_resource')->nullable();
            $t->string('recording_status')->nullable();
        });
    }
    public function down(): void {
        Schema::table('bconnect_meetings', function (Blueprint $t) {
            $t->dropColumn(['recording_sid','recording_resource','recording_status']);
        });
    }
};
