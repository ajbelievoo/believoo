<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('streaming_api_keys', function (Blueprint $table) {
            $table->foreignId('hosting_id')->nullable()->after('order_id')->constrained('user_hostings')->onDelete('set null');
            $table->index('hosting_id');
        });
    }

    public function down(): void
    {
        Schema::table('streaming_api_keys', function (Blueprint $table) {
            $table->dropForeign(['hosting_id']);
            $table->dropColumn('hosting_id');
        });
    }
};
