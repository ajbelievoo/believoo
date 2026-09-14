<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgreementRequestStatusNotification extends Notification
{
    use Queueable;

    protected $request;
    protected $status;
    protected $message;

    public function __construct($request, $status, $message = null)
    {
        $this->request = $request;
        $this->status = $status;
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->status) {
            'under_review' => 'Your Project Request is Under Review',
            'approved' => 'Your Project Request Has Been Approved',
            'rejected' => 'Update on Your Project Request',
            default => 'Project Request Update',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $this->request->client->name . ',')
            ->line('Your project request #' . $this->request->request_number . ' has been updated.');

        switch ($this->status) {
            case 'under_review':
                $mail->line('Our team is now reviewing your project requirements.')
                     ->line('We will prepare a detailed agreement proposal for you shortly.');
                break;
            case 'approved':
                $mail->line('Great news! Your project request has been approved.')
                     ->line('We are now preparing your agreement proposal.')
                     ->line('You will receive the agreement soon for your review and signature.');
                break;
            case 'rejected':
                $mail->line('Unfortunately, we are unable to proceed with your project request at this time.')
                     ->lineIf($this->message, 'Reason: ' . $this->message)
                     ->line('Please feel free to reach out if you have any questions.');
                break;
        }

        return $mail->action('View Dashboard', route('client.dashboard', ['tab' => 'agreements']))
                    ->line('Thank you for choosing Believoo!');
    }

    public function toArray(object $notifiable): array
    {
        $statusLabel = match ($this->status) {
            'under_review' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Updated',
        };

        return [
            'request_id' => $this->request->id,
            'request_number' => $this->request->request_number,
            'project_name' => $this->request->project_name,
            'status' => $this->status,
            'status_label' => $statusLabel,
            'type' => 'agreement_request_status',
            'action_url' => route('client.dashboard', ['tab' => 'agreements']),
        ];
    }
}
