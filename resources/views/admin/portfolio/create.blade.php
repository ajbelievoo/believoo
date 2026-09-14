@extends('layouts.admin')

@section('title', 'Add Portfolio Item')

@section('content')
<div class="page-header">
    <h1 class="page-title">Add Portfolio Item</h1>
</div>

<div class="data-table" style="max-width: 700px;">
    <div class="table-header"><h3 class="table-title">Item Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.portfolio.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Slug *</label>
                    <input type="text" name="slug" value="{{ old('slug') }}" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Description *</label>
                <textarea name="description" rows="3" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('description') }}</textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Upload Image</label>
                    <input type="file" name="image" accept="image/*"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    @error('image') <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Or Image URL</label>
                    <input type="text" name="image_url" value="{{ old('image_url') }}"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Visible</label>
                    <select name="is_visible" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary);">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Save Item</button>
                <a href="{{ route('admin.portfolio.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
