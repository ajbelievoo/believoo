<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralRewardNotification extends Notification
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
            ->subject('🎉 Congratulations! You\'ve Earned a Referral Discount!')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Fantastic news! Your referral has been rewarded!')
            ->line('')
            ->line('🎁 **Reward Details:**')
            ->line('Referred Friend: ' . $this->referral->referred_name ?? $this->referral->referred_email)
            ->line('Discount Earned: $' . number_format($this->referral->discount_amount, 2))
            ->line('Your Total Balance: $' . number_format($notifiable->referral_discount_balance, 2))
            ->line('')
            ->line('💰 **How to use your discount:**')
            ->line('Your discount will be automatically applied to your next milestone payment.')
            ->line('')
            ->line('🚀 **Keep referring and earning!**')
            ->line('There\'s no limit to how many friends you can refer.')
            ->line('')
            ->action('Refer More Friends', route('client.dashboard'))
            ->line('Thanks for being a valued member of the Believoo community!')
            ->salutation('Cheers, The Believoo Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'referral_id' => $this->referral->id,
            'referred_email' => $this->referral->referred_email,
            'discount_amount' => $this->referral->discount_amount,
            'total_balance' => $notifiable->referral_discount_balance,
            'type' => 'referral_rewarded',
            'action_url' => route('client.dashboard'),
        ];
    }
}
