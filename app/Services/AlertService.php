<?php

namespace App\Services;

use App\Models\AdminAlertSetting;
use App\Models\ServerHealthCheck;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\ServerAlertNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AlertService
{
    /**
     * Send alert through configured channels
     */
    public function sendAlert(ServerHealthCheck $healthCheck, AdminAlertSetting $settings): void
    {
        $channels = $settings->getActiveChannels();
        
        if (empty($channels)) {
            Log::warning('No alert channels configured', ['user_id' => $settings->user_id]);
            return;
        }

        $alertData = $this->formatAlertData($healthCheck);
        
        foreach ($channels as $channel) {
            try {
                match ($channel) {
                    'telegram' => $this->sendTelegramAlert($settings, $alertData),
                    'email' => $this->sendEmailAlert($settings, $alertData),
                    default => null,
                };
            } catch (\Exception $e) {
                Log::error("Alert failed via {$channel}", [
                    'error' => $e->getMessage(),
                    'health_check_id' => $healthCheck->id,
                ]);
            }
        }

        $healthCheck->markAlertSent(implode(',', $channels));
    }

    /**
     * Send immediate critical alert
     */
    public function sendCriticalAlert(string $type, string $message, array $details = []): void
    {
        $settings = AdminAlertSetting::where('user_id', 1)->first(); // Default to first admin
        
        if (!$settings) {
            Log::warning('No admin alert settings found');
            return;
        }

        $alertData = [
            'status' => 'critical',
            'type' => $type,
            'title' => "🚨 Critical: {$type}",
            'message' => $message,
            'details' => $details,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        $channels = $settings->getActiveChannels();
        
        foreach ($channels as $channel) {
            match ($channel) {
                'telegram' => $this->sendTelegramAlert($settings, $alertData),
                'email' => $this->sendEmailAlert($settings, $alertData),
                default => null,
            };
        }
    }

    /**
     * Send payment notification
     */
    public function sendPaymentAlert($order): void
    {
        $settings = AdminAlertSetting::where('alert_payment_received', true)->get();
        
        foreach ($settings as $setting) {
            $alertData = [
                'status' => 'info',
                'type' => 'payment_received',
                'title' => '💰 Payment Received',
                'message' => "Order #{$order->order_number} paid: ₹{$order->amount}",
                'details' => [
                    'Customer' => $order->user->name,
                    'Email' => $order->user->email,
                    'Amount' => '₹' . $order->amount,
                    'Method' => strtoupper($order->payment_method ?? 'N/A'),
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];

            foreach ($setting->getActiveChannels() as $channel) {
                match ($channel) {
                    'telegram' => $this->sendTelegramAlert($setting, $alertData),
                    'email' => $this->sendEmailAlert($setting, $alertData),
                    default => null,
                };
            }
        }
    }

    /**
     * Send high priority ticket alert
     */
    public function sendTicketAlert(Ticket $ticket): void
    {
        if ($ticket->priority !== 'high' && $ticket->priority !== 'urgent') {
            return;
        }

        $settings = AdminAlertSetting::where('alert_ticket_priority_high', true)->get();
        
        foreach ($settings as $setting) {
            $alertData = [
                'status' => 'warning',
                'type' => 'high_priority_ticket',
                'title' => "🎫 High Priority Ticket: {$ticket->ticket_id}",
                'message' => $ticket->subject,
                'details' => [
                    'From' => $ticket->name,
                    'Email' => $ticket->email,
                    'Priority' => strtoupper($ticket->priority),
                    'Status' => $ticket->status,
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'action_url' => route('admin.tickets.show', $ticket),
            ];

            foreach ($setting->getActiveChannels() as $channel) {
                match ($channel) {
                    'telegram' => $this->sendTelegramAlert($setting, $alertData),
                    'email' => $this->sendEmailAlert($setting, $alertData),
                    default => null,
                };
            }
        }
    }

    /**
     * Send Telegram alert
     */
    private function sendTelegramAlert(AdminAlertSetting $settings, array $data): void
    {
        $token = $settings->telegram_bot_token;
        $chatId = $settings->telegram_chat_id;

        if (!$token || !$chatId) {
            return;
        }

        $emoji = match ($data['status']) {
            'critical' => '🚨',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '🔔',
        };

        $text = "{$emoji} *{$data['title']}*\n\n";
        $text .= "{$data['message']}\n\n";
        
        if (!empty($data['details'])) {
            $text .= "*Details:*\n";
            foreach ($data['details'] as $key => $value) {
                $text .= "• {$key}: {$value}\n";
            }
            $text .= "\n";
        }
        
        $text .= "🕐 {$data['timestamp']}";

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true,
        ]);
    }

    /**
     * Send Email alert
     */
    private function sendEmailAlert(AdminAlertSetting $settings, array $data): void
    {
        $email = $settings->email_address ?? $settings->user->email;

        if (!$email) {
            return;
        }

        // Send notification via Laravel's notification system
        $settings->user->notify(new ServerAlertNotification($data));
    }

    /**
     * Format health check data for alerts
     */
    private function formatAlertData(ServerHealthCheck $healthCheck): array
    {
        $vm = $healthCheck->vm;
        
        $statusLabels = [
            'critical' => '🚨 CRITICAL',
            'warning' => '⚠️ WARNING',
            'healthy' => '✅ HEALTHY',
        ];

        return [
            'status' => $healthCheck->status,
            'type' => $healthCheck->check_type,
            'title' => $statusLabels[$healthCheck->status] ?? 'ALERT',
            'message' => $healthCheck->message ?? "VM health check: {$healthCheck->check_type}",
            'details' => [
                'VM ID' => $vm?->vmid ?? 'N/A',
                'Hostname' => $vm?->hostname ?? 'Unknown',
                'Check Type' => $healthCheck->check_type,
                'Metric' => $healthCheck->metric_value ? "{$healthCheck->metric_value}{$healthCheck->metric_unit}" : 'N/A',
            ],
            'timestamp' => $healthCheck->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Test alert channels
     */
    public function testChannels(AdminAlertSetting $settings): array
    {
        $results = [];
        
        $testData = [
            'status' => 'info',
            'type' => 'test',
            'title' => '🔔 Test Alert',
            'message' => 'This is a test alert from BelieVoo monitoring system.',
            'details' => ['Test' => 'Connection verified'],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];

        foreach ($settings->getActiveChannels() as $channel) {
            try {
                match ($channel) {
                    'telegram' => $this->sendTelegramAlert($settings, $testData),
                    'email' => $this->sendEmailAlert($settings, $testData),
                    default => null,
                };
                $results[$channel] = 'success';
            } catch (\Exception $e) {
                $results[$channel] = 'failed: ' . $e->getMessage();
            }
        }

        return $results;
    }
}
