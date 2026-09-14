<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowBalanceWarning extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public float $requiredAmount
    ) {}

    public function build(): self
    {
        return $this->subject('Action Required: Low Wallet Balance - Renewal in 3 Days')
            ->view('emails.low-balance-warning');
    }
}
