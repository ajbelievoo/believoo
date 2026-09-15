@extends('layouts.admin')

@section('title', 'Campaign: ' . $campaign->name)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $campaign->name }}</h1>
        <a href="{{ route('admin.campaigns.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Status</strong><br><span class="badge bg-{{ $campaign->status === 'sent' ? 'success' : 'secondary' }}">{{ ucfirst($campaign->status) }}</span></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Sent</strong><br>{{ number_format($campaign->sent_count) }}</div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Opens</strong><br>{{ number_format($campaign->opened_count) }}</div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><strong>Clicks</strong><br>{{ number_format($campaign->clicked_count) }}</div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">Preview</div>
        <div class="card-body">
            <p><strong>Subject:</strong> {{ $campaign->subject }}</p>
            <p><strong>From:</strong> {{ $campaign->from_name ?? config('mail.from.name') }} &lt;{{ $campaign->from_email ?? config('mail.from.address') }}&gt;</p>
            <p><strong>Segment:</strong> {{ ucfirst($campaign->segment) }}</p>
            <hr>
            <div class="border p-3 bg-light rounded">
                {!! $campaign->content_html !!}
            </div>
        </div>
    </div>

    <h4 class="mb-3">Recipients</h4>
    <div class="table-responsive">
        <table class="table table-sm table-hover">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Opened</th>
                    <th>Clicked</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recipients as $r)
                    <tr>
                        <td>{{ $r->email }}</td>
                        <td><span class="badge bg-{{ $r->status === 'sent' ? 'success' : ($r->status === 'bounced' ? 'danger' : 'secondary') }}">{{ ucfirst($r->status) }}</span></td>
                        <td>{{ $r->sent_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $r->opened_at?->format('d M Y H:i') ?? '-' }}</td>
                        <td>{{ $r->clicked_at?->format('d M Y H:i') ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No recipients yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $recipients->links() }}
</div>
@endsection
