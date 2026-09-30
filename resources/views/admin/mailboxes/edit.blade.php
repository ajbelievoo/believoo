@extends('layouts.admin')

@section('title', 'Edit Email Account')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Email Account</h1>
    <p class="page-subtitle">{{ $mailbox->username }}</p>
</div>

@if($errors->any())
<div style="background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.35); color: #dc2626; padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; max-width: 640px;">
    @foreach($errors->all() as $error)
        <div><i class="fas fa-exclamation-circle"></i> {{ $error }}</div>
    @endforeach
</div>
@endif

<div class="card" style="max-width: 640px;">
    <div class="table-header"><h3 class="table-title">Mailbox Settings</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.mailboxes.update', $mailbox->username) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Email Address</label>
                <input type="text" value="{{ $mailbox->username }}" disabled
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-muted); font-size: 0.9rem; opacity: .7;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Display Name</label>
                <input type="text" name="full_name" value="{{ old('full_name', $mailbox->full_name) }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">New Password</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" name="new_password" id="mailbox-password" placeholder="Leave blank to keep current password"
                        style="flex: 1; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <button type="button" class="btn btn-secondary" onclick="generatePassword()" style="white-space: nowrap;">
                        <i class="fas fa-sync-alt"></i> Generate
                    </button>
                </div>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;">Min 8 characters with uppercase, lowercase and a number.</p>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Mailbox Quota (GB)</label>
                <input type="number" name="quota_gb" value="{{ old('quota_gb', $mailbox->quota ? round($mailbox->quota / 1073741824, 1) : '') }}" min="0" step="0.5"
                    style="width: 160px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" {{ old('active', $mailbox->active) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                    Active (can send &amp; receive email)
                </label>
            </div>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
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
