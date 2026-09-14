<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 13px; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #00b7ff; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: 800; color: #0f172a; }
        .logo span { color: #00b7ff; }
        .invoice-title { font-size: 28px; font-weight: 700; color: #0f172a; }
        .meta { margin-bottom: 30px; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .meta-label { color: #64748b; }
        .meta-value { font-weight: 700; }
        .bill-to { background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 30px; }
        .bill-to h3 { margin: 0 0 8px; font-size: 14px; color: #64748b; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #0f172a; color: #fff; text-align: left; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        .totals { width: 300px; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .totals-row.total { font-size: 16px; font-weight: 800; border-top: 2px solid #0f172a; border-bottom: none; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 4px; font-weight: 700; font-size: 12px; text-transform: uppercase; }
        .status.paid { background: #dcfce7; color: #166534; }
        .status.pending { background: #fef9c3; color: #854d0e; }
        .status.overdue { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 11px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">B-<span>CONNECT</span></div>
        <div class="invoice-title">INVOICE</div>
    </div>

    <div class="meta">
        <div class="meta-row">
            <div><span class="meta-label">Invoice Number:</span> <span class="meta-value">{{ $invoice->invoice_number }}</span></div>
            <div><span class="meta-label">Status:</span> <span class="status {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></div>
        </div>
        <div class="meta-row">
            <div><span class="meta-label">Issue Date:</span> <span class="meta-value">{{ $invoice->created_at->format('M d, Y') }}</span></div>
            @if($invoice->paid_at)<div><span class="meta-label">Paid Date:</span> <span class="meta-value">{{ $invoice->paid_at->format('M d, Y') }}</span></div>@endif
        </div>
    </div>

    <div class="bill-to">
        <h3>Bill To</h3>
        <div class="meta-value">{{ $company->name }}</div>
        @if($client && $client->user)<div>{{ $client->user->name }} ({{ $client->user->email }})</div>@endif
    </div>

    <table>
        <thead>
            <tr><th>Description</th><th>Amount</th></tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->description }}</td>
                <td>₹{{ number_format($invoice->amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row"><span>Subtotal</span><span>₹{{ number_format($invoice->amount, 2) }}</span></div>
        <div class="totals-row"><span>Tax</span><span>₹0.00</span></div>
        <div class="totals-row total"><span>Total</span><span>₹{{ number_format($invoice->amount, 2) }}</span></div>
    </div>

    <div class="footer">
        Thank you for using B-CONNECT by Believoo. For support, contact support@believoo.com
    </div>
</body>
</html>
