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
        Schema::table('streaming_subscriptions', function (Blueprint $table) {
            // Beauty Filter Settings
            $table->boolean('beauty_enabled')->default(false)->after('status');
            $table->decimal('beauty_skin_smoothing', 3, 2)->default(0.50)->after('beauty_enabled');
            $table->decimal('beauty_face_slimming', 3, 2)->default(0.30)->after('beauty_skin_smoothing');
            $table->decimal('beauty_eye_enlargement', 3, 2)->default(0.20)->after('beauty_face_slimming');
            $table->decimal('beauty_brightness', 3, 2)->default(0.10)->after('beauty_eye_enlargement');
            $table->string('beauty_preset', 20)->default('NATURAL')->after('beauty_brightness');
            
            // Index for performance
            $table->index(['beauty_enabled'], 'idx_streaming_beauty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('streaming_subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_streaming_beauty');
            $table->dropColumn([
                'beauty_enabled',
                'beauty_skin_smoothing',
                'beauty_face_slimming',
                'beauty_eye_enlargement',
                'beauty_brightness',
                'beauty_preset',
            ]);
        });
    }
};
