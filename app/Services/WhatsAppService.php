<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public static function enabled(): bool
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $provider = $settings['whatsapp_provider'] ?? 'none';
        return in_array($provider, ['twilio', 'wati', 'meta']) && ($settings['whatsapp_enabled'] ?? '0') === '1';
    }

    public static function send(string $phone, string $message, ?string $templateName = null, array $vars = []): bool
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $provider = $settings['whatsapp_provider'] ?? 'none';

        if (!$phone || !self::enabled()) {
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        try {
            return match ($provider) {
                'twilio' => self::sendTwilio($phone, $message, $settings),
                'wati' => self::sendWati($phone, $message, $settings),
                'meta' => self::sendMeta($phone, $templateName, $vars, $settings),
                default => false,
            };
        } catch (\Throwable $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function chatLink(string $phone, string $message = ''): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return 'https://wa.me/' . $phone . ($message ? '?text=' . urlencode($message) : '');
    }

    protected static function sendTwilio(string $phone, string $message, array $settings): bool
    {
        $sid = $settings['twilio_sid'] ?? '';
        $token = $settings['twilio_token'] ?? '';
        $from = $settings['twilio_whatsapp_from'] ?? '';

        if (!$sid || !$token || !$from) {
            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => 'whatsapp:' . $from,
                'To' => 'whatsapp:' . $phone,
                'Body' => $message,
            ]);

        return $response->successful();
    }

    protected static function sendWati(string $phone, string $message, array $settings): bool
    {
        $apiKey = $settings['wati_api_key'] ?? '';
        $domain = $settings['wati_domain'] ?? '';

        if (!$apiKey || !$domain) {
            return false;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://{$domain}.wati.io/api/v1/send-session-message", [
            'whatsappNumber' => $phone,
            'messageText' => $message,
        ]);

        return $response->successful();
    }

    protected static function sendMeta(string $phone, ?string $templateName, array $vars, array $settings): bool
    {
        $token = $settings['meta_whatsapp_token'] ?? '';
        $phoneId = $settings['meta_whatsapp_phone_id'] ?? '';

        if (!$token || !$phoneId || !$templateName) {
            return false;
        }

        $parameters = [];
        foreach ($vars as $var) {
            $parameters[] = ['type' => 'text', 'text' => $var];
        }

        $response = Http::withToken($token)
            ->post("https://graph.facebook.com/v18.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'en'],
                    'components' => $parameters ? [['type' => 'body', 'parameters' => $parameters]] : [],
                ],
            ]);

        return $response->successful();
    }
}
