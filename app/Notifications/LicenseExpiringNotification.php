<?php

namespace App\Notifications;

use App\Models\VpsLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiringNotification extends Notification
{
    use Queueable;

    protected VpsLicense $license;
    protected int $daysRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(VpsLicense $license)
    {
        $this->license = $license;
        $this->daysRemaining = $license->expires_at ? $license->expires_at->diffInDays(now()) : 0;
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
        $urgency = $this->daysRemaining <= 3 ? '🔴 Urgent' : '🟡 Reminder';
        $server = $this->license->proxmoxVm;

        return (new MailMessage)
            ->subject("{$urgency}: Your License Expires in {$this->daysRemaining} Days")
            ->greeting("Hello {$notifiable->name},")
            ->when($this->daysRemaining <= 3, function ($mail) {
                $mail->line('⚠️ **Your license expires soon!** Please renew to avoid service interruption.');
            })
            ->when($this->daysRemaining > 3, function ($mail) {
                $mail->line('This is a friendly reminder about your upcoming license expiration.');
            })
            ->line('---')
            ->line("**License Type:** " . ucfirst($this->license->type))
            ->line('---')
            ->when($server, function ($mail) use ($server) {
                $mail->line("**Server:** {$server->hostname}")
                     ->line("**IP Address:** {$server->ip_address}");
            })
            ->line('---')
            ->line("**Expires On:** {$this->license->expires_at->format('F j, Y')}")
            ->line("**Days Remaining:** {$this->daysRemaining}")
            ->action('Renew License', url('/client/licenses'))
            ->line('If you have any questions, please contact our support team.')
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
            'type' => 'license_expiring',
            'license_id' => $this->license->id,
            'license_type' => $this->license->type,
            'days_remaining' => $this->daysRemaining,
            'expires_at' => $this->license->expires_at,
        ];
    }
}
