<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgreementRequestSubmittedNotification extends Notification
{
    use Queueable;

    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Agreement Request - ' . $this->request->project_name)
            ->greeting('Hello Admin,')
            ->line('A new agreement request has been submitted by a client.')
            ->line('Project: ' . $this->request->project_name)
            ->line('Client: ' . $this->request->client->name)
            ->line('Request #: ' . $this->request->request_number)
            ->action('Review Request', route('filament.admin.resources.agreement-requests.view', $this->request))
            ->line('Please review the request and create an agreement proposal.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'project_name' => $this->request->project_name,
            'client_name' => $this->request->client->name,
            'type' => 'agreement_request_submitted',
            'action_url' => route('filament.admin.resources.agreement-requests.view', $this->request),
        ];
    }
}
