<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    use Queueable;

    protected $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We’ve Received Your Inquiry - Believoo Support')
            ->greeting('Hello ' . $this->ticket->name . ',')
            ->line('Thanks for contacting Believoo! This is an automated confirmation that we’ve received your message.')
            ->line('Ticket ID: ' . $this->ticket->ticket_id)
            ->line('Status: In Progress')
            ->line('One of our specialists will review your requirements and respond within 24 hours. In the meantime, feel free to explore our portfolio to see our next-gen work.')
            ->action('View Dashboard', route('client.dashboard'))
            ->line('Excellence is on its way.')
            ->line('Team Believoo');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->ticket_id,
            'subject' => $this->ticket->subject,
            'message' => 'Your ticket has been created successfully.',
            'type' => 'ticket_created'
        ];
    }
}
