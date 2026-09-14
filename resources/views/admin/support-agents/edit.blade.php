@extends('layouts.admin')

@section('title', 'Edit Agent')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit: {{ $supportAgent->display_name ?: $supportAgent->name }}</h1>
</div>

<div class="data-table" style="max-width:800px;">
    <div class="table-header"><h3 class="table-title">Agent Details</h3></div>
    <div style="padding:24px;">
        <form action="{{ route('admin.support-agents.update', $supportAgent) }}" method="POST">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Real Name *</label>
                    <input type="text" name="name" value="{{ old('name', $supportAgent->name) }}" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Display Name</label>
                    <input type="text" name="display_name" value="{{ old('display_name', $supportAgent->display_name) }}"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Login Email *</label>
                    <input type="email" name="email" value="{{ old('email', $supportAgent->email) }}" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">New Password (blank = keep current)</label>
                    <input type="password" name="password" minlength="6"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $supportAgent->phone) }}"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Max Chats</label>
                    <input type="number" name="max_chats" value="{{ old('max_chats', $supportAgent->max_chats) }}" min="1" max="20" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $supportAgent->is_active) ? 'checked' : '' }}> Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            <a href="{{ route('admin.support-agents.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
