<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1a1f2e;
            background: #fff;
        }
        .container { max-width: 800px; margin: 0 auto; padding: 40px; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
            padding-bottom: 30px;
            border-bottom: 3px solid #00b7ff;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .brand-name {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg, #00b7ff 0%, #0099ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }
        .brand-tagline {
            font-size: 12px;
            color: #8b9bb4;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-number {
            font-size: 28px;
            font-weight: 700;
            color: #1a1f2e;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1px;
        }
        .status-paid {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #fff;
        }
        .status-pending {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
            color: #fff;
        }
        .status-overdue {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
        }

        .two-col {
            display: flex;
            justify-content: space-between;
            gap: 60px;
            margin-bottom: 40px;
        }
        .col {
            flex: 1;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #00b7ff;
            margin-bottom: 15px;
        }
        .info-box {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #00b7ff;
        }
        .info-box p {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .info-box strong {
            color: #1a1f2e;
            font-weight: 600;
        }
        .info-box .name {
            font-size: 18px;
            font-weight: 700;
            color: #1a1f2e;
            margin-bottom: 10px;
        }

        .service-details {
            background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%);
            color: #fff;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 40px;
        }
        .service-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .service-item {
            text-align: center;
        }
        .service-item-label {
            font-size: 11px;
            color: #8b9bb4;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }
        .service-item-value {
            font-size: 18px;
            font-weight: 700;
            color: #00b7ff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        th {
            background: #1a1f2e;
            color: #fff;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        td {
            padding: 20px 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .item-name {
            font-weight: 700;
            color: #1a1f2e;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .item-desc {
            font-size: 13px;
            color: #64748b;
        }

        .totals {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 30px;
            border-radius: 16px;
            margin-top: 30px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 15px;
        }
        .total-row:not(:last-child) {
            border-bottom: 1px solid #e2e8f0;
        }
        .grand-total {
            font-size: 24px;
            font-weight: 800;
            color: #00b7ff;
            padding-top: 20px;
            margin-top: 15px;
            border-top: 3px solid #00b7ff;
        }

        .footer {
            margin-top: 60px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        .footer-brand {
            font-size: 24px;
            font-weight: 800;
            color: #1a1f2e;
            margin-bottom: 10px;
        }
        .footer-text {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 5px;
        }
        .payment-info {
            background: linear-gradient(135deg, #00b7ff 0%, #0099ff 100%);
            color: #fff;
            padding: 25px;
            border-radius: 12px;
            margin-top: 30px;
            text-align: center;
        }
        .payment-info-title {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <div class="brand">
                <div>
                    <div class="brand-name">BELIEVOO</div>
                    <div class="brand-tagline">Cloud Hosting Solutions</div>
                </div>
            </div>
            <div class="invoice-meta">
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <span class="status-badge status-{{ $invoice->status }}">
                    {{ strtoupper($invoice->status) }}
                </span>
            </div>
        </div>

        {{-- Billing Info --}}
        <div class="two-col">
            <div class="col">
                <div class="section-title">Billed To</div>
                <div class="info-box">
                    <div class="name">{{ $user->name }}</div>
                    <p>{{ $user->email }}</p>
                    @if($user->phone)
                        <p>{{ $user->phone }}</p>
                    @endif
                    @if($user->whmcs_client_id)
                        <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                            <strong>Client ID:</strong> {{ $user->whmcs_client_id }}
                        </p>
                    @endif
                </div>
            </div>
            <div class="col">
                <div class="section-title">Invoice Details</div>
                <div class="info-box">
                    <p><strong>Invoice Date:</strong> {{ $invoice_date }}</p>
                    <p><strong>Due Date:</strong> {{ $due_date }}</p>
                    @if($paid_date)
                        <p><strong>Paid Date:</strong> {{ $paid_date }}</p>
                    @endif
                    <p><strong>Order ID:</strong> {{ $invoice->order_id }}</p>
                </div>
            </div>
        </div>

        {{-- Service Details --}}
        <div class="service-details">
            <div class="service-title">
                <i class="fas fa-server" style="margin-right: 10px;"></i>
                {{ $invoice->plan_name }}
            </div>
            <div class="service-grid">
                <div class="service-item">
                    <div class="service-item-label">Service Type</div>
                    <div class="service-item-value">VPS Hosting</div>
                </div>
                <div class="service-item">
                    <div class="service-item-label">Billing Cycle</div>
                    <div class="service-item-value">Monthly</div>
                </div>
                <div class="service-item">
                    <div class="service-item-label">VPS Count</div>
                    <div class="service-item-value">{{ count($user->getVpsIdsArray()) }}</div>
                </div>
            </div>
        </div>

        {{-- Invoice Items --}}
        <div class="section-title">Service Charges</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 60%;">Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="item-name">{{ $invoice->service_description }}</div>
                        <div class="item-desc">
                            {{ $invoice->plan_name }} - Monthly Subscription
                            @if($user->getVpsIdsArray())
                                <br>VPS IDs: {{ implode(', ', $user->getVpsIdsArray()) }}
                            @endif
                        </div>
                    </td>
                    <td style="text-align: right; font-size: 18px; font-weight: 700;">
                        {{ $settings['currency_symbol'] ?? '₹' }}{{ number_format($invoice->amount, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="total-row">
                <span>Subtotal</span>
                <span style="font-weight: 600;">{{ $settings['currency_symbol'] ?? '₹' }}{{ number_format($invoice->amount, 2) }}</span>
            </div>
            @if($settings['gst_number'] ?? false)
                <div class="total-row">
                    <span>GST (18%)</span>
                    <span style="font-weight: 600;">{{ $settings['currency_symbol'] ?? '₹' }}{{ number_format($invoice->amount * 0.18, 2) }}</span>
                </div>
            @endif
            <div class="total-row grand-total">
                <span>Total Due</span>
                <span>{{ $settings['currency_symbol'] ?? '₹' }}{{ number_format($invoice->amount + ($settings['gst_number'] ? $invoice->amount * 0.18 : 0), 2) }}</span>
            </div>
        </div>

        {{-- Payment Info --}}
        @if($invoice->status === 'paid')
            <div class="payment-info">
                <div class="payment-info-title">Payment Received</div>
                <p>Thank you for your payment! Your service is now active.</p>
                @if($invoice->payment_method)
                    <p style="margin-top: 10px; font-size: 13px; opacity: 0.9;">
                        Paid via {{ ucfirst($invoice->payment_method) }}
                        @if($invoice->transaction_id)
                            | Transaction: {{ $invoice->transaction_id }}
                        @endif
                    </p>
                @endif
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-brand">BELIEVOO</div>
            <div class="footer-text">{{ $settings['company_address'] ?? 'Your Company Address' }}</div>
            <div class="footer-text">
                Email: {{ $settings['company_email'] ?? 'billing@believoo.com' }} | 
                Website: {{ $settings['company_website'] ?? 'https://believoo.com' }}
            </div>
            @if($settings['gst_number'] ?? false)
                <div class="footer-text" style="margin-top: 10px;">GST Number: {{ $settings['gst_number'] }}</div>
            @endif
            <div class="footer-text" style="margin-top: 20px; font-style: italic;">
                Thank you for choosing Believoo for your hosting needs!
            </div>
        </div>
    </div>
</body>
</html>
