<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->string('client_signature_ip')->nullable()->after('client_signature_data');
            $table->text('client_signature_user_agent')->nullable()->after('client_signature_ip');
            $table->string('client_signature_certificate_id')->nullable()->after('client_signature_user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('agreements', function (Blueprint $table) {
            $table->dropColumn(['client_signature_ip', 'client_signature_user_agent', 'client_signature_certificate_id']);
        });
    }
};
