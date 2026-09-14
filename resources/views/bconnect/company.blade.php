@extends('bconnect.layout')
@section('title', 'Company')
@section('content')
<div class="max-w-3xl bg-slate-900 rounded-xl border border-slate-800 p-8">
    <form method="POST" action="{{ route('bconnect.company.update') }}" class="space-y-5">@csrf @method('PUT')
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Company Name</label><input type="text" name="name" value="{{ $company->name }}" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Description</label><textarea name="description" rows="3" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">{{ $company->description }}</textarea></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Custom Domain</label><input type="text" name="domain" value="{{ $company->domain }}" placeholder="company.believoo.com" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Logo URL</label><input type="url" name="logo" value="{{ $company->logo }}" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Brand Color</label><input type="text" name="branding[primary_color]" value="{{ $company->branding['primary_color'] ?? '#06b6d4' }}" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></div>
        <div><label class="block text-sm font-medium text-slate-400 mb-1">Plan</label><select name="plan" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="free" {{ $company->plan == 'free' ? 'selected' : '' }}>Free</option><option value="pro" {{ $company->plan == 'pro' ? 'selected' : '' }}>Pro</option><option value="enterprise" {{ $company->plan == 'enterprise' ? 'selected' : '' }}>Enterprise</option></select></div>
        <button type="submit" class="px-6 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Save Company</button>
        @if($company->domain)
        <button form="applyDomain" type="submit" class="px-6 py-2 bg-green-500/20 text-green-400 font-bold rounded-lg hover:bg-green-500/30 ml-2">Apply Nginx Vhost</button>
        @endif
    </form>
    @if($company->domain)
    <form id="applyDomain" method="POST" action="{{ route('bconnect.company.apply-domain') }}" class="hidden">@csrf</form>
    @endif
</div>
@endsection
