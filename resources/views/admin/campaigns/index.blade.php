@extends('layouts.admin')

@section('title', 'Email Campaigns')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Email Campaigns</h1>
        <p class="page-subtitle">Branded email blasts to clients &amp; newsletter subscribers</p>
    </div>
    <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i>New Campaign</a>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-envelope-open-text"></i>All Campaigns</div>
        <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="q" placeholder="Search name/subject" value="{{ request('q') }}" class="form-input" style="min-width: 220px;">
            <select name="status" onchange="this.form.submit()" class="form-select" style="width: auto;">
                <option value="">All Statuses</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-secondary"><i class="fas fa-filter"></i>Filter</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Segment</th>
                    <th>Sent</th>
                    <th>Opens</th>
                    <th>Clicks</th>
                    <th>Scheduled</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $campaign)
                    <tr>
                        <td style="color: var(--text-primary); font-weight: 600;">{{ $campaign->name }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($campaign->subject, 40) }}</td>
                        <td>
                            @php
                                $badge = match($campaign->status) {
                                    'sent' => 'badge-success',
                                    'sending' => 'badge-warning',
                                    'scheduled' => 'badge-info',
                                    default => 'badge-info',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($campaign->status) }}</span>
                        </td>
                        <td>{{ ucfirst($campaign->segment) }}</td>
                        <td>{{ number_format($campaign->sent_count) }}</td>
                        <td>{{ number_format($campaign->opened_count) }}</td>
                        <td>{{ number_format($campaign->clicked_count) }}</td>
                        <td>{{ $campaign->scheduled_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-secondary btn-sm">View</a>
                            <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @if(in_array($campaign->status, ['draft', 'scheduled']))
                                <form method="post" action="{{ route('admin.campaigns.send', $campaign) }}" style="display: inline;" onsubmit="return confirm('Send this campaign now?')">
                                    @csrf
                                    @method('patch')
                                    <button class="btn btn-primary btn-sm">Send</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('admin.campaigns.destroy', $campaign) }}" style="display: inline;" onsubmit="return confirm('Delete campaign?')">
                                @csrf
                                @method('delete')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="empty-state"><i class="fas fa-envelope-open-text"></i><div>No campaigns found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())
    <div class="card-footer">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>
@endsection
