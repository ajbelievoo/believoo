@extends('layouts.admin')

@section('title', 'Edit Knowledge')

@section('content')
<div class="page-header"><h1 class="page-title">Edit: {{ $knowledge->title }}</h1></div>

<div class="data-table" style="max-width:800px;">
    <div class="table-header"><h3 class="table-title">Article</h3></div>
    <div style="padding:24px;">
        <form action="{{ route('admin.knowledge.update', $knowledge) }}" method="POST">
            @csrf @method('PUT')
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $knowledge->title) }}" required
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Category</label>
                    <input type="text" name="category" value="{{ old('category', $knowledge->category) }}"
                        style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
                </div>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Keywords</label>
                <input type="text" name="keywords" value="{{ old('keywords', $knowledge->keywords) }}"
                    style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;margin-bottom:6px;">Content *</label>
                <textarea name="content" rows="6" required
                    style="width:100%;background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:10px 16px;color:var(--text-primary);font-size:0.9rem;">{{ old('content', $knowledge->content) }}</textarea>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:8px;font-size:0.9rem;">
                    <input type="checkbox" name="is_active" value="1" {{ $knowledge->is_active ? 'checked' : '' }}> Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            <a href="{{ route('admin.knowledge.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
