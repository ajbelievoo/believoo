@extends('layouts.admin')

@section('title', 'Email Campaigns')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Email Campaigns</h1>
        <a href="{{ route('admin.campaigns.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> New Campaign
        </a>
    </div>

    @include('admin.partials.alerts')

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="get" class="row g-3 mb-3">
                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" placeholder="Search name/subject" value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $s)
                            <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
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
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                            <tr>
                                <td>{{ $campaign->name }}</td>
                                <td>{{ $campaign->subject }}</td>
                                <td><span class="badge bg-{{ $campaign->status === 'sent' ? 'success' : ($campaign->status === 'sending' ? 'warning' : 'secondary') }}">{{ ucfirst($campaign->status) }}</span></td>
                                <td>{{ ucfirst($campaign->segment) }}</td>
                                <td>{{ number_format($campaign->sent_count) }}</td>
                                <td>{{ number_format($campaign->opened_count) }}</td>
                                <td>{{ number_format($campaign->clicked_count) }}</td>
                                <td>{{ $campaign->scheduled_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.campaigns.show', $campaign) }}" class="btn btn-sm btn-info">View</a>
                                    <a href="{{ route('admin.campaigns.edit', $campaign) }}" class="btn btn-sm btn-secondary">Edit</a>
                                    @if(in_array($campaign->status, ['draft', 'scheduled']))
                                        <form method="post" action="{{ route('admin.campaigns.send', $campaign) }}" class="d-inline" onsubmit="return confirm('Send this campaign now?')">
                                            @csrf
                                            @method('patch')
                                            <button class="btn btn-sm btn-success">Send</button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('admin.campaigns.destroy', $campaign) }}" class="d-inline" onsubmit="return confirm('Delete campaign?')">
                                        @csrf
                                        @method('delete')
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No campaigns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $campaigns->links() }}
        </div>
    </div>
</div>
@endsection
