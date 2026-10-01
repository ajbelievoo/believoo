@component('emails.bconnect-layout', ['bconnectBrand' => $bconnectBrand])
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">Hello {{ $user->name ?? 'there' }},</h1>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">Your Bmydesk account password was just changed. If you made this change, no further action is required.</p>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">If you did not change your password, please <a href="{{ route('bconnect.forgot-password', [], false) }}" style="color:#0077cc; text-decoration:underline;">reset your password</a> immediately or contact our support team.</p>

<p style="font-size:15px; line-height:1.65; margin:0; color:#475569;">Regards,<br><strong style="color:#0f172a;">{{ $bconnectBrand['title'] ?? 'Bmydesk' }} Team</strong></p>
@endcomponent
