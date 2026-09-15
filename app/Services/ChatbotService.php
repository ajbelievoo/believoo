<?php

namespace App\Services;

use App\Models\AiMessage;
use App\Models\KnowledgeArticle;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotService
{
    public function answer(string $question, string $sessionId, ?User $user = null, string $source = 'api'): array
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        if (($settings['ai_enabled'] ?? '0') !== '1') {
            return [
                'success' => true,
                'answer' => 'Our AI assistant is currently offline. Please create a support ticket or email us.',
                'citations' => [],
            ];
        }

        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';
        $model = $this->resolveModel($settings['ai_gemini_model'] ?? '');

        if (!$apiKey) {
            return [
                'success' => false,
                'answer' => 'AI is not configured. Please contact support.',
                'citations' => [],
            ];
        }

        $lang = $this->detectLang($question);

        AiMessage::create([
            'session_id' => $sessionId,
            'user_id' => $user?->id,
            'type' => 'user',
            'message' => $question,
            'lang' => $lang,
            'source' => $source,
        ]);

        // Knowledge base context
        $articles = $this->searchKnowledgeBase($question);
        $citations = $articles->pluck('title')->toArray();

        // Conversation history
        $history = $this->conversationHistory($sessionId);

        // Recent support tickets
        $tickets = $user ? $this->recentTickets($user) : [];

        $system = $settings['ai_system_prompt'] ?? 'You are a helpful support assistant for Believoo, a web hosting and cloud services company.';

        $context = $this->buildPrompt($system, $articles, $history, $tickets, $question, $lang);

        try {
            $resp = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey),
                [
                    'contents' => [['parts' => [['text' => $context]]]],
                    'generationConfig' => ['maxOutputTokens' => 800],
                ]
            );

            if ($resp->successful()) {
                $answer = trim($resp->json('candidates.0.content.parts.0.text') ?? '');
                if (empty($answer)) {
                    $answer = $this->fallback($lang);
                }
            } else {
                $answer = $this->fallback($lang);
            }
        } catch (\Exception $e) {
            Log::error('Chatbot Gemini error: ' . $e->getMessage());
            $answer = $this->fallback($lang);
        }

        $aiMsg = AiMessage::create([
            'session_id' => $sessionId,
            'user_id' => $user?->id,
            'type' => 'ai',
            'message' => $answer,
            'citations' => $citations,
            'lang' => $lang,
            'source' => $source,
        ]);

        return [
            'success' => true,
            'answer' => $answer,
            'citations' => $citations,
            'message_id' => $aiMsg->id,
        ];
    }

    protected function buildPrompt(string $system, $articles, $history, $tickets, string $question, string $lang): string
    {
        $parts = [$system];
        $parts[] = "\nAnswer the user's question using the knowledge below. If the knowledge does not cover it, provide a helpful general answer and suggest creating a support ticket.";

        if ($articles->isNotEmpty()) {
            $parts[] = "\n--- Knowledge Base ---";
            foreach ($articles as $article) {
                $parts[] = "Title: " . $article->title . "\n" . strip_tags($article->content);
            }
        }

        if (!empty($tickets)) {
            $parts[] = "\n--- Recent User Tickets ---";
            foreach ($tickets as $ticket) {
                $parts[] = "Ticket: " . $ticket['subject'] . " (" . $ticket['status'] . ")\n" . $ticket['summary'];
            }
        }

        if (!empty($history)) {
            $parts[] = "\n--- Conversation History ---";
            foreach ($history as $msg) {
                $role = $msg['type'] === 'user' ? 'User' : 'Assistant';
                $parts[] = $role . ": " . $msg['message'];
            }
        }

        $parts[] = "\nUser (" . ($lang === 'hi' ? 'Hindi' : 'English') . "): " . $question;
        $parts[] = $lang === 'hi'
            ? "\nAnswer in Hindi. Be concise and friendly."
            : "\nAnswer in English. Be concise and friendly.";

        return implode("\n\n", $parts);
    }

    protected function searchKnowledgeBase(string $question)
    {
        $q = trim(strtolower($question));
        $words = array_filter(explode(' ', $q), fn ($w) => strlen($w) >= 3);

        $query = KnowledgeArticle::query()
            ->where('is_active', true)
            ->limit(10);

        $query->where(function ($sq) use ($q) {
            $sq->whereRaw('LOWER(title) LIKE ?', ["%{$q}%"])
                ->orWhereRaw('LOWER(content) LIKE ?', ["%{$q}%"])
                ->orWhereRaw('LOWER(keywords) LIKE ?', ["%{$q}%"]);
        });

        foreach ($words as $word) {
            $query->orWhere(function ($sq) use ($word) {
                $sq->whereRaw('LOWER(title) LIKE ?', ["%{$word}%"])
                    ->orWhereRaw('LOWER(content) LIKE ?', ["%{$word}%"])
                    ->orWhereRaw('LOWER(keywords) LIKE ?', ["%{$word}%"]);
            });
        }

        return $query->get()->sortByDesc(function ($article) use ($q, $words) {
            $score = 0;
            $haystack = strtolower($article->title . ' ' . strip_tags($article->content) . ' ' . $article->keywords);
            if (str_contains($haystack, $q)) {
                $score += 10;
            }
            foreach ($words as $word) {
                if (str_contains($haystack, $word)) {
                    $score += 1;
                }
            }
            return $score;
        })->take(3);
    }

    protected function conversationHistory(string $sessionId, int $limit = 6): array
    {
        return AiMessage::where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['type' => $m->type, 'message' => $m->message])
            ->toArray();
    }

    protected function recentTickets(User $user): array
    {
        return Ticket::where('user_id', $user->id)
            ->orWhere('email', $user->email)
            ->latest()
            ->limit(3)
            ->get()
            ->map(function ($ticket) {
                return [
                    'subject' => $ticket->subject,
                    'status' => $ticket->status,
                    'summary' => Str::limit($ticket->messages()->latest()->value('message') ?? 'No messages', 200),
                ];
            })
            ->toArray();
    }

    protected function detectLang(string $text): string
    {
        return preg_match('/[\x{0900}-\x{097F}]/u', $text) ? 'hi' : 'en';
    }

    protected function fallback(string $lang): string
    {
        return $lang === 'hi'
            ? 'Maaf kijiye, mujhe iska jawab nahi mila. Kripya support ticket banayein.'
            : 'Sorry, I could not find an answer. Please create a support ticket and our team will assist you.';
    }

    protected function resolveModel(string $model): string
    {
        $valid = ['gemini-1.5-flash', 'gemini-1.5-flash-latest', 'gemini-1.5-pro', 'gemini-1.5-pro-latest', 'gemini-2.0-flash', 'gemini-2.0-flash-lite', 'gemini-2.0-pro-exp', 'gemini-2.5-flash', 'gemini-2.5-flash-preview-05-20', 'gemini-2.5-pro', 'gemini-2.5-pro-preview-05-06'];

        if (empty($model) || !in_array($model, $valid)) {
            return 'gemini-1.5-flash-latest';
        }

        return $model;
    }
}
