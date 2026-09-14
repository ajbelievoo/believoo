<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AmcPaymentProofSubmittedNotification extends Notification
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
        return (new MailMessage)
            ->subject('🧾 AMC Payment Proof Submitted - ' . $this->subscription->agreement->project_name)
            ->greeting('Hello Admin,')
            ->line('A client has submitted payment proof for AMC subscription.')
            ->line('')
            ->line('📋 **Subscription Details:**')
            ->line('Client: ' . $this->subscription->client->name)
            ->line('Project: ' . $this->subscription->agreement->project_name)
            ->line('Plan: ' . $this->subscription->plan_label)
            ->line('Amount: $' . number_format($this->subscription->monthly_amount, 2) . '/month')
            ->line('Subscription #: ' . $this->subscription->subscription_number)
            ->line('')
            ->action('Verify & Activate AMC', route('admin.amc-subscriptions.index'))
            ->line('Please verify the payment and activate the subscription.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'subscription_id' => $this->subscription->id,
            'subscription_number' => $this->subscription->subscription_number,
            'client_name' => $this->subscription->client->name,
            'project_name' => $this->subscription->agreement->project_name,
            'amount' => $this->subscription->monthly_amount,
            'type' => 'amc_payment_proof',
            'action_url' => route('admin.amc-subscriptions.index'),
        ];
    }
}
