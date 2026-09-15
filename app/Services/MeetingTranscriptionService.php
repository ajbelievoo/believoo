<?php

namespace App\Services;

use App\Models\Bconnect\Meeting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MeetingTranscriptionService
{
    /**
     * Transcribe an audio file using OpenAI Whisper and save segments to the meeting.
     */
    public static function transcribeAudio(Meeting $meeting, string $localPath): void
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = $settings['ai_openai_api_key'] ?? $settings['ai_api_key'] ?? '';

        $meeting->update(['transcript_status' => 'processing']);

        if (!$apiKey) {
            Log::warning('OpenAI API key missing; cannot transcribe meeting audio.');
            $meeting->update(['transcript_status' => 'failed']);
            return;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->attach('file', fopen($localPath, 'r'), basename($localPath))
                ->attach('model', 'whisper-1')
                ->attach('response_format', 'verbose_json')
                ->attach('timestamp_granularities[]', 'word')
                ->post('https://api.openai.com/v1/audio/transcriptions');

            if (!$response->successful()) {
                Log::error('Whisper transcription failed: ' . $response->body());
                $meeting->update(['transcript_status' => 'failed']);
                return;
            }

            $data = $response->json();
            $text = $data['text'] ?? '';
            $words = $data['words'] ?? [];

            // Group words into sentence-level segments for readability
            $segments = self::wordsToSegments($words);

            foreach ($segments as $segment) {
                $meeting->transcripts()->create([
                    'speaker' => $segment['speaker'] ?? null,
                    'starts_at' => $segment['start'],
                    'duration' => $segment['end'] - $segment['start'],
                    'text' => $segment['text'],
                    'source' => 'whisper',
                ]);
            }

            if (empty($segments) && !empty($text)) {
                $meeting->transcripts()->create([
                    'starts_at' => 0,
                    'text' => $text,
                    'source' => 'whisper',
                ]);
            }

            $meeting->update(['transcript_status' => 'completed']);
        } catch (\Exception $e) {
            Log::error('Transcription exception: ' . $e->getMessage());
            $meeting->update(['transcript_status' => 'failed']);
        }
    }

    /**
     * Convert Whisper word-level timestamps into sentence-level segments.
     */
    protected static function wordsToSegments(array $words): array
    {
        $segments = [];
        $current = null;
        $maxWordsPerSegment = 25;

        foreach ($words as $word) {
            $text = $word['word'] ?? '';
            $start = $word['start'] ?? 0;
            $end = $word['end'] ?? $start;

            if ($current === null) {
                $current = ['text' => $text, 'start' => $start, 'end' => $end, 'word_count' => 1];
                continue;
            }

            $current['text'] .= ' ' . $text;
            $current['end'] = $end;
            $current['word_count']++;

            if (str_ends_with(rtrim($text), '.') || $current['word_count'] >= $maxWordsPerSegment) {
                $segments[] = $current;
                $current = null;
            }
        }

        if ($current !== null) {
            $segments[] = $current;
        }

        return $segments;
    }

    /**
     * Upload and transcribe an audio blob from the browser.
     */
    public static function handleUpload(Meeting $meeting, $file): array
    {
        $path = $file->store('meeting-audio/' . $meeting->company_id, 'local');
        $meeting->update(['audio_path' => $path, 'transcript_status' => 'pending']);

        $fullPath = Storage::disk('local')->path($path);
        self::transcribeAudio($meeting, $fullPath);

        return [
            'status' => $meeting->transcript_status,
            'segments' => $meeting->transcripts()->count(),
        ];
    }
}
