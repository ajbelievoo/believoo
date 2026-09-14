<?php

namespace App\Notifications;

use App\Models\VpsLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseActivatedNotification extends Notification
{
    use Queueable;

    protected VpsLicense $license;

    /**
     * Create a new notification instance.
     */
    public function __construct(VpsLicense $license)
    {
        $this->license = $license;
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
        $licenseKey = decrypt($this->license->license_key);
        $server = $this->license->proxmoxVm;

        return (new MailMessage)
            ->subject('🎉 Your License is Now Active!')
            ->greeting("Hello {$notifiable->name},")
            ->line('Great news! Your license has been successfully activated.')
            ->line('---')
            ->line("**License Type:** " . ucfirst($this->license->type))
            ->line("**License Key:** {$licenseKey}")
            ->line("**Activation Code:** {$this->license->activation_code}")
            ->line('---')
            ->when($server, function ($mail) use ($server) {
                $mail->line("**Server:** {$server->hostname}")
                     ->line("**IP Address:** {$server->ip_address}");
            })
            ->line('---')
            ->line("**Activated At:** {$this->license->activated_at->format('F j, Y g:i A')}")
            ->line("**Expires At:** {$this->license->expires_at->format('F j, Y g:i A')}")
            ->action('Manage License', url('/client/licenses'))
            ->line('Thank you for choosing BelieVoo!')
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
            'type' => 'license_activated',
            'license_id' => $this->license->id,
            'license_type' => $this->license->type,
            'server_id' => $this->license->proxmox_vm_id,
            'expires_at' => $this->license->expires_at,
        ];
    }
}
