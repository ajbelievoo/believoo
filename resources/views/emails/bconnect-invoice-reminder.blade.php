<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Reminder</title>
    <style>
        body { margin: 0; padding: 0; background: #0f172a; font-family: Arial, sans-serif; color: #e2e8f0; }
        .container { max-width: 600px; margin: 0 auto; background: #1e293b; border-radius: 12px; overflow: hidden; }
        .header { background: #0b1220; padding: 30px; text-align: center; border-bottom: 1px solid rgba(0,183,255,0.2); }
        .header h1 { margin: 0; color: #00b7ff; font-size: 24px; }
        .body { padding: 30px; }
        .amount { font-size: 32px; font-weight: 800; color: #f59e0b; margin: 20px 0; }
        .details { background: #0f172a; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .details p { margin: 6px 0; color: #cbd5e1; }
        .button { display: inline-block; padding: 14px 28px; background: #00b7ff; color: #0f172a; text-decoration: none; font-weight: 700; border-radius: 6px; margin-top: 20px; }
        .footer { padding: 20px; text-align: center; color: #64748b; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice Reminder</h1>
        </div>
        <div class="body">
            <p>Hi {{ $invoice->client?->user?->name ?? 'there' }},</p>
            <p>This is a friendly reminder that invoice <strong>{{ $invoice->invoice_number }}</strong> is still pending.</p>

            <div class="details">
                <p><strong>Description</strong> {{ $invoice->description }}</p>
                <p><strong>Created</strong> {{ $invoice->created_at->format('M d, Y') }}</p>
                <p><strong>Due Date</strong> {{ $invoice->due_at?->format('M d, Y') ?? 'On receipt' }}</p>
            </div>

            <div class="amount">₹{{ number_format($invoice->amount, 2) }}</div>

            <p>Please find the attached invoice PDF. Pay as soon as possible to avoid service interruption.</p>
            <a href="{{ $payUrl }}" class="button">Pay Now</a>
        </div>
        <div class="footer">
            Bmydesk by Believoo &bull; support@believoo.com
        </div>
    </div>
</body>
</html>
