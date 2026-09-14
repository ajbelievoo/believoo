@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
<div class="page-header">
    <h1 class="page-title">Invoices</h1>
    <p class="page-subtitle">Manage all invoices</p>
</div>

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title">All Invoices</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Client</th>
                <th>Amount</th>
                <th>Type</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td style="font-weight: 600;">{{ $invoice->invoice_number }}</td>
                <td>{{ $invoice->user->name ?? '—' }}</td>
                <td style="font-weight: 600; color: #00b7ff;">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type ?? '—')) }}</td>
                <td>
                    <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : ($invoice->status === 'cancelled' ? 'danger' : 'warning')) }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </td>
                <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</td>
                <td>
                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-file-invoice-dollar" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No invoices found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($invoices->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
