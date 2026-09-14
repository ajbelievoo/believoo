<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bconnect_files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('member_id')->constrained('bconnect_members');
            $t->foreignId('project_id')->nullable()->constrained('bconnect_projects');
            $t->string('name');
            $t->string('path');
            $t->string('mime')->nullable();
            $t->unsignedBigInteger('size')->default(0);
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bconnect_files'); }
};
