<?php

namespace App\Services;

use App\Models\Bconnect\BconnectWebhook;
use App\Models\Bconnect\BconnectWebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BconnectWebhookService
{
    public static function dispatch(int $companyId, string $event, array $payload): void
    {
        $webhooks = BconnectWebhook::where('company_id', $companyId)
            ->where('active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if (!empty($webhook->events) && !in_array($event, $webhook->events, true)) {
                continue;
            }
            self::send($webhook, $event, $payload);
        }
    }

    public static function send(BconnectWebhook $webhook, string $event, array $payload): void
    {
        $body = json_encode(['event' => $event, 'payload' => $payload, 'timestamp' => now()->toIso8601String()]);
        $headers = ['Content-Type' => 'application/json'];
        if ($webhook->secret) {
            $headers['X-Bconnect-Signature'] = hash_hmac('sha256', $body, $webhook->secret);
        }

        $log = BconnectWebhookLog::create([
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $body,
            'attempt' => 1,
        ]);

        try {
            $resp = Http::timeout(15)->withHeaders($headers)->post($webhook->url, json_decode($body, true));
            $log->update(['status_code' => $resp->status(), 'response' => $resp->body(), 'delivered_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['response' => $e->getMessage()]);
            Log::warning('Bmydesk webhook failed: ' . $e->getMessage());
        }
    }

    public static function retry(BconnectWebhookLog $log): void
    {
        if ($log->attempt >= 5) return;
        $log->increment('attempt');
        self::send($log->webhook, $log->event, json_decode($log->payload, true)['payload'] ?? []);
    }
}
