@extends('layouts.admin')

@section('title', 'Add Team Member')

@section('content')
<div class="page-header">
    <h1 class="page-title">Add Team Member</h1>
</div>

<div class="data-table" style="max-width: 800px;">
    <div class="table-header"><h3 class="table-title">Member Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.teams.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Role / Position</label>
                    <input type="text" name="role" value="{{ old('role') }}"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Display Order</label>
                    <input type="number" name="order" value="{{ old('order', 0) }}" min="0"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Active</label>
                    <select name="is_active" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('is_active', '1') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Photo</label>
                <input type="file" name="photo" accept="image/*"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                @error('photo') <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Bio</label>
                <textarea name="bio" rows="4"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('bio') }}</textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Social Links</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <input type="url" name="facebook" value="{{ old('facebook') }}" placeholder="Facebook URL" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <input type="url" name="twitter" value="{{ old('twitter') }}" placeholder="Twitter URL" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <input type="url" name="instagram" value="{{ old('instagram') }}" placeholder="Instagram URL" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    <input type="url" name="linkedin" value="{{ old('linkedin') }}" placeholder="LinkedIn URL" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Save Member</button>
                <a href="{{ route('admin.teams.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
