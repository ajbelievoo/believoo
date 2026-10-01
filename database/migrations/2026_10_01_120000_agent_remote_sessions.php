<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->foreignId('company_id')->nullable()->change();
            $t->foreignId('requested_by')->nullable()->change();
            $t->foreignId('viewer_member_id')->nullable()->after('target_id')->constrained('bconnect_members')->nullOnDelete();
            $t->string('host_kind')->default('member')->after('session_code'); // member | agent
            $t->string('host_label')->nullable()->after('host_kind');
            $t->string('agent_token', 64)->nullable()->after('host_label');
            $t->timestamp('expires_at')->nullable()->after('ended_at');
        });
    }

    public function down(): void {
        Schema::table('bconnect_remote_sessions', function (Blueprint $t) {
            $t->dropConstrainedForeignId('viewer_member_id');
            $t->dropColumn(['host_kind', 'host_label', 'agent_token', 'expires_at']);
        });
    }
};
