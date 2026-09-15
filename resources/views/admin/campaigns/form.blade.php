@php
    $isEdit = isset($campaign);
    $action = $isEdit ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store');
    $method = $isEdit ? 'patch' : 'post';
@endphp

<form method="post" action="{{ $action }}">
    @csrf
    @method($method)

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Campaign Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $campaign->name ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Email Subject</label>
            <input type="text" name="subject" class="form-control" value="{{ old('subject', $campaign->subject ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">From Name</label>
            <input type="text" name="from_name" class="form-control" value="{{ old('from_name', $campaign->from_name ?? '') }}" placeholder="Believoo">
        </div>
        <div class="col-md-6">
            <label class="form-label">From Email</label>
            <input type="email" name="from_email" class="form-control" value="{{ old('from_email', $campaign->from_email ?? '') }}" placeholder="noreply@believoo.com">
        </div>
        <div class="col-md-6">
            <label class="form-label">Test Email</label>
            <input type="email" name="test_email" class="form-control" value="{{ old('test_email', $campaign->test_email ?? '') }}" placeholder="test@example.com">
            <div class="form-text">If set, only this email receives the campaign (for testing).</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Audience Segment</label>
            <select name="segment" class="form-select" required>
                @foreach($segments as $key => $label)
                    <option value="{{ $key }}" {{ old('segment', $campaign->segment ?? 'all') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Schedule At</label>
            <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', isset($campaign) && $campaign->scheduled_at ? $campaign->scheduled_at->format('Y-m-d\TH:i') : '') }}">
        </div>
        <div class="col-12">
            <label class="form-label">HTML Content</label>
            <textarea name="content_html" class="form-control" rows="12" required>{{ old('content_html', $campaign->content_html ?? '') }}</textarea>
            <div class="form-text">Use full HTML for the email body. Tracking pixel and link tracking are injected automatically.</div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" name="action" value="save" class="btn btn-secondary">Save Draft</button>
        <button type="submit" name="action" value="send_now" class="btn btn-primary" {{ $isEdit && !in_array($campaign->status, ['draft', 'scheduled']) ? 'disabled' : '' }}>Save & Send Now</button>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-link">Cancel</a>
    </div>
</form>
