@component('emails.layout')
{!! $body !!}

<p style="font-size:13px; color:#64748b; margin:24px 0 0; padding-top:16px; border-top:1px solid #e2e8f0;">
    You're receiving this because you subscribed to {{ config('app.name') }} updates.
    <a href="{{ $unsubscribeUrl }}" style="color:#0077cc; text-decoration:underline;">Unsubscribe</a> anytime.
</p>
@endcomponent
