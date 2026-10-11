@extends('bconnect.layout')
@section('title', 'My Profile')
@section('content')
<div class="grid lg:grid-cols-2 gap-6 max-w-5xl">
    <div class="bc-card p-6">
        <h3 class="font-semibold text-slate-200 mb-5">Profile</h3>
        <form method="POST" action="{{ route('bconnect.profile.update') }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <div class="flex items-center gap-4">
                @php $av = Auth::user()->avatar ?: Auth::user()->profile_photo_path; @endphp
                @if($av)
                    <img src="{{ $av }}" class="w-16 h-16 rounded-full object-cover border-2 border-slate-700" alt="avatar">
                @else
                    <div class="w-16 h-16 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-2xl">{{ substr(Auth::user()->name,0,1) }}</div>
                @endif
                <div>
                    <label class="bc-btn bc-btn-secondary text-xs cursor-pointer"><i class="fas fa-camera mr-1"></i>Change photo<input type="file" name="avatar" accept="image/*" class="hidden" onchange="this.form.submit()"></label>
                </div>
            </div>
            <div><label class="block text-sm text-slate-400 mb-1">Name</label>
                <input name="name" value="{{ Auth::user()->name }}" class="bc-input w-full"></div>
            <div><label class="block text-sm text-slate-400 mb-1">Phone</label>
                <input name="phone" value="{{ Auth::user()->phone }}" class="bc-input w-full"></div>
            <div><label class="block text-sm text-slate-400 mb-1">Email</label>
                <input value="{{ Auth::user()->email }}" disabled class="bc-input w-full opacity-50"></div>
            <button class="bc-btn bc-btn-primary">Save profile</button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="bc-card p-6">
            <h3 class="font-semibold text-slate-200 mb-5">Change password</h3>
            <form method="POST" action="{{ route('bconnect.profile.update') }}" class="space-y-4">@csrf
                <div><label class="block text-sm text-slate-400 mb-1">Current password</label>
                    <input type="password" name="current_password" class="bc-input w-full" autocomplete="current-password"></div>
                <div><label class="block text-sm text-slate-400 mb-1">New password</label>
                    <input type="password" name="new_password" class="bc-input w-full" autocomplete="new-password"></div>
                <div><label class="block text-sm text-slate-400 mb-1">Confirm new password</label>
                    <input type="password" name="new_password_confirmation" class="bc-input w-full" autocomplete="new-password"></div>
                @error('new_password')<p class="text-rose-400 text-xs">{{ $message }}</p>@enderror
                <button class="bc-btn bc-btn-primary">Update password</button>
            </form>
        </div>

        <div class="bc-card p-6">
            <h3 class="font-semibold text-slate-200 mb-3">Two-factor authentication</h3>
            @if(auth()->user()->twoFactorEnabled())
                <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-400 text-xs font-semibold">Enabled</span>
                    <a href="{{ route('two-factor.recovery') }}" class="text-cyan-400 text-sm hover:underline">Recovery codes</a>
                </div>
            @else
                <a href="{{ route('two-factor.setup') }}" class="bc-btn bc-btn-secondary text-sm">Enable 2FA</a>
            @endif
        </div>
    </div>
</div>
@endsection
