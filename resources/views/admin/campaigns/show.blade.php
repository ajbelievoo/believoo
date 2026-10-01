@extends('layouts.admin')

@section('title', 'Campaign: ' . $campaign->name)

@section('content')
<div class="page-header" style="display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
    <div>
        <h1 class="page-title">{{ $campaign->name }}</h1>
        <p class="page-subtitle">{{ $campaign->subject }}</p>
    </div>
    <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

@include('admin.partials.alerts')

<div class="stats-grid" style="margin-bottom: 24px;">
    <div class="stat-card">
        <div class="stat-label">Status</div>
        <div style="margin-top: 8px;">
            <span class="badge {{ $campaign->status === 'sent' ? 'badge-success' : ($campaign->status === 'sending' ? 'badge-warning' : 'badge-info') }}">{{ ucfirst($campaign->status) }}</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sent</div>
        <div class="stat-value">{{ number_format($campaign->sent_count) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Opens</div>
        <div class="stat-value">{{ number_format($campaign->opened_count) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Clicks</div>
        <div class="stat-value">{{ number_format($campaign->clicked_count) }}</div>
    </div>
</div>

<div class="data-table" style="margin-bottom: 24px;">
    <div class="table-header"><h3 class="table-title">Preview</h3></div>
    <div style="padding: 24px;">
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 6px;"><strong style="color: var(--text-primary);">Subject:</strong> {{ $campaign->subject }}</p>
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 6px;"><strong style="color: var(--text-primary);">From:</strong> {{ $campaign->from_name ?? config('mail.from.name') }} &lt;{{ $campaign->from_email ?? config('mail.from.address') }}&gt;</p>
        <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 16px;"><strong style="color: var(--text-primary);">Segment:</strong> {{ ucfirst($campaign->segment) }}</p>
        <div style="background: #ffffff; color: #111111; border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
            {!! $campaign->content_html !!}
        </div>
    </div>
</div>

<div class="data-table">
    <div class="table-header"><h3 class="table-title">Recipients</h3></div>
    <div style="overflow-x: auto;">
        <table>
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
                        <td style="color: var(--text-primary);">{{ $r->email }}</td>
                        <td><span class="badge {{ $r->status === 'sent' ? 'badge-success' : ($r->status === 'bounced' ? 'badge-danger' : 'badge-info') }}">{{ ucfirst($r->status) }}</span></td>
                        <td>{{ $r->sent_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $r->opened_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>{{ $r->clicked_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">No recipients yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div style="padding: 16px 22px;">
        {{ $recipients->links() }}
    </div>
</div>
@endsection
