<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('chat_assignments', function (Blueprint $table) {
            $table->text('summary')->nullable()->after('client_feedback');
        });
    }
    public function down(): void {
        Schema::table('chat_assignments', function (Blueprint $table) {
            $table->dropColumn('summary');
        });
    }
};
