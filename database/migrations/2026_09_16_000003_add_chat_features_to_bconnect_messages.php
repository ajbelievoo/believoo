<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('bconnect_messages', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('channel_id')->constrained('bconnect_messages')->nullOnDelete();
            }
            if (!Schema::hasColumn('bconnect_messages', 'mentions')) {
                $table->json('mentions')->nullable()->after('message');
            }
            if (!Schema::hasColumn('bconnect_messages', 'read_by')) {
                $table->json('read_by')->nullable()->after('mentions');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bconnect_messages', function (Blueprint $table) {
            $table->dropColumn(['parent_id', 'mentions', 'read_by']);
        });
    }
};
