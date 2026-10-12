<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bconnect_meetings', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_meetings', 'max_participants')) {
                $t->unsignedInteger('max_participants')->nullable()->after('waiting_room');
            }
        });
    }
    public function down(): void {
        Schema::table('bconnect_meetings', function (Blueprint $t) {
            $t->dropColumn('max_participants');
        });
    }
};
