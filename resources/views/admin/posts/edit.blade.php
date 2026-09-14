@extends('layouts.admin')

@section('title', 'Edit Post')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Blog Post</h1>
</div>

<div class="data-table">
    <div style="padding: 24px;">
        <form action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $post->title) }}" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Slug *</label>
                    <input type="text" name="slug" value="{{ old('slug', $post->slug) }}" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Excerpt</label>
                <textarea name="excerpt" rows="2"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('excerpt', $post->excerpt) }}</textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Content *</label>
                <input type="hidden" name="content" value="{{ old('content', $post->content) }}">
                <div id="editor" style="height: 400px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px;"></div>
                @error('content') <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Featured Image URL</label>
                <input type="url" name="featured_image" value="{{ old('featured_image', str_starts_with($post->featured_image ?? '', 'http') ? $post->featured_image : '') }}" placeholder="https://..."
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Or Upload Featured Image</label>
                <input type="file" name="featured_image_file" accept="image/*"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                @if($post->featuredImageUrl())
                    <div style="margin-top: 12px;">
                        <img src="{{ $post->featuredImageUrl() }}" alt="Current featured image" style="max-width: 200px; max-height: 120px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <label style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.85rem; color: var(--text-secondary);">
                            <input type="checkbox" name="remove_featured_image" value="1"> Remove current image
                        </label>
                    </div>
                @endif
            </div>
            <h4 style="font-size: 1rem; font-weight: 700; margin: 24px 0 16px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">SEO Settings</h4>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title', $post->meta_title) }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Meta Description</label>
                <textarea name="meta_description" rows="2"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('meta_description', $post->meta_description) }}</textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Meta Keywords</label>
                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $post->meta_keywords) }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Published?</label>
                    <select name="is_published" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="1" {{ old('is_published', $post->is_published ? '1' : '0') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('is_published', $post->is_published ? '1' : '0') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Published At</label>
                    <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Update Post</button>
                <a href="{{ route('admin.posts.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>
<script>
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Write your blog post here...',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });

    quill.root.innerHTML = document.querySelector('input[name="content"]').value;

    const form = document.querySelector('form[action="{{ route('admin.posts.update', $post) }}"]');
    form.addEventListener('formdata', function(e) {
        e.formData.set('content', quill.root.innerHTML);
    });
</script>
@endpush
