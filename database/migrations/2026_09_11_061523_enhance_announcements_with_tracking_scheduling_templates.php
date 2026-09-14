<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->unsignedBigInteger('template_id')->nullable()->after('id');
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            $table->string('attachment')->nullable()->after('message');
            $table->text('message_hi')->nullable()->after('message');
            $table->string('locale', 10)->default('en')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn([
                'template_id',
                'scheduled_at',
                'attachment',
                'message_hi',
                'locale',
            ]);
        });
    }
};
