<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_imports', function (Blueprint $table) {
            // Sync direction: pull (import to BelieVoo) or push (export to external)
            $table->string('sync_direction')->default('pull')->after('user_hosting_id');
            
            // SSH key details for passwordless sync
            $table->text('ssh_private_key')->nullable()->after('source_root_password');
            $table->text('ssh_public_key')->nullable()->after('ssh_private_key');
            $table->boolean('ssh_key_setup_complete')->default(false)->after('ssh_public_key');
            
            // Live log of current file being transferred
            $table->text('sync_log')->nullable()->after('current_file');
            
            // Speed tracking
            $table->string('transfer_speed')->nullable()->after('transferred_bytes');
            $table->integer('eta_seconds')->nullable()->after('transfer_speed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_imports', function (Blueprint $table) {
            $table->dropColumn([
                'sync_direction',
                'ssh_private_key',
                'ssh_public_key',
                'ssh_key_setup_complete',
                'sync_log',
                'transfer_speed',
                'eta_seconds',
            ]);
        });
    }
};
