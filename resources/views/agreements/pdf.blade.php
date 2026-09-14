<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $agreement->title }} - {{ $agreement->agreement_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 40px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #6366f1;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: -0.5px;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
        .header .agreement-number {
            color: #6366f1;
            font-weight: 700;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6366f1;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .parties {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }
        .party-box {
            flex: 1;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .party-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .party-name {
            font-size: 16px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .party-detail {
            font-size: 11px;
            color: #6b7280;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            text-align: left;
            padding: 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #f3f4f6;
        }
        .total-row {
            background: #eef2ff;
            font-weight: 700;
        }
        .total-row td {
            color: #6366f1;
        }
        .milestone {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .milestone-number {
            width: 30px;
            height: 30px;
            background: #6366f1;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
            margin-right: 15px;
        }
        .milestone-content {
            flex: 1;
        }
        .milestone-title {
            font-weight: 700;
            color: #1a1a1a;
        }
        .milestone-desc {
            font-size: 10px;
            color: #6b7280;
        }
        .milestone-amount {
            text-align: right;
        }
        .milestone-price {
            font-size: 14px;
            font-weight: 700;
            color: #1a1a1a;
        }
        .signatures {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-grid {
            display: flex;
            gap: 40px;
        }
        .signature-box {
            flex: 1;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
        }
        .signature-line {
            margin-top: 30px;
            border-top: 2px solid #1a1a1a;
            padding-top: 10px;
        }
        .signature-name {
            font-weight: 700;
            font-size: 14px;
            color: #6366f1;
        }
        .signature-date {
            font-size: 10px;
            color: #6b7280;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-signed {
            background: #d1fae5;
            color: #059669;
        }
        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-gray { color: #6b7280; }
        .text-primary { color: #6366f1; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-4 { margin-top: 16px; }
        .text-sm { font-size: 11px; }
        .text-lg { font-size: 14px; }
        .text-xl { font-size: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $agreement->title }}</h1>
        <p class="agreement-number">Agreement #{{ $agreement->agreement_number }}</p>
        <span class="status-badge {{ $agreement->status == 'signed' ? 'status-signed' : 'status-pending' }}">
            {{ $agreement->status_label }}
        </span>
    </div>

    <div class="parties">
        <div class="party-box">
            <p class="party-label">Service Provider</p>
            <p class="party-name">{{ $agreement->service_provider_name }}</p>
            <p class="party-detail">{{ $agreement->lead_developer }}</p>
        </div>
        <div class="party-box">
            <p class="party-label">Client</p>
            <p class="party-name">{{ $agreement->client_name }}</p>
            <p class="party-detail">{{ $agreement->client->email }}</p>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">1. Project Overview</h2>
        <p>{!! strip_tags($agreement->project_overview) !!}</p>
    </div>

    @if($agreement->workItems->count() > 0)
    <div class="section">
        <h2 class="section-title">2. Itemized Costing</h2>
        <table>
            <thead>
                <tr>
                    <th>Work Item</th>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agreement->workItems as $item)
                <tr>
                    <td class="font-bold">{{ $item->item_name }}</td>
                    <td class="text-gray text-sm">{{ $item->description }}</td>
                    <td class="text-right font-bold">${{ number_format($item->amount, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="text-primary">TOTAL PROJECT VALUE</td>
                    <td class="text-right text-xl">${{ number_format($agreement->total_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    @if($agreement->technical_specs)
    <div class="section">
        <h2 class="section-title">3. Technical Specifications</h2>
        <p>{!! strip_tags($agreement->technical_specs) !!}</p>
    </div>
    @endif

    @if($agreement->milestones->count() > 0)
    <div class="section">
        <h2 class="section-title">4. Timeline & Milestones ({{ $agreement->timeline_months }} Months)</h2>
        @foreach($agreement->milestones as $milestone)
        <div class="milestone">
            <div class="milestone-number">{{ $milestone->timeline_month }}</div>
            <div class="milestone-content">
                <p class="milestone-title">{{ $milestone->phase_name }}</p>
                <p class="milestone-desc">{{ $milestone->description }}</p>
            </div>
            <div class="milestone-amount">
                <p class="milestone-price">${{ number_format($milestone->payment_amount, 2) }}</p>
                <span class="status-badge {{ $milestone->status == 'paid' ? 'status-signed' : 'status-pending' }}">
                    {{ $milestone->status }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($agreement->payment_terms)
    <div class="section">
        <h2 class="section-title">5. Payment Terms</h2>
        <p>{!! strip_tags($agreement->payment_terms) !!}</p>
        @if($agreement->upfront_amount > 0)
        <p class="mt-4 text-primary font-bold">
            Upfront Deposit Required: ${{ number_format($agreement->upfront_amount, 2) }}
        </p>
        @endif
    </div>
    @endif

    @if($agreement->deliverables)
    <div class="section">
        <h2 class="section-title">6. Deliverables</h2>
        <p>{!! strip_tags($agreement->deliverables) !!}</p>
    </div>
    @endif

    @if($agreement->support_terms)
    <div class="section">
        <h2 class="section-title">7. Support & Maintenance</h2>
        <p>{!! strip_tags($agreement->support_terms) !!}</p>
    </div>
    @endif

    @if($agreement->additional_terms)
    <div class="section">
        <h2 class="section-title">8. Additional Terms</h2>
        <p>{!! strip_tags($agreement->additional_terms) !!}</p>
    </div>
    @endif

    <div class="signatures">
        <h2 class="section-title">Signatures</h2>
        <div class="signature-grid">
            <div class="signature-box">
                <p class="party-label">For Service Provider</p>
                <p class="party-name">{{ $agreement->service_provider_name }}</p>
                <div class="signature-line">
                    @if($agreement->admin_signature_data)
                        <p class="signature-name">{{ $agreement->admin_signature_data }}</p>
                    @else
                        <p style="color: #9ca3af; font-style: italic;">Pending signature</p>
                    @endif
                </div>
            </div>
            <div class="signature-box">
                <p class="party-label">For Client</p>
                <p class="party-name">{{ $agreement->client_name }}</p>
                <div class="signature-line">
                    @if($agreement->client_signature_data)
                        <p class="signature-name">{{ $agreement->client_signature_data }}</p>
                        <p class="signature-date">Signed on {{ $agreement->client_signed_at?->format('F d, Y') }}</p>
                    @else
                        <p style="color: #d97706; font-style: italic;">Waiting for signature</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($agreement->client_signature_data && $agreement->client_signature_certificate_id)
    <!-- Digital Signature Certificate -->
    <div class="section" style="page-break-inside: avoid;">
        <h2 class="section-title">Digital Signature Certificate</h2>
        <div style="background: #f0fdf4; border: 2px solid #22c55e; border-radius: 8px; padding: 20px; margin-top: 15px;">
            <div style="text-align: center; margin-bottom: 15px;">
                <div style="display: inline-block; background: #22c55e; color: white; padding: 8px 20px; border-radius: 20px; font-weight: 700; font-size: 12px;">
                    ✓ VERIFIED DIGITAL SIGNATURE
                </div>
            </div>
            <table style="width: 100%; font-size: 11px;">
                <tr>
                    <td style="padding: 8px; color: #6b7280; width: 40%;">Certificate ID:</td>
                    <td style="padding: 8px; font-weight: 700; color: #166534; font-family: monospace;">{{ $agreement->client_signature_certificate_id }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; color: #6b7280;">Signed By:</td>
                    <td style="padding: 8px; font-weight: 700;">{{ $agreement->client_name }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; color: #6b7280;">Date & Time:</td>
                    <td style="padding: 8px; font-weight: 700;">{{ $agreement->client_signed_at?->format('F d, Y \a\t h:i A') }} UTC</td>
                </tr>
                <tr>
                    <td style="padding: 8px; color: #6b7280;">IP Address:</td>
                    <td style="padding: 8px; font-weight: 700; font-family: monospace;">{{ $agreement->client_signature_ip ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; color: #6b7280;">Browser:</td>
                    <td style="padding: 8px; font-weight: 700; font-size: 10px; word-break: break-all;">{{ $agreement->client_signature_user_agent ?? 'N/A' }}</td>
                </tr>
            </table>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #bbf7d0; text-align: center;">
                <p style="font-size: 10px; color: #166534; margin: 0;">
                    This document has been electronically signed in accordance with the Information Technology Act.
                    The signature and associated data above constitute a legally binding agreement.
                </p>
            </div>
        </div>
    </div>
    @endif

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #9ca3af;">
        <p>This agreement was generated on {{ now()->format('F d, Y') }} by Believoo.</p>
    </div>
</body>
</html>
