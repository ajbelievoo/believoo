<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MilestonePaidNotification extends Notification
{
    use Queueable;

    protected $milestone;
    protected $order;

    public function __construct($milestone, $order)
    {
        $this->milestone = $milestone;
        $this->order = $order;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('💰 Milestone Payment Received - ' . $this->milestone->agreement->project_name)
            ->greeting('Hello Admin,')
            ->line('A milestone payment has been received.')
            ->line('Project: ' . $this->milestone->agreement->project_name)
            ->line('Client: ' . $this->milestone->agreement->client->name)
            ->line('Milestone: ' . $this->milestone->phase_name)
            ->line('Amount: ' . $this->order->currency . ' ' . number_format($this->order->amount, 2))
            ->action('View Agreement', route('filament.admin.resources.agreements.view', $this->milestone->agreement))
            ->line('The milestone status has been automatically updated.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'milestone_id' => $this->milestone->id,
            'agreement_id' => $this->milestone->agreement_id,
            'project_name' => $this->milestone->agreement->project_name,
            'client_name' => $this->milestone->agreement->client->name,
            'amount' => $this->order->amount,
            'currency' => $this->order->currency,
            'type' => 'milestone_paid',
            'action_url' => route('filament.admin.resources.agreements.view', $this->milestone->agreement),
        ];
    }
}
