<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bconnect_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('bconnect_meetings', 'audio_path')) {
                $table->string('audio_path')->nullable()->after('recording_status');
            }
            if (!Schema::hasColumn('bconnect_meetings', 'transcript_status')) {
                $table->string('transcript_status')->default('pending')->after('audio_path');
            }
            if (!Schema::hasColumn('bconnect_meetings', 'notes_status')) {
                $table->string('notes_status')->default('pending')->after('transcript_status');
            }
        });

        Schema::create('bconnect_meeting_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('bconnect_meetings')->cascadeOnDelete();
            $table->string('speaker')->nullable();
            $table->decimal('starts_at', 10, 3)->default(0); // seconds from meeting start
            $table->decimal('duration', 10, 3)->nullable();
            $table->text('text');
            $table->string('source')->default('browser'); // browser, whisper, manual
            $table->timestamps();

            $table->index(['meeting_id', 'starts_at']);
        });

        Schema::create('bconnect_meeting_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('bconnect_meetings')->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->json('key_points')->nullable();
            $table->json('action_items')->nullable();
            $table->json('decisions')->nullable();
            $table->string('ai_model')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bconnect_meeting_notes');
        Schema::dropIfExists('bconnect_meeting_transcripts');

        Schema::table('bconnect_meetings', function (Blueprint $table) {
            $table->dropColumn(['audio_path', 'transcript_status', 'notes_status']);
        });
    }
};
