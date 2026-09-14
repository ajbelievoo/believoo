<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bconnect_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('member_id')->constrained('bconnect_members');
            $t->morphs('channel'); // project or ticket
            $t->text('message');
            $t->json('attachments')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bconnect_messages'); }
};
