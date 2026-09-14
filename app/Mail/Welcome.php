<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\View;

class Welcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' — your account is ready',
        );
    }

    public function content(): Content
    {
        $html = View::make('emails.welcome-html', [
            'user' => $this->user,
            'dashboardUrl' => route('client.dashboard'),
        ])->render();

        return new Content(htmlString: $html);
    }
}
