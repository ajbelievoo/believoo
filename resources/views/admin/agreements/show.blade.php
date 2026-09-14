@extends('layouts.admin')

@section('title', 'Agreement - ' . $agreement->agreement_number)

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $agreement->agreement_number }}</h1>
    <p class="page-subtitle">{{ $agreement->project_name }}</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Agreement Details</h3></div>
            <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Client</div>
                    <div style="font-weight: 600;">{{ $agreement->client->name ?? $agreement->client_name }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Total Amount</div>
                    <div style="font-weight: 600; color: #00b7ff;">{{ $agreement->formatted_total }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Start Date</div>
                    <div>{{ $agreement->start_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">End Date</div>
                    <div>{{ $agreement->end_date?->format('M d, Y') ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Status</div>
                    <span class="badge badge-{{ $agreement->status === 'signed' ? 'success' : ($agreement->status === 'sent' ? 'info' : ($agreement->status === 'cancelled' ? 'danger' : 'warning')) }}">
                        {{ $agreement->status_label }}
                    </span>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Timeline</div>
                    <div>{{ $agreement->timeline_months }} months</div>
                </div>
            </div>
        </div>

        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Invoices</h3></div>
            <table>
                <thead>
                    <tr><th>Invoice</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($agreement->invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->currency }} {{ number_format($invoice->amount, 2) }}</td>
                        <td><span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($invoice->status) }}</span></td>
                        <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center; padding: 30px; color: var(--text-muted);">No invoices</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Update Status</h3></div>
            <div style="padding: 24px;">
                <form action="{{ route('admin.agreements.update-status', $agreement) }}" method="POST">
                    @csrf
                    <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-bottom: 12px;">
                        @foreach(['draft','sent','viewed','signed','cancelled'] as $s)
                        <option value="{{ $s }}" {{ $agreement->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                </form>
            </div>
        </div>

        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Milestones</h3></div>
            <div style="padding: 16px;">
                @forelse($agreement->milestones as $milestone)
                <div style="padding: 12px; border: 1px solid var(--border-color); border-radius: 10px; margin-bottom: 8px;">
                    <div style="font-weight: 600; font-size: 0.9rem;">{{ $milestone->phase_name }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $milestone->currency ?? $agreement->currency }} {{ number_format($milestone->payment_amount, 2) }}</div>
                </div>
                @empty
                <p style="color: var(--text-muted); text-align: center; padding: 20px;">No milestones</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
