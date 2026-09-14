<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralSentNotification extends Notification
{
    use Queueable;

    protected $referral;

    public function __construct($referral)
    {
        $this->referral = $referral;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🎉 Referral Sent Successfully!')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your referral has been sent to ' . $this->referral->referred_email)
            ->line('')
            ->line('📧 **What happens next?**')
            ->line('• Your friend will receive an invitation email')
            ->line('• When they sign up, you\'ll get notified')
            ->line('• When they complete their first project, you get 10% off your next milestone!')
            ->line('')
            ->line('🎁 **Your Referral Stats:**')
            ->line('Total Referrals: ' . $notifiable->total_referrals)
            ->line('Successful: ' . $notifiable->successful_referrals)
            ->line('Discount Balance: $' . number_format($notifiable->referral_discount_balance, 2))
            ->line('')
            ->action('View Your Referrals', route('client.dashboard'))
            ->line('Thanks for spreading the word about Believoo!')
            ->salutation('Best regards, The Believoo Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'referral_id' => $this->referral->id,
            'referred_email' => $this->referral->referred_email,
            'referral_code' => $this->referral->referral_code,
            'type' => 'referral_sent',
            'action_url' => route('client.dashboard'),
        ];
    }
}
