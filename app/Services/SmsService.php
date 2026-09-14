<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public static function enabled(): bool
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $provider = $settings['sms_provider'] ?? 'none';
        return in_array($provider, ['msg91', 'twilio']) && ($settings['sms_enabled'] ?? '0') === '1';
    }

    public static function send(string $phone, string $message): bool
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $provider = $settings['sms_provider'] ?? 'none';

        if (!$phone || !self::enabled()) {
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        try {
            return match ($provider) {
                'msg91' => self::sendMsg91($phone, $message, $settings),
                'twilio' => self::sendTwilio($phone, $message, $settings),
                default => false,
            };
        } catch (\Throwable $e) {
            Log::error('SMS send failed: ' . $e->getMessage());
            return false;
        }
    }

    protected static function sendMsg91(string $phone, string $message, array $settings): bool
    {
        $authKey = $settings['msg91_auth_key'] ?? '';
        $sender = $settings['msg91_sender_id'] ?? '';
        $route = $settings['msg91_route'] ?? '4'; // 4 = transactional

        if (!$authKey) {
            return false;
        }

        $response = Http::withHeaders([
            'authkey' => $authKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v2/sendsms', [
            'sender' => $sender,
            'route' => $route,
            'country' => $settings['msg91_country'] ?? '91',
            'sms' => [
                [
                    'message' => $message,
                    'to' => [$phone],
                ],
            ],
        ]);

        return $response->successful();
    }

    protected static function sendTwilio(string $phone, string $message, array $settings): bool
    {
        $sid = $settings['twilio_sid'] ?? '';
        $token = $settings['twilio_token'] ?? '';
        $from = $settings['twilio_from'] ?? '';

        if (!$sid || !$token || !$from) {
            return false;
        }

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $phone,
                'Body' => $message,
            ]);

        return $response->successful();
    }
}
