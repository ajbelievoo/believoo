<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('send_sms')->default(false)->after('is_published');
            $table->boolean('send_push')->default(false)->after('send_sms');
            $table->string('segment', 50)->nullable()->after('audience');
            $table->string('ab_test_name', 50)->nullable()->after('segment');
            $table->string('variant', 10)->nullable()->after('ab_test_name');
            $table->unsignedInteger('throttle_per_minute')->default(60)->after('variant');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 10)->nullable()->after('email');
        });

        Schema::table('email_preferences', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('sms')->default(true)->after('announcements');
            $table->boolean('push')->default(true)->after('sms');
            $table->string('locale', 10)->nullable()->after('push');
        });

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['send_sms', 'send_push', 'segment', 'ab_test_name', 'variant', 'throttle_per_minute']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
        Schema::table('email_preferences', function (Blueprint $table) {
            $table->dropColumn(['phone', 'sms', 'push', 'locale']);
        });
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
