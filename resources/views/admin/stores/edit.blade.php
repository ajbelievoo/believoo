@extends('layouts.admin')

@section('title', 'Edit Store')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Store</h1>
    <p class="page-subtitle">{{ $store->name }}</p>
</div>

<div class="data-table" style="max-width: 600px;">
    <div class="table-header"><h3 class="table-title">Store Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.stores.update', $store) }}" method="POST">
            @csrf
            @method('PUT')
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Name *</label>
                <input type="text" name="name" value="{{ old('name', $store->name) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">URL *</label>
                <input type="url" name="url" value="{{ old('url', $store->url) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Status *</label>
                <select name="status" required style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary);">
                    <option value="active" {{ $store->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $store->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Update Store</button>
                <a href="{{ route('admin.stores.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
