@extends('bconnect.layout')
@section('title', 'Company Settings')
@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-white mb-1">Company Settings</h2>
        <p class="text-slate-400 text-sm">Manage workspace branding, domain and plan.</p>
    </div>
    <div class="bc-card p-6 lg:p-8">
        <form method="POST" action="{{ route('bconnect.company.update') }}" class="space-y-5">@csrf @method('PUT')
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Company Name</label>
                <input type="text" name="name" value="{{ $company->name }}" required class="bc-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-400 mb-1">Description</label>
                <textarea name="description" rows="3" class="bc-input">{{ $company->description }}</textarea>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Custom Domain</label>
                    <input type="text" name="domain" value="{{ $company->domain }}" placeholder="company.believoo.com" class="bc-input">
                    <p class="text-xs text-slate-500 mt-1">Pro/Enterprise plans. Apply vhost after saving.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Logo URL</label>
                    <input type="url" name="logo" value="{{ $company->logo }}" class="bc-input">
                </div>
            </div>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Brand Color</label>
                    <div class="flex gap-2">
                        <input type="color" value="{{ $company->branding['primary_color'] ?? '#06b6d4' }}" class="h-11 w-14 rounded-lg bg-slate-800 border border-slate-700 cursor-pointer" oninput="document.getElementById('brandColor').value=this.value">
                        <input type="text" id="brandColor" name="branding[primary_color]" value="{{ $company->branding['primary_color'] ?? '#06b6d4' }}" class="bc-input flex-1">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-400 mb-1">Plan</label>
                    <select name="plan" class="bc-input" disabled title="Upgrade from Billing">
                        <option value="free" {{ $company->plan == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="pro" {{ $company->plan == 'pro' ? 'selected' : '' }}>Pro</option>
                        <option value="enterprise" {{ $company->plan == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <button type="submit" class="bc-btn bc-btn-primary"><i class="fas fa-save mr-2"></i>Save Company</button>
                @if($company->domain)
                <button form="applyDomain" type="submit" class="bc-btn bc-btn-secondary"><i class="fas fa-server mr-2"></i>Apply Nginx Vhost</button>
                @endif
            </div>
        </form>
        @if($company->domain)
        <form id="applyDomain" method="POST" action="{{ route('bconnect.company.apply-domain') }}" class="hidden">@csrf</form>
        @endif
    </div>
</div>
@endsection
