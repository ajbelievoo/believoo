<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to PayU...</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .container {
            text-align: center;
            color: white;
            padding: 2rem;
        }
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #00B7FF;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 2rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        p {
            color: #888;
            font-size: 1.1rem;
        }
        .logo {
            font-size: 3rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">💳</div>
        <div class="spinner"></div>
        <h1>Redirecting to PayU</h1>
        <p>Please wait while we securely redirect you to PayU payment gateway...</p>
        <p style="margin-top: 2rem; font-size: 0.9rem; color: #666;">Do not refresh or close this page</p>
    </div>

    <form id="payuForm" action="{{ $base_url }}/_payment" method="POST" style="display: none;">
        <input type="hidden" name="key" value="{{ $key }}">
        <input type="hidden" name="txnid" value="{{ $txnid }}">
        <input type="hidden" name="amount" value="{{ $amount }}">
        <input type="hidden" name="productinfo" value="{{ $productinfo }}">
        <input type="hidden" name="firstname" value="{{ $firstname }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="phone" value="{{ $phone }}">
        <input type="hidden" name="surl" value="{{ $surl }}">
        <input type="hidden" name="furl" value="{{ $furl }}">
        <input type="hidden" name="hash" value="{{ $hash }}">
    </form>

    <script>
        // Auto-submit the form after a short delay to show the loading screen
        setTimeout(function() {
            document.getElementById('payuForm').submit();
        }, 1500);
    </script>
</body>
</html>
