<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class BconnectWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . (\App\Helpers\BconnectHelper::brandData()['title'] ?? 'Bmydesk'),
            from: new Address('bconnect@believoo.com', 'Bmydesk by Believoo'),
            replyTo: [new Address('support@believoo.com', 'Believoo Support')],
        );
    }

    public function content(): Content
    {
        $html = View::make('emails.bconnect-welcome', [
            'user' => $this->user,
            'bconnectBrand' => \App\Helpers\BconnectHelper::brandData(),
        ])->render();

        return new Content(htmlString: $html);
    }
}
