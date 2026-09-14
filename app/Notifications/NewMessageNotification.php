<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Support Message: ' . $this->message->sender_name)
            ->greeting('Hello Admin,')
            ->line('You have received a new message from ' . $this->message->sender_name . ' (' . $this->message->sender_email . ').')
            ->line('Message: ' . $this->message->message)
            ->action('View in Admin Panel', url('/admin/messages'))
            ->line('Thank you for using our platform!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'sender_name' => $this->message->sender_name,
            'sender_email' => $this->message->sender_email,
            'message_preview' => substr($this->message->message, 0, 100),
        ];
    }
}
