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
        Schema::table('messages', function (Blueprint $blueprint) {
            $blueprint->foreignId('admin_id')->nullable()->constrained('users')->after('type');
            $blueprint->string('phone_number')->nullable()->after('sender_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $blueprint) {
            $blueprint->dropForeign(['admin_id']);
            $blueprint->dropColumn(['admin_id', 'phone_number']);
        });
    }
};
