<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletDebitReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Invoice $invoice
    ) {}

    public function build(): self
    {
        return $this->subject('Receipt: Wallet Auto-Renewal Successful')
            ->view('emails.wallet-debit-receipt');
    }
}
