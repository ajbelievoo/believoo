<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Wallet Top-up Successful</title>
</head>
<body style="margin:0;padding:0;background:#05070A;color:#ffffff;font-family:system-ui,-apple-system,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#05070A;">
        <tr>
            <td align="center" style="padding:40px 20px;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;background:#0B0F19;border:1px solid rgba(255,255,255,0.08);border-radius:24px;overflow:hidden;">
                    <tr>
                        <td style="padding:40px 32px 24px; text-align:center;">
                            <a href="{{ config('app.url') }}" style="display:inline-block;">
                                <img src="{{ config('app.url') }}/images/believoo-email-logo.png" alt="{{ config('app.name') }}" style="max-width:220px; height:74px; width:auto; display:block; margin:0 auto;">
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="height:4px; background:linear-gradient(90deg,#f59e0b,#fbbf24); line-height:4px; font-size:4px;">&nbsp;</td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;">
                            <div style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.25);border-radius:16px;padding:24px;text-align:center;">
                                <p style="margin:0 0 8px;font-size:12px;text-transform:uppercase;letter-spacing:0.15em;color:#34d399;font-weight:700;">Success</p>
                                <h2 style="margin:0;font-size:22px;font-weight:800;">Wallet Top-up Complete</h2>
                            </div>

                            <div style="margin-top:24px;">
                                <div style="display:flex;justify-content:space-between;padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                                    <span style="color:#8b9bb4;">Amount Added</span>
                                    <span style="font-weight:700;color:#34d399;">₹{{ number_format($topup->amount, 2) }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                                    <span style="color:#8b9bb4;">Transaction ID</span>
                                    <span style="font-weight:700;">{{ $topup->topup_number }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                                    <span style="color:#8b9bb4;">Payment Gateway</span>
                                    <span style="font-weight:700;">{{ ucfirst($topup->payment_gateway) }}</span>
                                </div>
                                <div style="display:flex;justify-content:space-between;padding:16px 0;border-bottom:1px solid rgba(255,255,255,0.06);">
                                    <span style="color:#8b9bb4;">Date</span>
                                    <span style="font-weight:700;">{{ $topup->paid_at ? $topup->paid_at->format('M d, Y h:i A') : now()->format('M d, Y h:i A') }}</span>
                                </div>
                            </div>

                            <p style="margin-top:24px;color:#64748b;font-size:13px;text-align:center;">Your wallet balance has been updated and is ready to use for instant payments.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px 32px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;color:#64748b;font-size:12px;">
                            <p style="margin:0 0 8px;">&copy; {{ date('Y') }} {{ config('app.name') }} — Growth Scale Partner. All rights reserved.</p>
                            <p style="margin:0 0 8px;">
                                <a href="{{ config('app.url') }}" style="color:#8b9bb4; text-decoration:underline;">Website</a> &middot;
                                <a href="mailto:support@believoo.com" style="color:#8b9bb4; text-decoration:underline;">Support</a> &middot;
                                <a href="{{ config('app.url') }}/services" style="color:#8b9bb4; text-decoration:underline;">Services</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
