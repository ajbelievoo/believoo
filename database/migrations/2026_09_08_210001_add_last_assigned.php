<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('support_agents', function (Blueprint $t) {
            $t->timestamp('last_assigned_at')->nullable()->after('last_seen_at');
        });
    }
    public function down(): void {
        Schema::table('support_agents', function (Blueprint $t) {
            $t->dropColumn('last_assigned_at');
        });
    }
};
