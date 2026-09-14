<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminTicketReplyNotification extends Notification implements ShouldQueue
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
            ->subject('New Reply on Ticket: ' . $this->ticket->ticket_id)
            ->greeting('Hello Admin,')
            ->line('You have received a new reply from ' . $this->ticket->name . ' on ticket: ' . $this->ticket->subject)
            ->line('Message:')
            ->line($this->reply->message)
            ->action('View Ticket', \App\Filament\Resources\TicketResource::getUrl('view', ['record' => $this->ticket], panel: 'admin'))
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_uid' => $this->ticket->ticket_id,
            'sender_name' => $this->ticket->name,
            'message' => 'New reply on ticket ' . $this->ticket->ticket_id,
            'type' => 'ticket_reply_admin'
        ];
    }
}
