@extends('layouts.admin')

@section('title', 'Add Knowledge')

@section('content')
<div class="page-header">
    <h1 class="page-title">Add Knowledge Article</h1>
    <p class="page-subtitle">AI will answer clients from this — e.g. refund policy, setup steps, pricing</p>
</div>

<div class="data-table" style="max-width:800px;">
    <div class="table-header"><h3 class="table-title">Article</h3></div>
    <div style="padding:24px;">
        <form action="{{ route('admin.knowledge.store') }}" method="POST">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required placeholder="e.g. Refund Policy"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Category</label>
                    <input type="text" name="category" value="{{ old('category') }}" placeholder="e.g. Billing"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Keywords (comma separated — words clients might use)</label>
                <input type="text" name="keywords" value="{{ old('keywords') }}" placeholder="e.g. refund, money back, cancel, return"
                    style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Content * (AI will use this as the answer)</label>
                <textarea name="content" rows="6" required placeholder="Write the full answer here..."
                    style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">{{ old('content') }}</textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;">
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            <a href="{{ route('admin.knowledge.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
