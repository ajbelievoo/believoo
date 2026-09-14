@extends('layouts.admin')

@section('title', 'Add Testimonial')

@section('content')
<div class="page-header">
    <h1 class="page-title">Add Testimonial</h1>
</div>

<div class="data-table" style="max-width: 600px;">
    <div class="table-header"><h3 class="table-title">Testimonial Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.testimonials.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Client Name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                @error('name')<p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Designation / Title</label>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="e.g. CEO"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    @error('title')<p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Company</label>
                    <input type="text" name="company" value="{{ old('company') }}" placeholder="e.g. FashionHub"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    @error('company')<p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>@enderror
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Feedback *</label>
                <textarea name="content" rows="4" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">{{ old('content') }}</textarea>
                @error('content')<p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p>@enderror
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Rating *</label>
                    <select name="rating" required style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary);">
                        @for($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" {{ old('rating', 5) == $i ? 'selected' : '' }}>{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_visible" value="1" {{ old('is_visible', true) ? 'checked' : '' }}>
                    Visible on website
                </label>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Save Testimonial</button>
                <a href="{{ route('admin.testimonials.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
