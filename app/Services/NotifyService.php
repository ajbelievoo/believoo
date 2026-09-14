<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyService
{
    private static function settings()
    {
        return Setting::pluck('value', 'key')->toArray();
    }

    // ── Slack ─────────────────────────────────────────────────────────
    public static function slack($title, $message, $channel = null)
    {
        $url = self::settings()['slack_webhook_url'] ?? '';
        if (!$url) return false;
        try {
            $payload = ['text' => "*{$title}*\n{$message}"];
            if ($channel) $payload['channel'] = $channel;
            Http::post($url, $payload);
            return true;
        } catch (\Exception $e) {
            Log::error('Slack notify failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── Discord ────────────────────────────────────────────────────────
    public static function discord($title, $message, $color = 5814783)
    {
        $url = self::settings()['discord_webhook_url'] ?? '';
        if (!$url) return false;
        try {
            Http::post($url, [
                'embeds' => [[
                    'title' => $title,
                    'description' => $message,
                    'color' => $color,
                    'timestamp' => now()->toIso8601String(),
                ]],
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error('Discord notify failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── SMS via Twilio ─────────────────────────────────────────────────
    public static function sms($to, $message)
    {
        $s = self::settings();
        if (empty($s['sms_enabled']) || empty($s['twilio_sid']) || empty($s['twilio_token']) || empty($s['twilio_from'])) {
            return false;
        }
        try {
            $sid = $s['twilio_sid'];
            $token = $s['twilio_token'];
            $from = $s['twilio_from'];
            $resp = Http::asForm()->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $to,
                    'From' => $from,
                    'Body' => $message,
                ]);
            return $resp->successful();
        } catch (\Exception $e) {
            Log::error('SMS failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── WhatsApp Business API (Meta Cloud API) ──────────────────────────
    public static function whatsapp($to, $message, $type = 'text')
    {
        $s = self::settings();
        $token = $s['whatsapp_api_token'] ?? '';
        $phoneId = $s['whatsapp_phone_id'] ?? '';
        if (!$token || !$phoneId) return false;
        try {
            $body = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => ['body' => $message],
            ];
            $resp = Http::withToken($token)
                ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", $body);
            return $resp->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp API failed: ' . $e->getMessage());
            return false;
        }
    }

    // ── Quick translation (fallback via Gemini) ────────────────────────
    public static function quickTranslate($text, $from, $to)
    {
        if ($from === $to) return $text;
        $settings = self::settings();
        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';
        if (!$apiKey) return $text;
        try {
            $model = $settings['ai_gemini_model'] ?? 'gemini-1.5-flash';
            $resp = \Illuminate\Support\Facades\Http::timeout(10)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                ['contents' => [['parts' => [['text' => "Translate this {$from} text to {$to}. Keep the same tone and formatting. Only output the translation, nothing else.\n\n{$text}"]]]], 'generationConfig' => ['maxOutputTokens' => 400]]
            );
            if ($resp->successful()) {
                return trim($resp->json('candidates.0.content.parts.0.text')) ?: $text;
            }
        } catch (\Exception $e) {
            \Log::error('Translation failed: ' . $e->getMessage());
        }
        return $text;
    }

    // ── Combined notify: Slack + Discord + Filament ─────────────────────
    public static function notifyAll($title, $message, $type = 'info')
    {
        self::slack($title, $message);
        self::discord($title, $message);
        Log::info("[Notify] {$title}: {$message}");
    }
}
