@php
    $isEdit = isset($campaign);
    $action = $isEdit ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store');
    $method = $isEdit ? 'patch' : 'post';
    $inputStyle = 'width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;';
    $labelStyle = 'display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--text-primary);';
    $helpStyle = 'font-size: 0.75rem; color: var(--text-muted); margin-top: 6px;';
@endphp

<form method="post" action="{{ $action }}">
    @csrf
    @method($method)

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 18px;">
        <div>
            <label style="{{ $labelStyle }}">Campaign Name *</label>
            <input type="text" name="name" value="{{ old('name', $campaign->name ?? '') }}" required style="{{ $inputStyle }}">
        </div>
        <div>
            <label style="{{ $labelStyle }}">Email Subject *</label>
            <input type="text" name="subject" value="{{ old('subject', $campaign->subject ?? '') }}" required style="{{ $inputStyle }}">
        </div>
        <div>
            <label style="{{ $labelStyle }}">From Name</label>
            <input type="text" name="from_name" value="{{ old('from_name', $campaign->from_name ?? '') }}" placeholder="Believoo" style="{{ $inputStyle }}">
        </div>
        <div>
            <label style="{{ $labelStyle }}">From Email</label>
            <input type="email" name="from_email" value="{{ old('from_email', $campaign->from_email ?? '') }}" placeholder="noreply@believoo.com" style="{{ $inputStyle }}">
        </div>
        <div>
            <label style="{{ $labelStyle }}">Test Email</label>
            <input type="email" name="test_email" value="{{ old('test_email', $campaign->test_email ?? '') }}" placeholder="test@example.com" style="{{ $inputStyle }}">
            <p style="{{ $helpStyle }}">If set, only this email receives the campaign (for testing).</p>
        </div>
        <div>
            <label style="{{ $labelStyle }}">Audience Segment *</label>
            <select name="segment" required style="{{ $inputStyle }}">
                @foreach($segments as $key => $label)
                    <option value="{{ $key }}" {{ old('segment', $campaign->segment ?? 'all') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="{{ $labelStyle }}">Schedule At</label>
            <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', isset($campaign) && $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '') }}" style="{{ $inputStyle }} color-scheme: dark;">
            <p style="{{ $helpStyle }}">Leave empty to save as draft without scheduling.</p>
        </div>
        <div style="grid-column: 1 / -1;">
            <label style="{{ $labelStyle }}">HTML Content *</label>
            <textarea name="content_html" rows="14" required style="{{ $inputStyle }} font-family: ui-monospace, monospace; font-size: 0.82rem; line-height: 1.5; resize: vertical;">{{ old('content_html', $campaign->content_html ?? '') }}</textarea>
            <p style="{{ $helpStyle }}">Use full HTML for the email body. Tracking pixel and link tracking are injected automatically.</p>
        </div>
    </div>

    <div style="display: flex; gap: 12px; margin-top: 24px; align-items: center;">
        <button type="submit" name="action" value="save" class="btn btn-secondary"><i class="fas fa-save"></i> Save Draft</button>
        <button type="submit" name="action" value="send_now" class="btn btn-primary" {{ $isEdit && !in_array($campaign->status, ['draft', 'scheduled']) ? 'disabled' : '' }}><i class="fas fa-paper-plane"></i> Save &amp; Send Now</button>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
