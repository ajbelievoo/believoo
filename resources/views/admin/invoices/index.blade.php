@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Invoices</h1>
        <p class="page-subtitle">Manage all invoices</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-file-invoice-dollar"></i>All Invoices</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
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
                    <td style="font-weight: 600; color: var(--accent);">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $invoice->invoice_type ?? '—')) }}</td>
                    <td>
                        <span class="badge badge-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : ($invoice->status === 'cancelled' ? 'danger' : 'warning')) }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                    <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</td>
                    <td>
                        <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-secondary btn-sm"><i class="fas fa-eye"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-state"><i class="fas fa-file-invoice-dollar"></i><div>No invoices found</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
    <div class="card-footer">
        {{ $invoices->links() }}
    </div>
    @endif
</div>
@endsection
