<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionUrl = null,
        public ?string $actionLabel = 'View',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'        => $this->title,
            'message'      => $this->message,
            'action_url'   => $this->actionUrl,
            'action_label' => $this->actionLabel,
        ];
    }
}
