@extends('bconnect.layout')
@section('title', 'Recurring Invoices')
@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <h3 class="font-bold mb-4">Recurring invoices</h3>
        <p class="text-xs text-slate-500 mb-4">Invoices generate automatically on each run date and are emailed to the client.</p>
        <table class="bc-table">
            <thead><tr><th>Client</th><th>Amount</th><th>Interval</th><th>Next run</th><th>Active</th></tr></thead>
            <tbody>
            @forelse($list as $rec)
            <tr>
                <td>{{ $clients->firstWhere('id', $rec->client_id)?->user?->name ?? '—' }}</td>
                <td>₹{{ number_format($rec->amount, 2) }}{{ $rec->tax_rate ? ' +'.$rec->tax_rate.'%' : '' }}</td>
                <td class="text-slate-400">{{ ucfirst($rec->interval) }}</td>
                <td class="text-slate-400">{{ $rec->next_run }}</td>
                <td>
                    <form method="POST" action="{{ route('bconnect.recurring.toggle', $rec->id) }}">@csrf
                        <button class="bc-badge {{ $rec->active ? 'bc-badge-green' : 'bc-badge-slate' }}">{{ $rec->active ? 'ON' : 'OFF' }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="bc-empty">No recurring invoices.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Schedule recurring invoice</h3>
        <form method="POST" action="{{ route('bconnect.recurring.store') }}" class="space-y-4">@csrf
            <select name="client_id" required class="bc-input"><option value="">Select client</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select>
            <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required class="bc-input">
            <input type="number" step="0.01" name="tax_rate" placeholder="Tax %" min="0" max="100" class="bc-input">
            <select name="interval" required class="bc-input"><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="yearly">Yearly</option></select>
            <input type="date" name="next_run" required class="bc-input" value="{{ now()->toDateString() }}">
            <textarea name="description" rows="2" placeholder="Description" class="bc-input"></textarea>
            <button class="bc-btn bc-btn-primary w-full"><i class="fas fa-repeat"></i>Schedule</button>
        </form>
    </div>
</div>
@endsection
