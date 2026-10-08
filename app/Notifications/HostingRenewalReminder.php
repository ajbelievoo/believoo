<?php

namespace App\Notifications;

use App\Models\UserHosting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HostingRenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected UserHosting $hosting,
        protected int $daysLeft
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $hosting = $this->hosting;
        $service = $hosting->plan_name ?: ucfirst($hosting->hosting_type ?? 'Hosting');
        $expiry = optional($hosting->expiry_date)->format('d M Y');
        $urgency = $this->daysLeft <= 1 ? 'URGENT: ' : ($this->daysLeft <= 3 ? 'Reminder: ' : '');

        return (new MailMessage)
            ->subject($urgency . 'Service renews in ' . $this->daysLeft . ' day' . ($this->daysLeft === 1 ? '' : 's') . ' - ' . $service)
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line('Your service **' . $service . '** is due for renewal on **' . $expiry . '** (' . $this->daysLeft . ' day' . ($this->daysLeft === 1 ? '' : 's') . ' left).')
            ->line('To avoid service suspension, please renew before the due date. You can pay using your wallet balance or any available payment method.')
            ->action('Renew Now', url('/client/invoices'))
            ->line('If you have already paid, please ignore this reminder.');
    }
}
