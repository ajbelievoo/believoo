@extends('layouts.admin')

@section('title', 'Edit Announcement')

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
.ql-toolbar.ql-snow { background: #f8fafc; border-color: var(--border-color); border-radius: 10px 10px 0 0; }
.ql-container.ql-snow { background: var(--bg-tertiary); border-color: var(--border-color); border-radius: 0 0 10px 10px; color: var(--text-primary); min-height: 240px; }
.ql-editor { color: var(--text-primary); }
#audience-count { font-weight: 600; color: var(--accent); }
</style>
@endpush

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Announcement</h1>
    <p class="page-subtitle">Update or re-send</p>
</div>

<div class="data-table" style="max-width: 1100px;">
    <div class="table-header"><h3 class="table-title">Announcement Details</h3></div>
    <div style="padding: 24px;">
        <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST" id="announcementForm" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Title</label>
                <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Message (English)</label>
                <input type="hidden" name="message" id="message" value="{{ old('message', $announcement->message) }}">
                <div id="editor" style="min-height: 240px;">{!! old('message', $announcement->message) !!}</div>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Message (Hindi) <small style="color: var(--text-muted); font-weight: 400;">optional</small></label>
                <input type="hidden" name="message_hi" id="message_hi" value="{{ old('message_hi', $announcement->message_hi) }}">
                <div id="editor_hi" style="min-height: 200px;">{!! old('message_hi', $announcement->message_hi) !!}</div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Type</label>
                    <select name="type" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="info" {{ old('type', $announcement->type) == 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ old('type', $announcement->type) == 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="important" {{ old('type', $announcement->type) == 'important' ? 'selected' : '' }}>Important</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Language</label>
                    <select name="locale" id="locale" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="en" {{ old('locale', $announcement->locale) == 'en' ? 'selected' : '' }}>English</option>
                        <option value="hi" {{ old('locale', $announcement->locale) == 'hi' ? 'selected' : '' }}>Hindi</option>
                        <option value="both" {{ old('locale', $announcement->locale) == 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Audience</label>
                    <select name="audience" id="audience" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="all" {{ old('audience', $announcement->audience) == 'all' ? 'selected' : '' }}>All users</option>
                        <option value="clients" {{ old('audience', $announcement->audience) == 'clients' ? 'selected' : '' }}>Clients only</option>
                        <option value="bconnect" {{ old('audience', $announcement->audience) == 'bconnect' ? 'selected' : '' }}>B-Connect users</option>
                        <option value="ghc" {{ old('audience', $announcement->audience) == 'ghc' ? 'selected' : '' }}>GHC users</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Audience Count</label>
                    <div id="audience-count" style="padding: 10px 0; font-size: 1rem;">—</div>
                </div>
            </div>

            <div style="margin-bottom: 18px; padding: 14px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px; font-weight: 600;">
                    <input type="checkbox" name="ab_enabled" value="1" {{ old('ab_enabled', $announcement->ab_enabled) ? 'checked' : '' }} onchange="toggleAbTest()"> Enable A/B testing
                </label>
                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 0 0 12px;">Split test two variants. A test sample is sent first; the winner is automatically sent to the rest after the test window.</p>

                <div id="ab-test-fields" style="display: none;">
                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">A/B Test Name</label>
                        <input type="text" name="ab_test_name" value="{{ old('ab_test_name', $announcement->ab_test_name) }}" placeholder="e.g. March Subject Line Test"
                            style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Variant B Title</label>
                        <input type="text" name="title_b" value="{{ old('title_b', $announcement->title_b) }}"
                            style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Variant B Message (English)</label>
                        <input type="hidden" name="message_b" id="message_b" value="{{ old('message_b', $announcement->message_b) }}">
                        <div id="editor_b" style="min-height: 200px;">{!! old('message_b', $announcement->message_b) !!}</div>
                    </div>

                    <div style="margin-bottom: 14px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Variant B Message (Hindi) <small style="color: var(--text-muted); font-weight: 400;">optional</small></label>
                        <input type="hidden" name="message_hi_b" id="message_hi_b" value="{{ old('message_hi_b', $announcement->message_hi_b) }}">
                        <div id="editor_hi_b" style="min-height: 180px;">{!! old('message_hi_b', $announcement->message_hi_b) !!}</div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 14px; margin-bottom: 8px;">
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Test sample %</label>
                            <input type="number" name="ab_test_percentage" value="{{ old('ab_test_percentage', $announcement->ab_test_percentage) }}" min="1" max="100"
                                style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.9rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Variant A % of test</label>
                            <input type="number" name="ab_split" value="{{ old('ab_split', $announcement->ab_split) }}" min="0" max="100"
                                style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.9rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Win metric</label>
                            <select name="ab_metric" style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.9rem;">
                                <option value="opens" {{ old('ab_metric', $announcement->ab_metric) == 'opens' ? 'selected' : '' }}>Opens</option>
                                <option value="clicks" {{ old('ab_metric', $announcement->ab_metric) == 'clicks' ? 'selected' : '' }}>Clicks</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.8rem; font-weight: 600; margin-bottom: 4px;">Test duration (min)</label>
                            <input type="number" name="ab_duration_minutes" value="{{ old('ab_duration_minutes', $announcement->ab_duration_minutes) }}" min="1"
                                style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.9rem;">
                        </div>
                    </div>
                </div>
            </div>

            <div id="audience-note" style="margin-bottom: 18px; padding: 12px 16px; background: var(--bg-tertiary); border-left: 4px solid var(--accent); border-radius: 0 10px 10px 0; font-size: 0.85rem; color: var(--text-secondary);">
                This announcement will be sent to all Believoo, B-Connect and GHC users.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Attachment</label>
                    @if($announcement->attachment)
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0 0 6px;">Current: {{ $announcement->attachment }}</p>
                    @endif
                    <input type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.zip"
                        style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                </div>

                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Load Template</label>
                    <select id="template-select" style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
                        <option value="">— Select —</option>
                        @foreach($templates as $template)
                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Send Time</label>
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 10px;">
                    @php
                        $sendType = old('send_type', $announcement->scheduled_at ? 'schedule' : ($announcement->is_published ? 'now' : 'draft'));
                    @endphp
                    <label style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary); font-size: 0.85rem;">
                        <input type="radio" name="send_type" value="now" {{ $sendType == 'now' ? 'checked' : '' }} onchange="toggleSchedule()"> Send now
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary); font-size: 0.85rem;">
                        <input type="radio" name="send_type" value="schedule" {{ $sendType == 'schedule' ? 'checked' : '' }} onchange="toggleSchedule()"> Schedule
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; color: var(--text-secondary); font-size: 0.85rem;">
                        <input type="radio" name="send_type" value="draft" {{ $sendType == 'draft' ? 'checked' : '' }} onchange="toggleSchedule()"> Save draft
                    </label>
                </div>
                <input type="datetime-local" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at', $announcement->scheduled_at?->format('Y-m-d\TH:i')) }}"
                    style="width: 100%; max-width: 300px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px; padding: 10px 16px; color: var(--text-primary); font-size: 0.9rem;">
            </div>

            <div style="margin-bottom: 18px; padding: 14px; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 10px;">
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 8px;">
                    <input type="checkbox" name="save_template" value="1" {{ old('save_template') ? 'checked' : '' }} onchange="document.getElementById('template-name-wrap').style.display = this.checked ? 'block' : 'none';"> Save as template
                </label>
                <div id="template-name-wrap" style="display: none; margin-top: 8px;">
                    <input type="text" name="template_name" value="{{ old('template_name') }}" placeholder="Template name"
                        style="width: 100%; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; color: var(--text-primary); font-size: 0.9rem;">
                </div>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" name="action" value="save" id="saveBtn">Update</button>
                @if(! $announcement->sent_at)
                    <form action="{{ route('admin.announcements.send', $announcement) }}" method="POST" style="display:inline;" onsubmit="return confirm('Send to all selected users?');">
                        @csrf
                        <button type="submit" class="btn btn-success">Send Now</button>
                    </form>
                @endif
                <button type="submit" class="btn btn-secondary" name="action" value="test" formmethod="POST" formaction="{{ route('admin.announcements.test') }}">Send Test to Me</button>
                <button type="button" class="btn btn-info" onclick="previewEmail()">Preview</button>
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
const toolbarOptions = [
    ['bold', 'italic', 'underline', 'strike'],
    ['blockquote', 'code-block'],
    [{ 'list': 'ordered' }, { 'list': 'bullet' }],
    [{ 'header': [1, 2, 3, false] }],
    [{ 'color': [] }, { 'background': [] }],
    ['link'],
    ['clean']
];

const quill = new Quill('#editor', { theme: 'snow', modules: { toolbar: toolbarOptions } });
const quillHi = new Quill('#editor_hi', { theme: 'snow', modules: { toolbar: toolbarOptions } });
const quillB = new Quill('#editor_b', { theme: 'snow', modules: { toolbar: toolbarOptions } });
const quillHiB = new Quill('#editor_hi_b', { theme: 'snow', modules: { toolbar: toolbarOptions } });

const form = document.getElementById('announcementForm');
form.addEventListener('submit', function() {
    document.getElementById('message').value = quill.root.innerHTML;
    document.getElementById('message_hi').value = quillHi.root.innerHTML;
    document.getElementById('message_b').value = quillB.root.innerHTML;
    document.getElementById('message_hi_b').value = quillHiB.root.innerHTML;
});

const notes = {
    'all': 'This announcement will be sent to all Believoo, B-Connect and GHC users.',
    'clients': 'This announcement will be sent to Believoo client users only.',
    'bconnect': 'This announcement will be sent to B-Connect workspace members.',
    'ghc': 'This announcement will be sent to GHC users only.'
};

const audience = document.getElementById('audience');
const note = document.getElementById('audience-note');
audience.addEventListener('change', function() {
    note.textContent = notes[this.value] || notes['all'];
    updateAudienceCount();
});

function updateAudienceCount() {
    const countEl = document.getElementById('audience-count');
    countEl.textContent = '...';
    fetch('{{ route('admin.announcements.audience-count') }}?audience=' + audience.value, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(d => countEl.textContent = d.count + ' users')
    .catch(() => countEl.textContent = '—');
}
updateAudienceCount();

function toggleSchedule() {
    const isSchedule = document.querySelector('input[name="send_type"]:checked').value === 'schedule';
    const input = document.getElementById('scheduled_at');
    input.style.display = isSchedule ? 'block' : 'none';
    input.required = isSchedule;
    document.getElementById('saveBtn').textContent = isSchedule ? 'Schedule' : 'Update';
}
toggleSchedule();

function toggleAbTest() {
    const enabled = document.querySelector('input[name="ab_enabled"]').checked;
    document.getElementById('ab-test-fields').style.display = enabled ? 'block' : 'none';
}
toggleAbTest();

function previewEmail() {
    const formData = new FormData(form);
    formData.append('message', quill.root.innerHTML);
    formData.append('message_hi', quillHi.root.innerHTML);
    formData.append('message_b', quillB.root.innerHTML);
    formData.append('message_hi_b', quillHiB.root.innerHTML);

    fetch('{{ route('admin.announcements.preview') }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(r => r.text())
    .then(html => {
        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(html);
        w.document.close();
    });
}

document.getElementById('template-select').addEventListener('change', function() {
    if (! this.value) return;
    fetch('{{ url('/admin/announcement-templates') }}/' + this.value + '/load')
    .then(r => r.json())
    .then(d => {
        document.querySelector('input[name="title"]').value = d.title;
        quill.root.innerHTML = d.message || '';
        quillHi.root.innerHTML = d.message_hi || '';
        document.querySelector('select[name="type"]').value = d.type;
        document.querySelector('select[name="locale"]').value = d.locale;
        updateAudienceCount();
    });
});
</script>
@endpush
@endsection
