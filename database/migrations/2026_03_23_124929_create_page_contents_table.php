<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('page_name'); // e.g., 'home', 'about', 'services'
            $table->string('section_name'); // e.g., 'hero', 'why_choose_us'
            $table->string('key')->unique(); // e.g., 'hero_title', 'bento_1_text'
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, image, json
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
