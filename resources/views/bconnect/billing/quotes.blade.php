@extends('bconnect.layout')
@section('title', 'Quotes & Estimates')
@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <h3 class="font-bold mb-4">Quotes</h3>
        <table class="bc-table">
            <thead><tr><th>Number</th><th>Client</th><th>Amount</th><th>Tax</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($quotes as $q)
            <tr>
                <td class="font-medium">{{ $q->quote_number }}</td>
                <td class="text-slate-400">{{ $clients->firstWhere('id', $q->client_id)?->user?->name ?? '—' }}</td>
                <td>₹{{ number_format($q->amount, 2) }}</td>
                <td class="text-slate-400">{{ $q->tax_rate ? $q->tax_rate.'%' : '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('bconnect.quotes.status', $q->id) }}" class="inline">@csrf
                        <select name="status" onchange="this.form.submit()" class="bc-input text-xs !w-auto">
                            @foreach(['draft','sent','accepted','declined','converted'] as $s)
                                <option value="{{ $s }}" {{ $q->status === $s ? 'selected' : '' }} {{ $s === 'converted' ? 'disabled' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </form>
                </td>
                <td>
                    @if(!$q->converted_invoice_id && $q->status !== 'declined')
                    <form method="POST" action="{{ route('bconnect.quotes.convert', $q->id) }}" onsubmit="return confirm('Convert to invoice?')">@csrf
                        <button class="bc-btn bc-btn-primary py-1 px-2.5 text-xs">→ Invoice</button>
                    </form>
                    @elseif($q->converted_invoice_id)
                    <span class="text-xs text-green-400">Invoiced</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="bc-empty">No quotes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">New Quote</h3>
        <form method="POST" action="{{ route('bconnect.quotes.store') }}" class="space-y-4">@csrf
            <select name="client_id" required class="bc-input"><option value="">Select client</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select>
            <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required class="bc-input">
            <input type="number" step="0.01" name="tax_rate" placeholder="Tax %" min="0" max="100" class="bc-input">
            <textarea name="description" rows="3" placeholder="Scope / description" class="bc-input"></textarea>
            <button class="bc-btn bc-btn-primary w-full"><i class="fas fa-plus"></i>Create quote</button>
        </form>
    </div>
</div>
@endsection
