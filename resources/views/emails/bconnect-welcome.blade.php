@component('emails.bconnect-layout', ['bconnectBrand' => $bconnectBrand])
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">Welcome to {{ $bconnectBrand['title'] ?? 'B-CONNECT' }}, {{ $user->name }}!</h1>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">Your B-Connect workspace has been created successfully. You can now invite team members, manage projects, and raise support tickets.</p>

<p style="text-align:center; margin:28px 0;">
    <a href="{{ route('bconnect.dashboard', [], false) }}" style="display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px;">Go to Workspace</a>
</p>

<p style="font-size:15px; line-height:1.65; margin:0; color:#475569;">If you ever need help, just reply to this email or reach us at <a href="mailto:support@believoo.com" style="color:#0077cc; text-decoration:underline;">support@believoo.com</a>.</p>
@endcomponent
