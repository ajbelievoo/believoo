<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class BconnectNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $heading,
        public array $lines,
        public ?string $url = null,
        public ?string $button = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            from: new \Illuminate\Mail\Mailables\Address('bconnect@believoo.com', 'Bmydesk by Believoo'),
            replyTo: [new \Illuminate\Mail\Mailables\Address('support@believoo.com', 'Believoo Support')],
        );
    }

    public function content(): Content
    {
        $html = View::make('emails.bconnect-notification', [
            'heading' => $this->heading,
            'lines' => $this->lines,
            'url' => $this->url,
            'button' => $this->button,
            'bconnectBrand' => \App\Helpers\BconnectHelper::brandData(),
        ])->render();

        return new Content(htmlString: $html);
    }
}
