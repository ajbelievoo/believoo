<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('chat_assignments', function (Blueprint $t) {
            $t->integer('sla_target_seconds')->nullable()->after('summary');
            $t->boolean('sla_breached')->default(false)->after('sla_target_seconds');
        });
    }
    public function down(): void {
        Schema::table('chat_assignments', function (Blueprint $t) {
            $t->dropColumn(['sla_target_seconds', 'sla_breached']);
        });
    }
};
