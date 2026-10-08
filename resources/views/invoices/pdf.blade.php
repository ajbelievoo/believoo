@php($co = \App\Models\Setting::whereIn('key', ['company_legal_name','company_cin','company_pan','company_gstin','company_registered_office','company_address'])->pluck('value','key'))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin: 20px 0;
            color: #1f2937;
        }
        .invoice-details {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            color: #6b7280;
        }
        .value {
            color: #1f2937;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin: 30px 0 15px 0;
            color: #2563eb;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #f3f4f6;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .total-section {
            background: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            border-top: 2px solid #2563eb;
            padding-top: 10px;
            margin-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        .status-paid {
            background: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $co['company_legal_name'] ?? 'BELIEVOO' }}</div>
        <div style="color: #6b7280; margin-top: 5px;">Software Development Agency</div>
        @if($co['company_cin'] ?? false)
            <div style="color: #6b7280; margin-top: 5px; font-size: 12px;">CIN: {{ $co['company_cin'] }}@if($co['company_pan'] ?? false) &nbsp;|&nbsp; PAN: {{ $co['company_pan'] }}@endif@if($co['company_gstin'] ?? false) &nbsp;|&nbsp; GSTIN: {{ $co['company_gstin'] }}@endif</div>
        @endif
        @if(($co['company_registered_office'] ?? $co['company_address'] ?? false))
            <div style="color: #6b7280; margin-top: 5px; font-size: 12px;">{{ $co['company_registered_office'] ?? $co['company_address'] }}</div>
        @endif
    </div>

    <div style="text-align: center; margin-bottom: 30px;">
        <span class="status-badge status-{{ $invoice->status }}">
            {{ strtoupper($invoice->status) }}
        </span>
    </div>

    <div class="invoice-title" style="text-align: center;">INVOICE</div>

    <div class="invoice-details">
        <div class="row">
            <span class="label">Invoice Number:</span>
            <span class="value">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="row">
            <span class="label">Invoice Date:</span>
            <span class="value">{{ ($invoice->invoice_date ?? $invoice->created_at)->format('F d, Y') }}</span>
        </div>
        <div class="row">
            <span class="label">Due Date:</span>
            <span class="value">{{ ($invoice->due_date ?? $invoice->created_at)->format('F d, Y') }}</span>
        </div>
    </div>

    <div style="margin-bottom: 30px;">
        <div class="section-title">Bill To</div>
        <div style="background: #f9fafb; padding: 15px; border-radius: 8px;">
            <strong>{{ $invoice->client->name ?? 'Client' }}</strong><br>
            {{ $invoice->client->email ?? '' }}<br>
            @if($invoice->client && $invoice->client->phone)
                {{ $invoice->client->phone }}<br>
            @endif
            @if($invoice->client && $invoice->client->company)
                {{ $invoice->client->company }}<br>
            @endif
        </div>
    </div>

    <div style="margin-bottom: 30px;">
        <div class="section-title">Project Details</div>
        <div style="background: #f9fafb; padding: 15px; border-radius: 8px;">
            <strong>Project:</strong> {{ $invoice->agreement->project_name ?? $invoice->service_name ?? 'Service' }}<br>
            @if($invoice->milestone)
                <strong>Milestone:</strong> {{ $invoice->milestone->title }}<br>
            @endif
            @if($invoice->agreement)
                <strong>Agreement #:</strong> {{ $invoice->agreement->agreement_number }}
            @endif
        </div>
    </div>

    <div class="section-title">Invoice Items</div>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    @if($invoice->milestone)
                        {{ $invoice->milestone->title }}
                    @else
                        Project Development Services
                    @endif
                    - {{ $invoice->agreement->project_name ?? $invoice->service_name ?? 'Service' }}
                </td>
                <td style="text-align: right;">${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Subtotal:</span>
            <span>${{ number_format($invoice->subtotal, 2) }}</span>
        </div>
        @if($invoice->tax_amount > 0)
        <div class="total-row">
            <span>Tax ({{ $invoice->tax_rate }}%):</span>
            <span>${{ number_format($invoice->tax_amount, 2) }}</span>
        </div>
        @endif
        @if($invoice->discount_amount > 0)
        <div class="total-row">
            <span>Discount:</span>
            <span>-${{ number_format($invoice->discount_amount, 2) }}</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>Total:</span>
            <span>${{ number_format($invoice->total_amount, 2) }}</span>
        </div>
        @if($invoice->amount_paid > 0)
        <div class="total-row" style="color: #16a34a; margin-top: 10px;">
            <span>Amount Paid:</span>
            <span>${{ number_format($invoice->amount_paid, 2) }}</span>
        </div>
        <div class="total-row" style="font-weight: bold;">
            <span>Balance Due:</span>
            <span>${{ number_format($invoice->balance_due, 2) }}</span>
        </div>
        @endif
    </div>

    @if($invoice->notes)
    <div style="margin-top: 30px;">
        <div class="section-title">Notes</div>
        <div style="background: #f9fafb; padding: 15px; border-radius: 8px;">
            {{ $invoice->notes }}
        </div>
    </div>
    @endif

    @if($invoice->terms_conditions)
    <div style="margin-top: 30px;">
        <div class="section-title">Terms & Conditions</div>
        <div style="font-size: 12px; color: #6b7280;">
            {{ $invoice->terms_conditions }}
        </div>
    </div>
    @endif

    <div class="footer">
        <p><strong>Thank you for your business!</strong></p>
        <p>If you have any questions about this invoice, please contact us.</p>
        <p style="margin-top: 10px;">www.believoo.com | support@believoo.com</p>
        @if($co['company_cin'] ?? false)
            <p style="margin-top: 10px;">{{ $co['company_legal_name'] ?? 'Believoo Private Limited' }} · CIN: {{ $co['company_cin'] }} · Incorporated under the Companies Act, 2013, Govt. of India</p>
        @endif
    </div>
</body>
</html>
