<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class NewsletterUpdate extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
        public string $updateSubject,
        public string $updateBody
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->updateSubject);
    }

    public function content(): Content
    {
        $html = View::make('emails.newsletter-update-html', [
            'body' => $this->updateBody,
            'unsubscribeUrl' => route('newsletter.unsubscribe', $this->subscriber->unsubscribe_token),
        ])->render();

        return new Content(htmlString: $html);
    }
}
