<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->boolean('ab_enabled')->default(false)->after('throttle_per_minute');
            $table->unsignedTinyInteger('ab_test_percentage')->default(30)->after('ab_enabled');
            $table->unsignedTinyInteger('ab_split')->default(50)->after('ab_test_percentage');
            $table->string('ab_metric', 20)->default('opens')->after('ab_split');
            $table->unsignedInteger('ab_duration_minutes')->default(60)->after('ab_metric');
            $table->string('ab_status', 20)->default('draft')->after('ab_duration_minutes');
            $table->string('ab_winner', 10)->nullable()->after('ab_status');
            $table->timestamp('ab_winner_sent_at')->nullable()->after('ab_winner');
            $table->timestamp('ab_test_started_at')->nullable()->after('ab_winner_sent_at');
            $table->string('title_b')->nullable()->after('ab_test_started_at');
            $table->text('message_b')->nullable()->after('title_b');
            $table->text('message_hi_b')->nullable()->after('message_b');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'ab_enabled',
                'ab_test_percentage',
                'ab_split',
                'ab_metric',
                'ab_duration_minutes',
                'ab_status',
                'ab_winner',
                'ab_winner_sent_at',
                'ab_test_started_at',
                'title_b',
                'message_b',
                'message_hi_b',
            ]);
        });
    }
};
