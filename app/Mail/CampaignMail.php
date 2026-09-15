<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $content;
    public ?string $unsubscribeUrl;

    public function __construct(
        public string $subjectLine,
        string $content,
        ?string $unsubscribeUrl = null,
        public ?string $fromName = null,
        public ?string $fromEmail = null
    ) {
        $this->content = $content;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    public function build()
    {
        return $this
            ->from($this->fromEmail ?? config('mail.from.address'), $this->fromName ?? config('mail.from.name'))
            ->subject($this->subjectLine)
            ->view('emails.campaign-html')
            ->with([
                'content' => $this->content,
                'subject' => $this->subjectLine,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
