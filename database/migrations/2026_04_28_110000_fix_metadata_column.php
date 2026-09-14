<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change metadata from JSON to TEXT to support encryption
        Schema::table('domain_providers', function (Blueprint $table) {
            $table->text('metadata')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('domain_providers', function (Blueprint $table) {
            $table->json('metadata')->nullable()->change();
        });
    }
};
