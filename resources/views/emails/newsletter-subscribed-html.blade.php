@component('emails.layout')
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">You're subscribed!</h1>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">Thanks for subscribing to <strong style="color:#0f172a;">{{ config('app.name') }}</strong> updates — you'll now get our latest offers, new services, and important announcements right in your inbox.</p>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; padding:16px 18px; background:#eef7fd; border-left:4px solid #00b7ff; border-radius:0 10px 10px 0;">No action needed. Sit back — we'll keep you posted.</p>

<p style="text-align:center; margin:28px 0;">
    <a href="{{ config('app.url') }}" style="display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px;">Visit {{ config('app.name') }}</a>
</p>

<p style="font-size:13px; color:#64748b; margin:0;">Changed your mind? <a href="{{ $unsubscribeUrl }}" style="color:#0077cc; text-decoration:underline;">Unsubscribe anytime</a>.</p>
@endcomponent
