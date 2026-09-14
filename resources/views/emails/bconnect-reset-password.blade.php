@component('emails.bconnect-layout', ['bconnectBrand' => $bconnectBrand])
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">Hello {{ $name ?? 'there' }},</h1>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">You are receiving this email because we received a password reset request for your B-Connect account.</p>

<p style="text-align:center; margin:28px 0;">
    <a href="{{ $url }}" style="display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px;">Reset Password</a>
</p>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">This password reset link will expire in {{ $expire }} minutes.</p>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">If you did not request a password reset, no further action is required.</p>

<p style="font-size:15px; line-height:1.65; margin:0; color:#475569;">Regards,<br><strong style="color:#0f172a;">{{ $bconnectBrand['title'] ?? 'B-CONNECT' }} Team</strong></p>
@endcomponent
