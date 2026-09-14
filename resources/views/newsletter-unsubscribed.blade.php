<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed - {{ config('app.name') }}</title>
    <style>
        body { font-family: -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f4f9fd; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 16px; padding: 48px; max-width: 460px; text-align: center; box-shadow: 0 12px 40px -10px rgba(1,42,82,.15); }
        .logo { max-width: 180px; margin-bottom: 20px; }
        h1 { color: #0b2545; font-size: 22px; margin: 0 0 10px; }
        p { color: #5b7285; font-size: 15px; line-height: 1.6; }
        a { color: #0077cc; }
        .btn { display: inline-block; margin-top: 18px; padding: 12px 28px; background: linear-gradient(135deg,#00b7ff,#0066ff); color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/believoo-email-logo.png') }}" alt="{{ config('app.name') }}" class="logo">
        <h1>You've been unsubscribed</h1>
        <p><b>{{ $email }}</b> will no longer receive update emails from {{ config('app.name') }}.</p>
        <p>Changed your mind? You can re-subscribe anytime from our website.</p>
        <a href="{{ config('app.url') }}" class="btn">Back to {{ config('app.name') }}</a>
    </div>
</body>
</html>
