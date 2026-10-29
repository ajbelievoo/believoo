@extends('layouts.admin')

@section('title', 'App Signing')

@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;"><i class="fas fa-file-signature" style="color:#fff;font-size:1.1rem;"></i></div>
    <div>
        <h2 style="margin:0;font-size:1.25rem;font-weight:800;color:#f1f5f9;">App Signing — Keystores</h2>
        <p style="margin:2px 0 0;font-size:0.78rem;color:#64748b;">Release keystore backup + signing credentials for every BelieVoo Android app</p>
    </div>
</div>

@if(session('success'))<div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#22c55e;padding:12px 16px;border-radius:12px;margin-bottom:18px;"><i class="fas fa-check-circle" style="margin-right:8px;"></i>{{ session('success') }}</div>@endif
@if(session('error'))<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#ef4444;padding:12px 16px;border-radius:12px;margin-bottom:18px;"><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>{{ session('error') }}</div>@endif

<div style="background:rgba(239,68,68,0.07);border:1px solid rgba(239,68,68,0.25);color:#fca5a5;padding:12px 16px;border-radius:12px;font-size:0.8rem;margin-bottom:20px;">
    <i class="fas fa-triangle-exclamation" style="margin-right:6px;"></i>
    Keystores are stored <b>outside the webroot</b> at <code>/www/keystore/&lt;app&gt;/</code> (0700, meta 0600) — never served over HTTP.
    Passwords are write-only: they are <b>never displayed back</b>; leaving a field blank keeps the saved value.
    This is the <b>secure backup + CI build source</b> — a local <code>flutter build appbundle</code> still needs its own <code>android/key.properties</code>.
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:16px;">
@foreach($apps as $app)
<div class="ghc-card">
    <div class="ghc-card-header">
        <h3><i class="fas {{ $app['icon'] }}" style="color:#a855f7;margin-right:8px;"></i>{{ $app['label'] }}</h3>
        @if(!empty($app['meta']['file']))
            <span style="font-size:0.66rem;font-weight:700;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.35);color:#22c55e;padding:3px 10px;border-radius:999px;">STORED</span>
        @else
            <span style="font-size:0.66rem;font-weight:700;background:rgba(148,163,184,0.1);border:1px solid rgba(148,163,184,0.25);color:#94a3b8;padding:3px 10px;border-radius:999px;">EMPTY</span>
        @endif
    </div>
    <div style="padding:16px;">
        @if(!empty($app['meta']['file']))
        <div style="font-size:0.78rem;color:#94a3b8;margin-bottom:12px;display:flex;flex-direction:column;gap:4px;">
            <span><i class="fas fa-file-shield" style="color:#22c55e;margin-right:6px;"></i>{{ $app['meta']['file'] }}@if(!empty($app['meta']['uploaded'])) · {{ $app['meta']['uploaded'] }}@endif</span>
            @if(!empty($app['meta']['alias']))<span><i class="fas fa-tag" style="margin-right:6px;"></i>alias: <code>{{ $app['meta']['alias'] }}</code></span>@endif
            <span><i class="fas fa-lock" style="margin-right:6px;"></i>passwords: <code>••••••••</code> (saved)</span>
        </div>
        @else
        <p style="font-size:0.78rem;color:#64748b;margin:0 0 12px;">No keystore uploaded yet for this app.</p>
        @endif
        <form method="POST" action="{{ route('admin.brandpanels.signing.save') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="app" value="{{ $app['key'] }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="grid-column:1/-1;"><label style="font-size:0.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">KEYSTORE (.jks/.keystore)</label>
                    <input type="file" name="keystore" accept=".jks,.keystore" class="ghc-input" style="padding:7px;font-size:0.78rem;"></div>
                <div><label style="font-size:0.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">KEY ALIAS</label>
                    <input name="key_alias" value="{{ $app['meta']['alias'] ?? '' }}" placeholder="release" class="ghc-input" style="font-size:0.8rem;"></div>
                <div><label style="font-size:0.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">STORE PASSWORD</label>
                    <input type="password" name="store_password" placeholder="blank = keep" class="ghc-input" style="font-size:0.8rem;" autocomplete="new-password"></div>
                <div style="grid-column:1/-1;"><label style="font-size:0.68rem;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">KEY PASSWORD</label>
                    <input type="password" name="key_password" placeholder="blank = keep" class="ghc-input" style="font-size:0.8rem;" autocomplete="new-password"></div>
            </div>
            <button type="submit" class="ghc-btn" style="margin-top:12px;width:100%;"><i class="fas fa-upload"></i> Save {{ $app['label'] }}</button>
        </form>
    </div>
</div>
@endforeach
</div>
@endsection
