<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - {{ config('app.name') }}</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: #f5f5f5;
        }
        .payment-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .loading {
            font-size: 1.2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="loading">Loading payment gateway...</div>
    </div>

    <script>
        const cashfree = Cashfree({
            mode: '{{ $environment }}'
        });

        cashfree.checkout({
            paymentSessionId: '{{ $payment_session_id }}',
            redirectTarget: '_self'
        });
    </script>
</body>
</html>
