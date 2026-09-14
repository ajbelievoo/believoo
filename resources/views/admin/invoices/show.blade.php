@extends('layouts.admin')

@section('title', 'Invoice - ' . $invoice->invoice_number)

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $invoice->invoice_number }}</h1>
    <p class="page-subtitle">{{ $invoice->user->name ?? '—' }}</p>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <div>
        <div class="data-table" style="margin-bottom: 24px;">
            <div class="table-header"><h3 class="table-title">Invoice Details</h3></div>
            <div style="padding: 24px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Client</div>
                    <div style="font-weight: 600;">{{ $invoice->user->name ?? '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Total Amount</div>
                    <div style="font-weight: 600; color: #00b7ff;">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Tax</div>
                    <div>{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Due Date</div>
                    <div>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</div>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Status</div>
                    <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'warning') }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
                @if($invoice->paid_at)
                <div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px;">Paid At</div>
                    <div>{{ $invoice->paid_at->format('M d, Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>

        @if($invoice->line_items)
        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Line Items</h3></div>
            <table>
                <thead>
                    <tr><th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
                </thead>
                <tbody>
                    @foreach($invoice->line_items as $item)
                    <tr>
                        <td>{{ $item['description'] ?? '—' }}</td>
                        <td>{{ $item['quantity'] ?? 1 }}</td>
                        <td>{{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                        <td>{{ number_format(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div>
        <div class="data-table">
            <div class="table-header"><h3 class="table-title">Update Status</h3></div>
            <div style="padding: 24px;">
                <form action="{{ route('admin.invoices.update-status', $invoice) }}" method="POST">
                    @csrf
                    <select name="status" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); margin-bottom: 12px;">
                        @foreach(['pending','paid','overdue','cancelled'] as $s)
                        <option value="{{ $s }}" {{ $invoice->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
