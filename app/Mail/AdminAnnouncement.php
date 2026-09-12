<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\EmailPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AdminAnnouncement extends Mailable
{
    use Queueable, SerializesModels;

    public string $body;
    public string $plainText;
    public string $unsubscribeUrl;

    public function __construct(
        public Announcement $announcement,
        public string $toEmail,
        public ?AnnouncementRecipient $recipient = null,
        public string $userLocale = 'en',
    ) {
        $this->body = $this->buildBody();
        $this->plainText = strip_tags($this->body);
        $this->unsubscribeUrl = $this->buildUnsubscribeUrl();
    }

    public function envelope(): Envelope
    {
        $variant = $this->recipient?->variant ?: 'A';

        return new Envelope(
            subject: $this->announcement->titleForVariant($variant),
            from: new Address(config('mail.from.address'), config('mail.from.name', 'Believoo')),
            replyTo: [new Address('support@believoo.com', 'Believoo Support')],
        );
    }

    public function content(): Content
    {
        $html = View::make('emails.announcement', [
            'announcement' => $this->announcement,
            'body' => $this->body,
            'unsubscribeUrl' => $this->unsubscribeUrl,
        ])->render();

        return new Content(htmlString: $html, text: 'emails.announcement-text');
    }

    public function attachments(): array
    {
        if (! $this->announcement->attachment) {
            return [];
        }

        $path = storage_path('app/public/' . $this->announcement->attachment);
        if (! file_exists($path)) {
            return [];
        }

        return [
            Attachment::fromPath($path),
        ];
    }

    private function buildBody(): string
    {
        $variant = $this->recipient?->variant ?: 'A';
        $message = $this->announcement->messageForLocaleAndVariant($this->userLocale, $variant)
            ?? $this->announcement->message;

        // Track clicks on links inside the message
        if ($this->recipient) {
            $message = $this->trackClicks($message);
        }

        $pixel = $this->recipient ? $this->trackingPixel() : '';

        return $message . $pixel;
    }

    private function trackClicks(string $html): string
    {
        $route = route('track.announcement.click', [
            'announcement' => $this->announcement->id,
            'recipient' => $this->recipient->id,
        ], false);

        return preg_replace_callback(
            '/href=["\'](https?:\/\/[^"\']+)["\']/i',
            function ($matches) use ($route) {
                $url = urlencode($matches[1]);
                return 'href="' . url($route . '?url=' . $url) . '"';
            },
            $html
        ) ?? $html;
    }

    private function trackingPixel(): string
    {
        $url = route('track.announcement.open', [
            'announcement' => $this->announcement->id,
            'recipient' => $this->recipient->id,
        ], false);

        return '<img src="' . url($url) . '" alt="" width="1" height="1" style="display:block;" />';
    }

    private function buildUnsubscribeUrl(): string
    {
        return URL::signedRoute('announcements.unsubscribe', [
            'email' => $this->toEmail,
        ], now()->addDays(30));
    }
}
