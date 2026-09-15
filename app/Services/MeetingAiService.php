<?php

namespace App\Services;

use App\Models\Bconnect\MeetingNote;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeetingAiService
{
    public static function generateNotes(MeetingNote $note): void
    {
        $meeting = $note->meeting;
        if (!$meeting) return;

        $transcriptText = $meeting->transcripts()
            ->orderBy('starts_at')
            ->get()
            ->map(fn ($t) => ($t->speaker ? "{$t->speaker}: " : '') . $t->text)
            ->implode("\n");

        if (empty(trim($transcriptText))) {
            $note->update([
                'summary' => 'No transcript available to summarize.',
                'key_points' => [],
                'action_items' => [],
                'decisions' => [],
            ]);
            $meeting->update(['notes_status' => 'completed']);
            return;
        }

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';
        $model = $settings['ai_gemini_model'] ?? 'gemini-1.5-flash';
        $note->ai_model = "gemini:{$model}";

        if (!$apiKey) {
            $note->update([
                'summary' => 'AI API key not configured. Notes cannot be generated.',
                'key_points' => [],
                'action_items' => [],
                'decisions' => [],
            ]);
            $meeting->update(['notes_status' => 'failed']);
            return;
        }

        $prompt = <<<PROMPT
You are a meeting assistant. Analyze the following transcript and return a strict JSON object with these keys:
- "summary": a concise paragraph (max 200 words)
- "key_points": an array of 3-5 bullet strings
- "action_items": an array of strings in the format "Name or Role - Task" if a speaker is identifiable, otherwise "Task"
- "decisions": an array of strings describing decisions made

Transcript:
{$transcriptText}
PROMPT;

        try {
            $resp = Http::timeout(60)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => 1500, 'responseMimeType' => 'application/json'],
                ]
            );

            if ($resp->successful()) {
                $text = trim($resp->json('candidates.0.content.parts.0.text') ?? '');
                $json = self::extractJson($text);
                $note->update([
                    'summary' => $json['summary'] ?? $text,
                    'key_points' => is_array($json['key_points'] ?? null) ? $json['key_points'] : [],
                    'action_items' => is_array($json['action_items'] ?? null) ? $json['action_items'] : [],
                    'decisions' => is_array($json['decisions'] ?? null) ? $json['decisions'] : [],
                ]);
                $meeting->update(['notes_status' => 'completed']);
            } else {
                Log::error('Meeting AI notes failed: ' . $resp->body());
                $meeting->update(['notes_status' => 'failed']);
            }
        } catch (\Exception $e) {
            Log::error('Meeting AI notes exception: ' . $e->getMessage());
            $meeting->update(['notes_status' => 'failed']);
        }
    }

    protected static function extractJson(string $text): array
    {
        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Fallback: try to find a JSON object
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }
}
