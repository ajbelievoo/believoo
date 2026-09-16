@extends('bconnect.layout')
@section('title', 'Billing')
@section('content')
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-green-400">₹{{ number_format($invoices->where('status','paid')->sum('amount'), 2) }}</div>
        <div class="text-slate-400 text-sm">Paid</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-amber-400">₹{{ number_format($invoices->where('status','pending')->sum('amount'), 2) }}</div>
        <div class="text-slate-400 text-sm">Pending</div>
    </div>
    <div class="bc-card p-5">
        <div class="text-2xl font-black text-cyan-400">{{ $invoices->count() }}</div>
        <div class="text-slate-400 text-sm">Total Invoices</div>
    </div>
    <a href="{{ route('bconnect.billing.billable_time') }}" class="bc-card p-5 block bc-card-hover">
        <div class="text-2xl font-black text-pink-400">₹{{ number_format($unbilledTotal, 2) }}</div>
        <div class="text-slate-400 text-sm">Unbilled Time</div>
    </a>
    <div class="bc-card p-5">
        <div class="text-2xl font-black {{ $status == 'expired' ? 'text-red-400' : ($status == 'grace-period' ? 'text-amber-400' : 'text-green-400') }}">{{ ucfirst($company->plan) }}</div>
        <div class="text-slate-400 text-sm">{{ $days !== null ? $days . ' days left' : 'No expiry' }}</div>
    </div>
</div>

<div class="bc-card p-6 mb-8">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div class="flex items-center gap-3">
            @if($status == 'active')
                <span class="bc-badge bc-badge-green"><i class="fas fa-check-circle mr-1"></i>Active subscription</span>
            @elseif($status == 'cancelled-active-until-expiry')
                <span class="bc-badge bc-badge-amber"><i class="fas fa-exclamation-circle mr-1"></i>Cancelled — active until {{ $company->plan_expires_at?->format('M d, Y') }}</span>
            @elseif($status == 'grace-period')
                <span class="bc-badge bc-badge-amber"><i class="fas fa-clock mr-1"></i>Grace period until {{ $company->grace_period_until?->format('M d, Y') }}</span>
            @elseif($status == 'expired')
                <span class="bc-badge bc-badge-red"><i class="fas fa-times-circle mr-1"></i>Plan expired</span>
            @else
                <span class="bc-badge bc-badge-slate">Free plan</span>
            @endif
        </div>
        <div class="flex gap-2 flex-wrap">
            @if($company->plan !== 'free' && !str_starts_with($status, 'cancelled'))
            <form method="POST" action="{{ route('bconnect.billing.cancel') }}" class="inline" onsubmit="return confirm('Cancel subscription? Workspace will remain active until expiry.');">@csrf
                <button type="submit" class="bc-btn bc-btn-danger text-sm py-2 px-3"><i class="fas fa-ban"></i>Cancel</button>
            </form>
            <form method="POST" action="{{ route('bconnect.billing.renew') }}" class="inline">@csrf
                <button type="submit" class="bc-btn bc-btn-primary text-sm py-2 px-3"><i class="fas fa-sync"></i>Renew</button>
            </form>
            @endif
            <a href="{{ route('bconnect.billing.upgrade') }}" class="bc-btn bc-btn-primary text-sm py-2 px-3"><i class="fas fa-arrow-up"></i>Upgrade</a>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <h3 class="font-bold mb-4">Invoices</h3>
        <div class="overflow-x-auto">
            <table class="bc-table">
                <thead><tr><th>Number</th><th>Client</th><th>Amount</th><th>Due</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($invoices as $i)
                    <tr>
                        <td class="font-medium">{{ $i->invoice_number }}</td>
                        <td class="text-slate-400">{{ $i->client?->user?->name ?? '—' }}</td>
                        <td>₹{{ number_format($i->amount, 2) }}</td>
                        <td class="text-slate-400">{{ $i->due_at?->format('M d, Y') ?? '—' }}</td>
                        <td><span class="bc-badge {{ $i->status == 'paid' ? 'bc-badge-green' : 'bc-badge-amber' }}">{{ ucfirst($i->status) }}</span></td>
                        <td class="py-3 flex gap-2">
                            <a href="{{ route('bconnect.billing.download', $i->id) }}" class="bc-btn bc-btn-secondary p-1.5 text-xs"><i class="fas fa-download"></i></a>
                            @if($i->status != 'paid')
                                <a href="{{ route('bconnect.billing.pay', $i->id) }}" class="bc-btn bc-btn-primary py-1.5 px-2.5 text-xs"><i class="fas fa-credit-card mr-1"></i>Pay</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="bc-empty">No invoices.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $invoices->links() }}
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Create Invoice</h3>
        <form method="POST" action="{{ route('bconnect.invoices.store') }}" class="space-y-4">@csrf
            <select name="client_id" required class="bc-input"><option value="">Select client</option>@foreach(\App\Models\Bconnect\Member::with('user')->where('company_id', request()->input('bconnect_company_id'))->where('role', 'client')->get() as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select>
            <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required class="bc-input">
            <textarea name="description" rows="3" placeholder="Description" required class="bc-input"></textarea>
            <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-plus"></i>Create Invoice</button>
        </form>
    </div>
</div>
@endsection
