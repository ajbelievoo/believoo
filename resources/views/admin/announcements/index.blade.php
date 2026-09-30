@extends('layouts.admin')

@section('title', 'Announcements')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Announcements</h1>
        <p class="page-subtitle">Broadcast updates to users and track engagement</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i>New Announcement
        </a>
        <a href="{{ route('admin.announcement-templates.index') }}" class="btn btn-secondary">
            <i class="fas fa-file-alt"></i>Templates
        </a>
        <a href="{{ route('admin.announcements.analytics') }}" class="btn btn-info">
            <i class="fas fa-chart-bar"></i>Analytics
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-bullhorn"></i>All Announcements</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Audience</th>
                        <th>Language</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Opens</th>
                        <th>Clicks</th>
                        <th>Sent</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                    <tr>
                        <td style="font-weight: 600;">
                            {{ $announcement->title }}
                            @if($announcement->ab_enabled)
                                <span class="badge badge-warning" style="margin-left: 6px;">A/B</span>
                            @endif
                        </td>
                        <td><span class="badge badge-info">{{ ucfirst($announcement->audience) }}</span></td>
                        <td>{{ strtoupper($announcement->locale) }}</td>
                        <td>{{ ucfirst($announcement->type) }}</td>
                        <td>
                            @if($announcement->ab_enabled)
                                @if($announcement->ab_status === 'testing')
                                    <span class="badge badge-info">A/B Testing</span>
                                @elseif($announcement->ab_status === 'completed')
                                    <span class="badge badge-success">A/B Completed</span>
                                    <small style="display: block; color: var(--text-muted);">Winner: {{ strtoupper($announcement->ab_winner) }}</small>
                                @elseif($announcement->ab_status === 'winner')
                                    <span class="badge badge-primary">A/B Winner Sending</span>
                                @else
                                    <span class="badge badge-slate">A/B Draft</span>
                                @endif
                            @elseif($announcement->sent_at)
                                <span class="badge badge-success">Sent</span>
                            @elseif($announcement->scheduled_at && $announcement->scheduled_at->isFuture())
                                <span class="badge badge-warning">Scheduled</span>
                            @else
                                <span class="badge badge-slate">Draft</span>
                            @endif
                        </td>
                        <td>{{ $announcement->recipients()->whereNotNull('opened_at')->count() }}</td>
                        <td>{{ $announcement->recipients()->whereNotNull('clicked_at')->count() }}</td>
                        <td>{{ $announcement->sent_at?->diffForHumans() ?? '—' }}</td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="{{ route('admin.announcements.logs', $announcement) }}" class="btn btn-info btn-sm">Logs</a>
                            @if(! $announcement->sent_at)
                            <form action="{{ route('admin.announcements.send', $announcement) }}" method="POST" style="display: inline;" onsubmit="return confirm('Send to {{ $announcement->recipients()->count() }} users?');">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-sm">Send</button>
                            </form>
                            @endif
                            <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="empty-state"><i class="fas fa-bullhorn"></i><div>No announcements yet</div></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($announcements->hasPages())
    <div class="card-footer">
        {{ $announcements->links() }}
    </div>
    @endif
</div>
@endsection
