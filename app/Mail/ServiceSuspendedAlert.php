<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ServiceSuspendedAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?float $requiredAmount = null
    ) {}

    public function build(): self
    {
        return $this->subject('URGENT: Service Suspended Due to Insufficient Funds')
            ->view('emails.service-suspended');
    }
}
