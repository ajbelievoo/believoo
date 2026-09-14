<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bconnectBrand['title'] ?? 'B-CONNECT' }}</title>
    <style type="text/css">
        body { margin:0; padding:0; background:#f4f9fd; }
        table { border-collapse:collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { border:0; outline:none; text-decoration:none; -ms-interpolation-mode:bicubic; }
        .btn { display:inline-block; padding:14px 32px; border-radius:999px; background:linear-gradient(135deg,#00b7ff,#0066ff); color:#ffffff; font-weight:700; text-decoration:none; font-size:15px; }
        .btn:hover { background:linear-gradient(135deg,#009de8,#0052cc); }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f4f9fd; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif; color:#475569; line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f9fd;">
        <tr>
            <td align="center" style="padding:0;">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:14px; box-shadow:0 8px 30px -10px rgba(1,42,82,.15); margin:32px auto; overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0a0a1a; background-image:linear-gradient(135deg,#0a0a1a 0%,#101c3a 55%,#0a2540 100%); padding:34px 30px 30px; text-align:center;">
                            <a href="https://bc.believoo.com" style="display:inline-block;">
                                <img src="{{ $bconnectBrand['logo'] ?? 'https://bc.believoo.com/images/bconnect-logo.png' }}" alt="{{ $bconnectBrand['title'] ?? 'B-CONNECT' }}" style="max-width:180px; height:auto; display:block; margin:0 auto;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; background:linear-gradient(90deg,#00b7ff,#0066ff); line-height:4px; font-size:4px;">&nbsp;</td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:42px 40px 32px;">
                            @yield('content')
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px 40px; text-align:center; border-top:1px solid #e2e8f0;">
                            <p style="margin:0 0 8px; color:#94a3b8; font-size:12px;">© {{ date('Y') }} {{ $bconnectBrand['title'] ?? 'B-CONNECT' }} — a Believoo workspace</p>
                            <p style="margin:0 0 8px; color:#94a3b8; font-size:12px;">
                                <a href="https://bc.believoo.com" style="color:#0077cc; text-decoration:underline;">Workspace</a> &middot;
                                <a href="mailto:support@believoo.com" style="color:#0077cc; text-decoration:underline;">Support</a> &middot;
                                <a href="https://support.believoo.com" style="color:#0077cc; text-decoration:underline;">Help Center</a>
                            </p>
                            @isset($footerText)
                            <p style="margin:0; color:#94a3b8; font-size:12px;">{{ $footerText }}</p>
                            @endisset
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
