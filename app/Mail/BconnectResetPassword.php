<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class BconnectResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $url,
        public string $name,
        public int $expire
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password reset for your B-Connect account',
            from: new Address('bconnect@believoo.com', 'Believoo B-Connect'),
            replyTo: [new Address('support@believoo.com', 'Believoo Support')],
        );
    }

    public function content(): Content
    {
        $html = View::make('emails.bconnect-reset-password', [
            'url' => $this->url,
            'name' => $this->name,
            'expire' => $this->expire,
            'bconnectBrand' => \App\Helpers\BconnectHelper::brandData(),
        ])->render();

        return new Content(htmlString: $html);
    }
}
