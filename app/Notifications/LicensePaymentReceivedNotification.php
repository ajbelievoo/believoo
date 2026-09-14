<?php

namespace App\Notifications;

use App\Models\LicenseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicensePaymentReceivedNotification extends Notification
{
    use Queueable;

    protected LicenseOrder $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(LicenseOrder $order)
    {
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

        return (new MailMessage)
            ->subject('💰 Payment Received - License Order #' . $this->order->order_number)
            ->greeting("Hello {$notifiable->name},")
            ->line('We have received your payment! Your license will be activated shortly.')
            ->line('---')
            ->line("**Order Number:** {$this->order->order_number}")
            ->line("**License Type:** {$licenseName}")
            ->line("**Billing Cycle:** " . ucfirst($this->order->billing_cycle))
            ->line('---')
            ->line("**Amount Paid:** {$this->order->currency} {$this->order->amount}")
            ->line("**Payment Method:** " . strtoupper($this->order->payment_method ?? 'N/A'))
            ->line("**Payment ID:** {$this->order->payment_id}")
            ->line("**Paid At:** {$this->order->paid_at->format('F j, Y g:i A')}")
            ->line('---')
            ->when($this->order->activation_status === 'ready_to_activate', function ($mail) {
                $mail->line('✅ **Status:** Ready to activate')
                     ->action('Activate Now', url("/client/licenses/orders/{$this->order->order_number}"));
            })
            ->when($this->order->activation_status === 'active', function ($mail) {
                $mail->line('✅ **Status:** Already activated')
                     ->action('View License', url('/client/licenses'));
            })
            ->line('Thank you for your business!')
            ->salutation('The BelieVoo Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'license_payment_received',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'amount' => $this->order->amount,
            'license_type' => $this->order->license_type,
        ];
    }
}
