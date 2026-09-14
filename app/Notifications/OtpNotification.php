<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    use Queueable;

    protected string $otp;
    protected int $expiresInMinutes;
    protected ?string $purpose;

    public function __construct(string $otp, int $expiresInMinutes = 10, ?string $purpose = null)
    {
        $this->otp = $otp;
        $this->expiresInMinutes = $expiresInMinutes;
        $this->purpose = $purpose;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Your Believoo Verification Code: ' . $this->otp)
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . ',')
            ->line($this->purpose
                ? 'Use the one-time password (OTP) below to ' . $this->purpose . ':'
                : 'Use the one-time password (OTP) below to complete your verification:')
            ->line('# ' . $this->otp)
            ->line('This code is valid for **' . $this->expiresInMinutes . ' minutes**. Do not share it with anyone — Believoo staff will never ask for your OTP.')
            ->line('If you did not request this code, you can safely ignore this email.')
            ->salutation('Best regards, The Believoo Team');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'otp',
            'expires_in_minutes' => $this->expiresInMinutes,
        ];
    }
}
