<?php

namespace App\Notifications;

use App\Models\LicenseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLicenseNotification extends Notification
{
    use Queueable;

    protected string $event;
    protected LicenseOrder $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $event, LicenseOrder $order)
    {
        $this->event = $event;
        $this->order = $order;
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
        $licenseConfig = \App\Services\LicenseCheckoutService::LICENSE_TYPES[$this->order->license_type] ?? null;
        $licenseName = $licenseConfig['name'] ?? ucfirst($this->order->license_type);

        $subject = match ($this->event) {
            'payment' => "💰 License Payment Received - {$this->order->order_number}",
            'activated' => "✅ License Activated - {$this->order->order_number}",
            'failed' => "❌ License Activation Failed - {$this->order->order_number}",
            default => "License Update - {$this->order->order_number}",
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello Admin,")
            ->line("**Event:** " . ucfirst($this->event))
            ->line('---')
            ->line("**Order Number:** {$this->order->order_number}")
            ->line("**Customer:** {$this->order->user->name} ({$this->order->user->email})")
            ->line("**License Type:** {$licenseName}")
            ->line("**Amount:** {$this->order->currency} {$this->order->amount}")
            ->line('---')
            ->line("**Payment Status:** {$this->order->payment_status}")
            ->line("**Activation Status:** {$this->order->activation_status}");

        if ($this->order->server_ip) {
            $mail->line("**Server IP:** {$this->order->server_ip}");
        }

        $mail->action('View Order', url("/admin/license-orders/{$this->order->id}"))
             ->line('This is an automated admin notification.');

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
            'type' => 'admin_license_notification',
            'event' => $this->event,
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'user_id' => $this->order->user_id,
            'license_type' => $this->order->license_type,
            'amount' => $this->order->amount,
        ];
    }
}
