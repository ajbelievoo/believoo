<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketReplyNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $reply;

    /**
     * Create a new notification instance.
     */
    public function __construct($ticket, $reply)
    {
        $this->ticket = $ticket;
        $this->reply = $reply;
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
                    ->subject('Update on your Ticket: ' . $this->ticket->ticket_id)
                    ->greeting('Hello ' . $this->ticket->name . ',')
                    ->line('Our support team has replied to your ticket: ' . $this->ticket->subject)
                    ->line('Message:')
                    ->line($this->reply->message)
                    ->action('View Ticket', route('client.dashboard', ['ticket' => $this->ticket->ticket_id]))
                    ->line('Thank you for choosing Believoo!');
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
            'message' => 'Support team replied to your ticket',
            'type' => 'ticket_reply'
        ];
    }
}
