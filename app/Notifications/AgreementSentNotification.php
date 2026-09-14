<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgreementSentNotification extends Notification
{
    use Queueable;

    protected $agreement;

    public function __construct($agreement)
    {
        $this->agreement = $agreement;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Agreement from Believoo - ' . $this->agreement->title)
            ->greeting('Hello ' . $this->agreement->client_name . ',')
            ->line('We have prepared a service agreement for your review.')
            ->line('Agreement: ' . $this->agreement->title)
            ->line('Project: ' . $this->agreement->project_name)
            ->line('Total Value: ' . $this->agreement->formatted_total)
            ->action('View Agreement', route('client.agreement.view', $this->agreement))
            ->line('Please review the agreement and sign it to proceed.')
            ->line('If you have any questions, feel free to reach out to us.')
            ->line('Team Believoo');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agreement_id' => $this->agreement->id,
            'agreement_number' => $this->agreement->agreement_number,
            'title' => $this->agreement->title,
            'project_name' => $this->agreement->project_name,
            'type' => 'agreement_sent',
            'action_url' => route('client.agreement.view', $this->agreement),
        ];
    }
}
