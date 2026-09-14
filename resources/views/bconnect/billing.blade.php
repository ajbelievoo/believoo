@extends('bconnect.layout')
@section('title', 'Billing')
@section('content')
<div class="grid grid-cols-4 gap-4 mb-8">
    <div class="bg-slate-900 p-6 rounded-xl border border-slate-800"><div class="text-2xl font-black text-cyan-400">₹{{ number_format($invoices->where('status','paid')->sum('amount'), 2) }}</div><div class="text-slate-400 text-sm">Paid</div></div>
    <div class="bg-slate-900 p-6 rounded-xl border border-slate-800"><div class="text-2xl font-black text-amber-400">₹{{ number_format($invoices->where('status','pending')->sum('amount'), 2) }}</div><div class="text-slate-400 text-sm">Pending</div></div>
    <div class="bg-slate-900 p-6 rounded-xl border border-slate-800"><div class="text-2xl font-black text-pink-400">{{ $invoices->count() }}</div><div class="text-slate-400 text-sm">Total Invoices</div></div>
    <div class="bg-slate-900 p-6 rounded-xl border border-slate-800">
        <div class="text-2xl font-black {{ $status == 'expired' ? 'text-red-400' : ($status == 'grace-period' ? 'text-amber-400' : 'text-green-400') }}">{{ ucfirst($company->plan) }}</div>
        <div class="text-slate-400 text-sm">{{ $days !== null ? $days . ' days left' : 'No expiry' }}</div>
    </div>
</div>

<div class="flex justify-between items-center mb-4">
    <div>
        @if($status == 'active')
            <span class="text-green-400 text-sm"><i class="fas fa-check-circle mr-1"></i>Active subscription</span>
        @elseif($status == 'cancelled-active-until-expiry')
            <span class="text-amber-400 text-sm"><i class="fas fa-exclamation-circle mr-1"></i>Cancelled — active until {{ $company->plan_expires_at?->format('M d, Y') }}</span>
        @elseif($status == 'grace-period')
            <span class="text-amber-400 text-sm"><i class="fas fa-clock mr-1"></i>Grace period until {{ $company->grace_period_until?->format('M d, Y') }}</span>
        @elseif($status == 'expired')
            <span class="text-red-400 text-sm"><i class="fas fa-times-circle mr-1"></i>Plan expired</span>
        @else
            <span class="text-slate-400 text-sm">Free plan</span>
        @endif
    </div>
    <div class="flex gap-2">
        @if($company->plan !== 'free' && !str_starts_with($status, 'cancelled'))
        <form method="POST" action="{{ route('bconnect.billing.cancel') }}" class="inline" onsubmit="return confirm('Cancel subscription? Workspace will remain active until expiry.');">@csrf
            <button type="submit" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 text-sm"><i class="fas fa-ban mr-2"></i>Cancel</button>
        </form>
        <form method="POST" action="{{ route('bconnect.billing.renew') }}" class="inline">@csrf
            <button type="submit" class="px-4 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400 text-sm"><i class="fas fa-sync mr-2"></i>Renew</button>
        </form>
        @endif
        <a href="{{ route('bconnect.billing.upgrade') }}" class="px-4 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400 text-sm"><i class="fas fa-arrow-up mr-2"></i>Upgrade</a>
    </div>
</div>

<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Invoices</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Number</th><th>Client</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($invoices as $i)
                <tr class="border-b border-slate-800">
                    <td class="py-3">{{ $i->invoice_number }}</td>
                    <td>{{ $i->client?->user?->name ?? '—' }}</td>
                    <td>₹{{ number_format($i->amount, 2) }}</td>
                    <td><span class="px-2 py-1 rounded text-[10px] font-bold {{ $i->status == 'paid' ? 'bg-green-500/20 text-green-400' : 'bg-amber-500/20 text-amber-400' }}">{{ ucfirst($i->status) }}</span></td>
                    <td class="py-3 flex gap-2">
                        <a href="{{ route('bconnect.billing.download', $i->id) }}" class="px-3 py-1 bg-slate-700/50 text-slate-300 rounded text-xs hover:bg-slate-700"><i class="fas fa-download"></i></a>
                        @if($i->status != 'paid')
                            <a href="{{ route('bconnect.billing.pay', $i->id) }}" class="px-3 py-1 bg-cyan-500/20 text-cyan-400 rounded text-xs hover:bg-cyan-500/30">Pay Now</a>
                        @endif
                    </td>
                </tr>
                @empty<tr><td colspan="5" class="py-6 text-center text-slate-500">No invoices.</td></tr>@endforelse
            </tbody>
        </table>
        {{ $invoices->links() }}
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Create Invoice</h3>
        <form method="POST" action="{{ route('bconnect.invoices.store') }}" class="space-y-4">@csrf
            <select name="client_id" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="">Select client</option>@foreach(\App\Models\Bconnect\Member::with('user')->where('company_id', request()->input('bconnect_company_id'))->where('role', 'client')->get() as $c)<option value="{{ $c->id }}">{{ $c->user->name }}</option>@endforeach</select>
            <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
            <textarea name="description" rows="3" placeholder="Description" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></textarea>
            <button type="submit" class="w-full py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Create Invoice</button>
        </form>
    </div>
</div>
@endsection
