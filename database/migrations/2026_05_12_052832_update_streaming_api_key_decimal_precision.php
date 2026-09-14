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
        Schema::table('streaming_api_keys', function (Blueprint $table) {
            $table->decimal('bandwidth_used_gb', 10, 4)->change();
            $table->decimal('storage_used_gb', 10, 4)->change();
        });
    }

    public function down(): void
    {
        Schema::table('streaming_api_keys', function (Blueprint $table) {
            $table->decimal('bandwidth_used_gb', 10, 2)->change();
            $table->decimal('storage_used_gb', 10, 2)->change();
        });
    }
};
