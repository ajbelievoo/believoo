<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_agents', 'canned_replies')) {
            Schema::table('support_agents', function (Blueprint $table) {
                $table->json('canned_replies')->nullable()->after('max_chats');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_agents', 'canned_replies')) {
            Schema::table('support_agents', function (Blueprint $table) {
                $table->dropColumn('canned_replies');
            });
        }
    }
};
