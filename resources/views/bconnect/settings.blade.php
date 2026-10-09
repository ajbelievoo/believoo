@extends('bconnect.layout')
@section('title', 'Settings')
@section('content')
<div class="max-w-2xl bg-slate-900 rounded-xl border border-slate-800 p-8">
    <form method="POST" action="{{ route('bconnect.settings.update') }}" class="space-y-5">@csrf
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Company Name</label><input type="text" name="name" value="{{ $company->name }}" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Plan</label><select name="plan" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="free" {{ $company->plan == 'free' ? 'selected' : '' }}>Free</option><option value="pro" {{ $company->plan == 'pro' ? 'selected' : '' }}>Pro</option><option value="enterprise" {{ $company->plan == 'enterprise' ? 'selected' : '' }}>Enterprise</option></select></div>
        <div class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ $company->is_active ? 'checked' : '' }} class="accent-cyan-500"><span class="text-sm">Workspace Active</span></div>
        <button type="submit" class="px-6 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Save Settings</button>
    </form>
</div>

<div class="max-w-2xl bg-slate-900 rounded-xl border border-slate-800 p-8 mt-6">
    <h3 class="text-lg font-semibold text-white mb-1">Account Security</h3>
    <p class="text-sm text-slate-400 mb-4">Two-factor authentication for your login (all workspaces sharing this account).</p>
    @if(auth()->user()->twoFactorEnabled())
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-semibold">2FA enabled</span>
            <a href="{{ route('two-factor.recovery') }}" class="text-cyan-400 text-sm hover:underline">Recovery codes</a>
            <form method="POST" action="{{ route('two-factor.destroy') }}" onsubmit="return confirm('Disable two-factor authentication?')">@csrf @method('DELETE')
                <button type="submit" class="text-rose-400 text-sm hover:underline">Disable</button>
            </form>
        </div>
    @else
        <a href="{{ route('two-factor.setup') }}" class="inline-block px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-sm font-semibold">Enable two-factor authentication</a>
    @endif
</div>
@endsection
