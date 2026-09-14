<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class NewsletterSubscribed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're subscribed to " . config('app.name') . ' updates',
        );
    }

    public function content(): Content
    {
        $html = View::make('emails.newsletter-subscribed-html', [
            'unsubscribeUrl' => route('newsletter.unsubscribe', $this->subscriber->unsubscribe_token),
        ])->render();

        return new Content(htmlString: $html);
    }
}
