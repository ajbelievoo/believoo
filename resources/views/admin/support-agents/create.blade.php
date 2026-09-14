@extends('layouts.admin')

@section('title', 'Add Support Agent')

@section('content')
<div class="page-header">
    <h1 class="page-title">Add Support Agent</h1>
    <p class="page-subtitle">Create a live-chat team member with their own login</p>
</div>

<div class="data-table" style="max-width:800px;">
    <div class="table-header"><h3 class="table-title">Agent Details</h3></div>
    <div style="padding:24px;">
        <form action="{{ route('admin.support-agents.store') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Real Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                    @error('name')<p style="color:#ef4444;font-size:0.75rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Display Name (shown to clients)</label>
                    <input type="text" name="display_name" value="{{ old('display_name') }}" placeholder="e.g. Priya - Believoo Support"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Login Email *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                    @error('email')<p style="color:#ef4444;font-size:0.75rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Login Password *</label>
                    <input type="password" name="password" required minlength="6"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                    @error('password')<p style="color:#ef4444;font-size:0.75rem;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Max Simultaneous Chats</label>
                    <input type="number" name="max_chats" value="{{ old('max_chats', 3) }}" min="1" max="20" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Active (can log in)
                </label>
            </div>
            <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:16px;">
                Agent login URL: <strong>{{ url('/agent/login') }}</strong> — give them this URL with their email + password.
            </p>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Agent</button>
            <a href="{{ route('admin.support-agents.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
