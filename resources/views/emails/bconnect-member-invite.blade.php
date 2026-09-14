@component('emails.bconnect-layout', ['bconnectBrand' => $bconnectBrand])
<h1 style="color:#0f172a; font-size:22px; font-weight:800; margin:0 0 18px;">Welcome to {{ $bconnectBrand['title'] ?? 'B-CONNECT' }}, {{ $user->name }}!</h1>

<p style="font-size:15px; line-height:1.65; margin:0 0 16px; color:#475569;">You have been invited to a B-CONNECT workspace. Your account has been created with a temporary password.</p>

<div style="background:#f1f5f9; border-radius:12px; padding:16px; margin:20px 0;">
    <p style="margin:0 0 8px; font-size:14px; color:#64748b;"><strong>Login Email:</strong> {{ $user->email }}</p>
    <p style="margin:0; font-size:14px; color:#64748b;"><strong>Temporary Password:</strong> <code style="background:#ffffff; padding:4px 8px; border-radius:4px; border:1px solid #e2e8f0;">{{ $password }}</code></p>
</div>

<p style="text-align:center; margin:28px 0;">
    <a href="{{ route('bconnect.login', [], false) }}" style="display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px;">Login Now</a>
</p>

<p style="font-size:15px; line-height:1.65; margin:0; color:#475569;">Please change your password after first login. If you need help, contact <a href="mailto:support@believoo.com" style="color:#0077cc; text-decoration:underline;">support@believoo.com</a>.</p>
@endcomponent
