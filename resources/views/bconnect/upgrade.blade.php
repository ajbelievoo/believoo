@extends('bconnect.layout')
@section('title', 'Upgrade Plan')
@section('content')
<div class="max-w-5xl mx-auto">
    <h2 class="text-2xl font-bold text-white mb-2">Upgrade Bmydesk Plan</h2>
    <p class="text-slate-400 mb-8">Current plan: <span class="text-cyan-400 font-bold">{{ ucfirst($company->plan) }}</span></p>

    <form method="POST" action="{{ route('bconnect.billing.upgrade.process') }}" class="space-y-6">
        @csrf
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($plans as $key => $p)
            @if($key !== 'free')
            <label class="relative bg-slate-900 rounded-2xl border border-slate-800 p-6 cursor-pointer hover:border-cyan-500/50 transition block">
                <input type="radio" name="plan" value="{{ $key }}" class="peer sr-only" {{ $company->plan == $key ? 'checked' : '' }}>
                <div class="absolute top-4 right-4 w-4 h-4 rounded-full border border-slate-500 peer-checked:bg-cyan-400 peer-checked:border-cyan-400"></div>
                <h3 class="text-xl font-bold text-white mb-1">{{ $p['name'] }}</h3>
                <div class="text-3xl font-black text-cyan-400 mb-2">₹{{ number_format($p['price'], 0) }}<span class="text-sm text-slate-500 font-normal">/mo</span></div>
                <ul class="text-sm text-slate-400 space-y-2 mb-4">
                    <li><i class="fas fa-users w-5 text-cyan-400"></i>{{ $p['members'] ?? 'Unlimited' }} members</li>
                    <li><i class="fas fa-video w-5 text-cyan-400"></i>{{ $p['calls'] }} calls</li>
                    <li><i class="fas fa-desktop w-5 text-cyan-400"></i>Remote: {{ $p['remote'] ? 'Yes' : 'No' }}</li>
                    <li><i class="fas fa-robot w-5 text-cyan-400"></i>AI: {{ $p['ai'] ? 'Yes' : 'No' }}</li>
                </ul>
            </label>
            @endif
            @endforeach
        </div>

        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6">
            <h3 class="font-bold text-white mb-4">Billing Cycle</h3>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                    <input type="radio" name="billing_cycle" value="monthly" class="accent-cyan-500" checked> Monthly
                </label>
                <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                    <input type="radio" name="billing_cycle" value="yearly" class="accent-cyan-500"> Yearly (2 months free)
                </label>
            </div>
        </div>

        <button type="submit" class="px-8 py-3 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">
            <i class="fas fa-lock mr-2"></i>Proceed to Payment
        </button>
    </form>
</div>
@endsection
