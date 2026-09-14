<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServerAlertNotification extends Notification
{
    use Queueable;

    protected array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusColor = match ($this->data['status']) {
            'critical' => '#ef4444',
            'warning' => '#f59e0b',
            'info' => '#3b82f6',
            default => '#6b7280',
        };

        $mail = (new MailMessage)
            ->subject("[{$this->data['status']}] Server Alert: {$this->data['type']}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->data['title'])
            ->line($this->data['message']);

        if (!empty($this->data['details'])) {
            $mail->line('---');
            foreach ($this->data['details'] as $key => $value) {
                $mail->line("**{$key}:** {$value}");
            }
        }

        $mail->line('---')
            ->line("**Time:** {$this->data['timestamp']}")
            ->action('View Dashboard', url('/admin/dashboard'))
            ->line('This is an automated alert from BelieVoo monitoring system.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'server_alert',
            'status' => $this->data['status'],
            'alert_type' => $this->data['type'],
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'details' => $this->data['details'] ?? [],
            'timestamp' => $this->data['timestamp'],
        ];
    }
}
