<?php

namespace App\Mail;

use App\Models\User;
use App\Models\WalletTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletTopupSuccess extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public WalletTopup $topup
    ) {}

    public function build(): self
    {
        return $this->subject('Wallet Top-up Successful')
            ->view('emails.wallet-topup-success');
    }
}
