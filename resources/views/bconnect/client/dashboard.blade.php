@extends('bconnect.layout')
@section('title', 'Client Portal')
@section('content')
<div class="mb-8">
    <h2 class="text-2xl font-bold text-white mb-1">Welcome, {{ $member->user->name }}</h2>
    <p class="text-slate-400 text-sm">Here is your project overview from {{ $company->name }}.</p>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bc-card p-5">
        <div class="text-3xl font-black text-cyan-400">{{ $projects->count() }}</div>
        <div class="text-slate-400 text-sm">Projects</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-3xl font-black text-pink-400">{{ $tickets->where('status', '!=', 'closed')->count() }}</div>
        <div class="text-slate-400 text-sm">Open Tickets</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-3xl font-black text-amber-400">{{ $invoices->where('status', 'pending')->count() }}</div>
        <div class="text-slate-400 text-sm">Pending Invoices</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-3xl font-black text-green-400">₹{{ number_format($invoices->where('status', 'paid')->sum('amount'), 2) }}</div>
        <div class="text-slate-400 text-sm">Paid</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Your Projects</h3>
        <div class="space-y-3">
            @forelse($projects as $p)
            <a href="{{ route('bconnect.projects.show', $p->id) }}" class="flex items-center justify-between p-4 bg-slate-800/50 rounded-xl hover:bg-slate-800 transition">
                <div>
                    <div class="font-bold text-white">{{ $p->name }}</div>
                    <div class="text-sm text-slate-400">Client: {{ $p->client?->user?->name ?? '—' }}</div>
                </div>
                <span class="bc-badge {{ $p->status == 'active' ? 'bc-badge-green' : 'bc-badge-slate' }}">{{ ucfirst($p->status) }}</span>
            </a>
            @empty
            <div class="bc-empty">No projects assigned yet.</div>
            @endforelse
        </div>
    </div>

    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Recent Tickets</h3>
        <table class="bc-table">
            <thead><tr><th>Ticket</th><th>Project</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($tickets->take(5) as $t)
                <tr>
                    <td><a href="{{ route('bconnect.tickets.show', $t->id) }}" class="text-cyan-400 hover:underline font-medium">{{ $t->title }}</a></td>
                    <td class="text-slate-400">{{ $t->project?->name ?? '—' }}</td>
                    <td><span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span></td>
                </tr>
                @empty
                <tr><td colspan="3" class="bc-empty">No tickets.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($tickets->count() > 5)
        <a href="{{ route('bconnect.tickets') }}" class="text-cyan-400 text-sm hover:underline mt-3 inline-block">View all tickets</a>
        @endif
    </div>
</div>

<div class="bc-card p-6">
    <h3 class="font-bold mb-4">Your Invoices</h3>
    <table class="bc-table">
        <thead><tr><th>Number</th><th>Amount</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($invoices as $i)
            <tr>
                <td class="font-medium">{{ $i->invoice_number }}</td>
                <td>₹{{ number_format($i->amount, 2) }}</td>
                <td class="text-slate-400">{{ $i->due_at?->format('M d, Y') ?? '—' }}</td>
                <td><span class="bc-badge {{ $i->status == 'paid' ? 'bc-badge-green' : 'bc-badge-amber' }}">{{ ucfirst($i->status) }}</span></td>
                <td class="flex gap-2">
                    <a href="{{ route('bconnect.billing.download', $i->id) }}" class="bc-btn bc-btn-secondary p-1.5 text-xs"><i class="fas fa-download"></i></a>
                    @if($i->status != 'paid')
                    <a href="{{ route('bconnect.billing.pay', $i->id) }}" class="bc-btn bc-btn-primary py-1.5 px-2.5 text-xs"><i class="fas fa-credit-card mr-1"></i>Pay</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="bc-empty">No invoices.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $invoices->links() }}
</div>
@endsection
