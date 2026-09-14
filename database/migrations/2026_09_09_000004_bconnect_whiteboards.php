<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bconnect_whiteboards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained('bconnect_companies');
            $t->foreignId('project_id')->constrained('bconnect_projects');
            $t->longText('data'); // JSON strokes
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('bconnect_whiteboards'); }
};
