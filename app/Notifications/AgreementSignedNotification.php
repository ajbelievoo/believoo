<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgreementSignedNotification extends Notification
{
    use Queueable;

    protected $agreement;
    protected $signedBy;

    public function __construct($agreement, $signedBy = 'client')
    {
        $this->agreement = $agreement;
        $this->signedBy = $signedBy;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->signedBy === 'client') {
            return (new MailMessage)
                ->subject('Agreement Signed - ' . $this->agreement->title)
                ->greeting('Hello,')
                ->line('Great news! The client has signed the agreement.')
                ->line('Agreement: ' . $this->agreement->title)
                ->line('Client: ' . $this->agreement->client_name)
                ->action('View Agreement', route('filament.admin.resources.agreements.view', $this->agreement))
                ->line('You can now proceed with the project.')
                ->line('Team Believoo');
        }

        return (new MailMessage)
            ->subject('Agreement Fully Executed - ' . $this->agreement->title)
            ->greeting('Hello ' . $this->agreement->client_name . ',')
            ->line('Your agreement has been fully signed by both parties.')
            ->line('Agreement: ' . $this->agreement->title)
            ->line('Project: ' . $this->agreement->project_name)
            ->action('View Agreement', route('client.agreement.view', $this->agreement))
            ->line('We are excited to work with you on this project!')
            ->line('Team Believoo');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agreement_id' => $this->agreement->id,
            'agreement_number' => $this->agreement->agreement_number,
            'title' => $this->agreement->title,
            'signed_by' => $this->signedBy,
            'type' => 'agreement_signed',
        ];
    }
}
