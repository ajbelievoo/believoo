<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bconnect_chat_channels', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('created_by');
            $t->string('name', 80);
            $t->boolean('is_private')->default(false);
            $t->json('member_ids')->nullable(); // private rooms: allowed member ids; null/empty = whole company
            $t->timestamps();
        });
        Schema::table('bconnect_messages', function (Blueprint $t) {
            if (!Schema::hasColumn('bconnect_messages', 'edited_at')) $t->timestamp('edited_at')->nullable()->after('read_by');
            if (!Schema::hasColumn('bconnect_messages', 'deleted_at')) $t->timestamp('deleted_at')->nullable()->after('edited_at');
        });
    }
    public function down(): void {
        Schema::dropIfExists('bconnect_chat_channels');
        Schema::table('bconnect_messages', function (Blueprint $t) {
            $t->dropColumn(['edited_at', 'deleted_at']);
        });
    }
};
