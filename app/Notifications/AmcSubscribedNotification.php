<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AmcSubscribedNotification extends Notification
{
    use Queueable;

    protected $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $agreement = $this->subscription->agreement;

        return (new MailMessage)
            ->subject('🎉 AMC Subscription Confirmed - Welcome to Priority Support!')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Thank you for subscribing to our Annual Maintenance Contract!')
            ->line('')
            ->line('📋 **Subscription Details:**')
            ->line('Plan: ' . $this->subscription->plan_label)
            ->line('Project: ' . $agreement->project_name)
            ->line('Monthly Amount: $' . number_format($this->subscription->monthly_amount, 2))
            ->line('Valid From: ' . $this->subscription->start_date->format('M d, Y'))
            ->line('Valid Until: ' . $this->subscription->end_date->format('M d, Y'))
            ->line('Subscription #: ' . $this->subscription->subscription_number)
            ->line('')
            ->line('✅ **Your AMC Benefits Include:**')
            ->line('• 24/7 Priority Support')
            ->line('• Unlimited Bug Fixes')
            ->line('• Security Updates')
            ->line('• Performance Optimization')
            ->line('• Monthly System Reports')
            ->line('')
            ->action('View Subscription', route('client.dashboard', ['tab' => 'agreements']))
            ->line('')
            ->line('Need immediate assistance? Contact us anytime!')
            ->salutation('Welcome to the Priority Club, The Believoo Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'subscription_number' => $this->subscription->subscription_number,
            'agreement_id' => $this->subscription->agreement_id,
            'project_name' => $this->subscription->agreement->project_name,
            'plan_type' => $this->subscription->plan_type,
            'type' => 'amc_subscribed',
            'action_url' => route('client.dashboard', ['tab' => 'agreements']),
        ];
    }
}
