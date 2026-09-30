@extends('layouts.admin')

@section('title', 'Create Email Account')

@section('content')
<div class="page-header">
    <h1 class="page-title">Create Email Account</h1>
    <p class="page-subtitle">Add a new mailbox — it works instantly at <a href="https://mail.believoo.com" target="_blank" style="color: var(--accent-primary);">mail.believoo.com</a></p>
</div>

@if($errors->any())
<div style="background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35); color: #dc2626; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; max-width: 640px;">
    @foreach($errors->all() as $error)
        <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endforeach
</div>
@endif

<div class="card" style="max-width: 640px;">
    <div class="table-header"><h3 class="table-title">Mailbox Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.mailboxes.store') }}" method="POST">
            @csrf

            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Email Address *</label>
            <div style="display: flex; gap: 0; margin-bottom: 16px;">
                <input type="text" name="local_part" value="{{ old('local_part') }}" required placeholder="help" autocomplete="off"
                    style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-right: none; border-radius: 10px 0 0 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                <select name="domain" required
                    style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 0 10px 10px 0; padding: 10px 12px; color: var(--text-primary); font-size: 0.9rem;">
                    @foreach($domains as $domain)
                    <option value="{{ $domain }}" {{ old('domain', 'believoo.com') === $domain ? 'selected' : '' }}>@{{ $domain }}</option>
                    @endforeach
                </select>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin: -10px 0 16px;">Only lowercase letters, numbers, dot (.), dash (-), underscore (_)</p>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Display Name</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="e.g. Support Team"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Password *</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="password" id="mailbox-password" value="{{ old('password') }}" required
                        style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <button type="button" class="btn btn-secondary" onclick="generatePassword()" style="white-space: nowrap;">
                        <i class="fas fa-sync-alt"></i> Generate
                    </button>
                </div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Min 8 characters with uppercase, lowercase and a number. Save it — it will be needed to log in to webmail.</p>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Mailbox Quota (GB)</label>
                <input type="number" name="quota_gb" value="{{ old('quota_gb', 5) }}" min="0.5" step="0.5"
                    style="width: 160px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" {{ old('active', '1') ? 'checked' : '' }} style="width: 18px; height: 18px;">
                    Active (can send &amp; receive email immediately)
                </label>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-envelope"></i> Create Email Account</button>
                <a href="{{ route('admin.mailboxes.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
function generatePassword() {
    const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    const lower = 'abcdefghijkmnpqrstuvwxyz';
    const digits = '23456789';
    const all = upper + lower + digits;
    let pw = upper[Math.floor(Math.random() * upper.length)]
        + lower[Math.floor(Math.random() * lower.length)]
        + digits[Math.floor(Math.random() * digits.length)];
    for (let i = 0; i < 11; i++) pw += all[Math.floor(Math.random() * all.length)];
    pw = pw.split('').sort(() => Math.random() - 0.5).join('');
    document.getElementById('mailbox-password').value = pw;
}
</script>
@endsection
