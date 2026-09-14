<?php

namespace App\Http\Controllers;

use App\Models\AiMessage;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\CallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IntegrationWebhookController extends Controller
{
    // ── Telegram Bot Webhook ─────────────────────────────────────────
    // POST /webhook/telegram
    // Set webhook: https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://believoo.com/webhook/telegram
    public function telegram(Request $request)
    {
        $update = $request->all();
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (!$message) return response()->json(['ok' => true]);

        $chatId = $message['chat']['id'] ?? null;
        $text = trim($message['text'] ?? '');
        $from = $message['from'] ?? [];
        $senderName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: 'Telegram User';

        if (!$chatId || !$text) return response()->json(['ok' => true]);

        // Store in ai_messages so it appears in the chat widget session
        $sessionId = 'tg-' . $chatId;
        AiMessage::create([
            'session_id' => $sessionId,
            'type' => 'user',
            'message' => $text,
            'lang' => 'en',
        ]);

        // Get AI reply — reuse the same logic as web chat
        $hub = new \App\Livewire\SupportHub();
        $hub->sessionId = $sessionId;
        $reply = $this->getAiReply($text, $sessionId, 'en');

        // Save AI reply
        AiMessage::create([
            'session_id' => $sessionId,
            'type' => 'ai',
            'message' => $reply,
            'lang' => 'en',
        ]);

        // Send back to Telegram
        $token = config('services.telegram.bot_token') ?? \App\Models\Setting::where('key', 'telegram_bot_token')->value('value');
        if ($token) {
            try {
                Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $reply,
                    'parse_mode' => 'HTML',
                ]);
            } catch (\Exception $e) {
                Log::error('Telegram send failed: ' . $e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }

    // ── Facebook / Instagram DM Webhook ──────────────────────────────
    // GET  /webhook/meta  — verification (hub.challenge)
    // POST /webhook/meta  — incoming messages
    public function metaVerify(Request $request)
    {
        $verifyToken = config('services.meta.verify_token') ?? \App\Models\Setting::where('key', 'meta_verify_token')->value('value');
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $verifyToken) {
            return response($request->get('hub_challenge'), 200);
        }
        return response('Forbidden', 403);
    }

    public function metaMessage(Request $request)
    {
        $entries = $request->input('entry', []);
        foreach ($entries as $entry) {
            foreach ($entry['messaging'] ?? [] as $event) {
                $senderId = $event['sender']['id'] ?? null;
                $text = trim($event['message']['text'] ?? '');
                if (!$senderId || !$text) continue;

                $sessionId = 'fb-' . $senderId;
                AiMessage::create(['session_id' => $sessionId, 'type' => 'user', 'message' => $text, 'lang' => 'en']);

                $reply = $this->getAiReply($text, $sessionId, 'en');
                AiMessage::create(['session_id' => $sessionId, 'type' => 'ai', 'message' => $reply, 'lang' => 'en']);

                // Send reply via Meta API
                $token = config('services.meta.page_access_token') ?? \App\Models\Setting::where('key', 'meta_page_access_token')->value('value');
                if ($token) {
                    try {
                        Http::post("https://graph.facebook.com/v18.0/me/messages?access_token={$token}", [
                            'recipient' => ['id' => $senderId],
                            'message' => ['text' => $reply],
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Meta send failed: ' . $e->getMessage());
                    }
                }
            }
        }
        return response('EVENT_RECEIVED', 200);
    }

    // ── WhatsApp Business API (Meta Cloud API) ─────────────────────────
    // GET  /webhook/whatsapp — verification
    // POST /webhook/whatsapp — incoming messages
    public function whatsappVerify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token') ?? \App\Models\Setting::where('key', 'whatsapp_verify_token')->value('value') ?? 'believoo-wa-verify';
        if ($request->get('hub_mode') === 'subscribe' && $request->get('hub_verify_token') === $verifyToken) {
            return response($request->get('hub_challenge'), 200);
        }
        return response('Forbidden', 403);
    }

    public function whatsappMessage(Request $request)
    {
        $entry = $request->input('entry.0.changes.0.value', []);
        $messages = $entry['messages'] ?? [];

        foreach ($messages as $msg) {
            $from = $msg['from'] ?? null;
            $text = trim($msg['text']['body'] ?? '');
            if (!$from || !$text) continue;

            $sessionId = 'wa-' . $from;
            AiMessage::create(['session_id' => $sessionId, 'type' => 'user', 'message' => $text, 'lang' => 'en']);
            $reply = $this->getAiReply($text, $sessionId, 'en');
            AiMessage::create(['session_id' => $sessionId, 'type' => 'ai', 'message' => $reply, 'lang' => 'en']);

            // Send reply via WhatsApp API
            \App\Services\NotifyService::whatsapp($from, $reply);
        }

        return response()->json(['ok' => true]);
    }

    // ── Email-to-chat (inbound email → AI reply) ─────────────────────
    // POST /webhook/inbound-email
    // Works with Mailgun, SendGrid Inbound Parse, or any email-to-webhook service
    public function inboundEmail(Request $request)
    {
        $from = $request->input('from') ?? $request->input('sender') ?? '';
        $subject = trim($request->input('subject') ?? '');
        $body = trim($request->input('body-plain') ?? $request->input('text') ?? $request->input('body') ?? '');

        // Extract email
        preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $from, $m);
        $email = $m[0] ?? null;
        if (!$email || !$body) return response()->json(['ok' => true]);

        $sessionId = 'email-' . md5($email);
        $text = trim($subject . ' ' . $body);

        AiMessage::create(['session_id' => $sessionId, 'type' => 'user', 'message' => $text, 'lang' => 'en']);
        $reply = $this->getAiReply($text, $sessionId, 'en');
        AiMessage::create(['session_id' => $sessionId, 'type' => 'ai', 'message' => $reply, 'lang' => 'en']);

        // Reply via email
        try {
            \Illuminate\Support\Facades\Mail::raw($reply, function ($m) use ($email, $subject) {
                $m->to($email)->subject('Re: ' . ($subject ?: 'Your inquiry'));
            });
        } catch (\Exception $e) {
            Log::error('Inbound email reply failed: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ── Auto-ticket from email ────────────────────────────────────────
    // POST /webhook/email-ticket
    public function emailToTicket(Request $request)
    {
        $from = $request->input('from') ?? $request->input('sender') ?? '';
        $subject = trim($request->input('subject') ?? 'Email Support Request');
        $body = trim($request->input('body-plain') ?? $request->input('text') ?? $request->input('body') ?? '');

        preg_match('/[\w.+-]+@[\w-]+\.[\w.]+/', $from, $m);
        $email = $m[0] ?? null;
        $name = trim(preg_replace('/<.*>/', '', $from)) ?: 'Email User';

        if (!$email || !$body) return response()->json(['ok' => true]);

        $ticket = Ticket::create([
            'ticket_id' => 'TK-' . strtoupper(uniqid()),
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $body,
            'status' => 'open',
            'priority' => 'medium',
        ]);

        return response()->json(['ok' => true, 'ticket_id' => $ticket->ticket_id]);
    }

    // ── Shared AI reply (no Livewire, no session) ─────────────────────
    private function getAiReply($text, $sessionId, $lang)
    {
        // Try knowledge base first
        $kb = $this->searchKb($text);
        if ($kb) return $kb->content;

        // Try AI API
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';
        if ($apiKey && ($settings['ai_enabled'] ?? '0') == '1') {
            try {
                $model = $settings['ai_gemini_model'] ?? 'gemini-2.5-flash-lite';
                $resp = Http::timeout(20)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    ['contents' => [['parts' => [['text' => "You are Believoo support AI. Reply helpfully, concisely. Question: {$text}"]]]],
                    'generationConfig' => ['maxOutputTokens' => 800]]
                );
                if ($resp->successful()) {
                    $r = $resp->json('candidates.0.content.parts.0.text');
                    if ($r) return $r;
                }
            } catch (\Exception $e) {
                Log::error('Webhook AI failed: ' . $e->getMessage());
            }
        }

        return "Thanks for reaching out! I've received your message and our team will get back to you shortly. You can also visit https://believoo.com for instant chat support.";
    }

    private function searchKb($text)
    {
        $q = strtolower($text);
        if (strlen($q) < 3) return null;
        $articles = \App\Models\KnowledgeArticle::where('is_active', true)->get();
        $best = null; $score = 0;
        foreach ($articles as $a) {
            $s = 0;
            $haystack = strtolower($a->title . ' ' . ($a->keywords ?? '') . ' ' . $a->content);
            foreach (preg_split('/\s+/', $q) as $w) {
                if (strlen($w) >= 3 && str_contains($haystack, $w)) $s++;
            }
            if ($s > $score) { $score = $s; $best = $a; }
        }
        return $score >= 2 ? $best : null;
    }
}
