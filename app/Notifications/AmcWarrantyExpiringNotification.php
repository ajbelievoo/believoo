<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AmcWarrantyExpiringNotification extends Notification
{
    use Queueable;

    protected $agreement;
    protected $daysLeft;
    protected $amcAmount;

    public function __construct($agreement, int $daysLeft, float $amcAmount = 200.00)
    {
        $this->agreement = $agreement;
        $this->daysLeft = $daysLeft;
        $this->amcAmount = $amcAmount;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⏰ Your Free Support Ends in ' . $this->daysLeft . ' Days - Subscribe to AMC!')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We hope your project is running smoothly!')
            ->line('')
            ->line('⏰ **Important:** Your complimentary support period ends in **' . $this->daysLeft . ' days**.')
            ->line('')
            ->line('🔧 **Continue Worry-Free with our AMC (Annual Maintenance Contract)**')
            ->line('- 24/7 Priority Support')
            ->line('- Bug Fixes & Updates')
            ->line('- Performance Monitoring')
            ->line('- Security Patches')
            ->line('- Monthly Health Reports')
            ->line('')
            ->line('💰 **Only $' . number_format($this->amcAmount, 2) . '/month**')
            ->line('')
            ->action('Subscribe to AMC Now', route('client.amc.subscribe', $this->agreement))
            ->line('')
            ->line('Need help? Reply to this email or WhatsApp us anytime!')
            ->salutation('Best regards, The Believoo Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agreement_id' => $this->agreement->id,
            'project_name' => $this->agreement->project_name,
            'days_left' => $this->daysLeft,
            'amc_amount' => $this->amcAmount,
            'type' => 'warranty_expiring',
            'action_url' => route('client.amc.subscribe', $this->agreement),
        ];
    }
}
