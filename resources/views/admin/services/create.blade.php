@extends('layouts.admin')

@section('title', 'Create Service')

@section('content')
<div class="page-header">
    <h1 class="page-title">Create Service</h1>
</div>

<div class="card" style="max-width: 900px;">
    <div class="card-header"><h3 class="table-title">Service Details</h3></div>
    <div class="card-body">
        <form action="{{ route('admin.services.store') }}" method="POST" enctype="multipart/form-data">
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
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Category</label>
                    <select name="category" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="">Select Category</option>
                        <option value="Development" {{ old('category') == 'Development' ? 'selected' : '' }}>Development</option>
                        <option value="Infrastructure" {{ old('category') == 'Infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                        <option value="AI & Automation" {{ old('category') == 'AI & Automation' ? 'selected' : '' }}>AI & Automation</option>
                        <option value="Domains" {{ old('category') == 'Domains' ? 'selected' : '' }}>Domains</option>
                        <option value="Growth" {{ old('category') == 'Growth' ? 'selected' : '' }}>Growth</option>
                        <option value="Publishing" {{ old('category') == 'Publishing' ? 'selected' : '' }}>Publishing</option>
                        <option value="Support" {{ old('category') == 'Support' ? 'selected' : '' }}>Support</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Icon Class</label>
                    <input type="text" name="icon" value="{{ old('icon') }}" placeholder="fas fa-robot"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Banner Image</label>
                    <input type="file" name="image" accept="image/*"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    @error('image') <p style="color: #ef4444; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p> @enderror
                </div>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Short Description *</label>
                <textarea name="description" rows="3" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('description') }}</textarea>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Full Description / Content</label>
                <textarea name="content" rows="6"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('content') }}</textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Price (USD) *</label>
                    <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Price Label</label>
                    <input type="text" name="price_label" value="{{ old('price_label', 'Starting From') }}" placeholder="Starting From"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Active</label>
                    <select name="is_active" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary);">
                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('is_active', '1') == '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
            </div>
            <h4 style="font-size: 1rem; font-weight: 700; margin: 24px 0 16px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">SEO Settings</h4>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Meta Title</label>
                <input type="text" name="meta_title" value="{{ old('meta_title') }}" placeholder="Leave blank to auto-generate"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Meta Description</label>
                <textarea name="meta_description" rows="2" placeholder="Leave blank to use short description"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem; resize: vertical;">{{ old('meta_description') }}</textarea>
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Meta Keywords</label>
                <input type="text" name="meta_keywords" value="{{ old('meta_keywords') }}"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <h4 style="font-size: 1rem; font-weight: 700; margin: 24px 0 16px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">FAQs</h4>
            <div id="faq-list" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 16px;">
                <div class="faq-item" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;">
                    <input type="text" name="faqs[0][question]" placeholder="Question" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; color: var(--text-primary); margin-bottom: 8px;">
                    <textarea name="faqs[0][answer]" placeholder="Answer" rows="2" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; color: var(--text-primary); resize: vertical;"></textarea>
                </div>
            </div>
            <button type="button" onclick="addFaq()" class="btn btn-secondary" style="margin-bottom: 24px;">Add FAQ</button>

            <script>
                let faqIndex = 1;
                function addFaq() {
                    const list = document.getElementById('faq-list');
                    const div = document.createElement('div');
                    div.className = 'faq-item';
                    div.style.cssText = 'background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 16px;';
                    div.innerHTML = `<input type="text" name="faqs[${faqIndex}][question]" placeholder="Question" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; color: var(--text-primary); margin-bottom: 8px;"><textarea name="faqs[${faqIndex}][answer]" placeholder="Answer" rows="2" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px; color: var(--text-primary); resize: vertical;"></textarea>`;
                    list.appendChild(div);
                    faqIndex++;
                }
            </script>

            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary">Create Service</button>
                <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
