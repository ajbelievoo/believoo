@component('emails.layout')
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">Welcome to {{ config('app.name') }}, {{ $user->name }}!</h1>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">Your account has been created successfully. You're now part of the Believoo family — your <strong style="color:#0f172a;">Growth Scale Partner</strong> for hosting, domains, and digital services.</p>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">Here's what you can do right away:</p>

<ul style="font-size:15px; line-height:1.65; margin:0 0 22px; padding-left:20px; color:#475569;">
    <li><strong style="color:#0f172a;">Order services</strong> — VPS hosting, domains, streaming & more</li>
    <li><strong style="color:#0f172a;">Track projects</strong> — manage your requests and agreements</li>
    <li><strong style="color:#0f172a;">Get support</strong> — our team is always ready to help</li>
</ul>

<p style="text-align:center; margin:28px 0;">
    <a href="{{ $dashboardUrl }}" style="display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px;">Go to Your Dashboard</a>
</p>

<p style="font-size:15px; line-height:1.65; margin:0 0 8px; color:#475569;">If you ever need help, just reply to this email or reach us at <a href="mailto:support@believoo.com" style="color:#0077cc; text-decoration:underline;">support@believoo.com</a>.</p>

<p style="font-size:15px; line-height:1.65; margin:0; color:#475569;">Welcome aboard!<br><strong style="color:#0f172a;">Team {{ config('app.name') }}</strong></p>
@endcomponent
