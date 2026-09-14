<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgreementCreatedFromRequestNotification extends Notification
{
    use Queueable;

    protected $agreement;
    protected $request;

    public function __construct($agreement, $request)
    {
        $this->agreement = $agreement;
        $this->request = $request;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Agreement is Ready - ' . $this->agreement->title)
            ->greeting('Hello ' . $this->agreement->client_name . ',')
            ->line('Great news! We have prepared your service agreement based on your project request #' . $this->request->request_number . '.')
            ->line('Agreement: ' . $this->agreement->title)
            ->line('Project: ' . $this->agreement->project_name)
            ->line('Total Value: ' . $this->agreement->formatted_total)
            ->action('View Agreement', route('client.agreement.view', $this->agreement))
            ->line('Please review all the terms and conditions carefully. Once you are satisfied, you can digitally sign the agreement to proceed.')
            ->line('If you have any questions or need modifications, please contact us.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'agreement_id' => $this->agreement->id,
            'agreement_number' => $this->agreement->agreement_number,
            'title' => $this->agreement->title,
            'project_name' => $this->agreement->project_name,
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'type' => 'agreement_created_from_request',
            'action_url' => route('client.agreement.view', $this->agreement),
        ];
    }
}
