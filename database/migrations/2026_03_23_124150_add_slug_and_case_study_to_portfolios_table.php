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
        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('slug')->unique()->after('title')->nullable();
            $table->text('problem')->nullable()->after('description');
            $table->text('solution')->nullable()->after('problem');
            $table->text('result')->nullable()->after('solution');
            $table->json('tech_stack')->nullable()->after('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn(['slug', 'problem', 'solution', 'result', 'tech_stack']);
        });
    }
};
