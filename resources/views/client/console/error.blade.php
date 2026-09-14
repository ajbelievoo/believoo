<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Console Error - BelieVoo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            max-width: 500px;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: rgba(239, 68, 68, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        p {
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #00b7ff, #7c3aed);
            color: #fff;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 183, 255, 0.3);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        .support {
            margin-top: 32px;
            font-size: 12px;
            color: #64748b;
        }
        .support a {
            color: #00b7ff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Console Connection Failed</h1>
        <p>{{ $message ?? 'Unable to establish a connection to the VM console. This could be due to the session expiring or the VM being powered off.' }}</p>
        
        <div class="actions">
            <button onclick="window.location.reload()" class="btn btn-primary">
                Try Again
            </button>
            <a href="{{ route('client.dashboard', ['tab' => 'hosting']) }}" class="btn btn-secondary">
                Back to Dashboard
            </a>
        </div>

        <div class="support">
            Need help? <a href="{{ route('contact') }}">Contact Support</a>
        </div>
    </div>

    <script>
        // Auto-retry once after 3 seconds
        setTimeout(() => {
            if (document.visibilityState === 'visible') {
                console.log('Auto-retrying connection...');
            }
        }, 3000);
    </script>
</body>
</html>
