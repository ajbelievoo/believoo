<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        // Email-threaded replies have no portal member — allow null so the
        // importer can store them instead of dropping the FK-less comment.
        DB::statement('ALTER TABLE bconnect_ticket_comments MODIFY member_id BIGINT UNSIGNED NULL');
    }
    public function down(): void {
        DB::statement('ALTER TABLE bconnect_ticket_comments MODIFY member_id BIGINT UNSIGNED NOT NULL');
    }
};
