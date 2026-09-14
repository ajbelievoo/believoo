<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'auto_renew')) {
                $table->boolean('auto_renew')->default(true)->after('billing_status');
            }
        });

        if (Schema::hasTable('user_hostings')) {
            Schema::table('user_hostings', function (Blueprint $table) {
                if (!Schema::hasColumn('user_hostings', 'auto_renew')) {
                    $table->boolean('auto_renew')->default(true)->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'auto_renew')) {
                $table->dropColumn('auto_renew');
            }
        });

        if (Schema::hasTable('user_hostings')) {
            Schema::table('user_hostings', function (Blueprint $table) {
                if (Schema::hasColumn('user_hostings', 'auto_renew')) {
                    $table->dropColumn('auto_renew');
                }
            });
        }
    }
};
