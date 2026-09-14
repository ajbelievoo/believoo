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
@endsection
