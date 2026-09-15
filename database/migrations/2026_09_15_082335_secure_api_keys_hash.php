<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            // Move from storing plaintext keys to hashed keys.
            if (Schema::hasColumn('api_keys', 'key')) {
                $table->dropColumn('key');
            }
            if (Schema::hasColumn('api_keys', 'secret')) {
                $table->dropColumn('secret');
            }
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key_hash', 64)->unique()->after('name');
            $table->string('key_prefix', 16)->nullable()->after('key_hash');
            $table->json('scopes')->nullable()->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropColumn(['key_hash', 'key_prefix', 'scopes']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->string('key', 64)->unique()->after('name');
            $table->string('secret', 64)->nullable()->after('key');
        });
    }
};
