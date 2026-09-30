@extends('layouts.admin')

@section('title', 'AI Chat Conversations')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">AI Chat</h1>
        <p class="page-subtitle">Review AI conversations and user feedback</p>
    </div>
</div>

@include('admin.partials.alerts')

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">Total Messages</div>
                <div class="stat-value">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">Today</div>
                <div class="stat-value">{{ number_format($stats['today']) }}</div>
            </div>
            <div class="stat-icon green"><i class="fas fa-calendar-day"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">Sessions</div>
                <div class="stat-value">{{ number_format($stats['sessions']) }}</div>
            </div>
            <div class="stat-icon purple"><i class="fas fa-robot"></i></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-label">Avg Rating</div>
                <div class="stat-value">{{ $stats['avg_rating'] }}<span style="font-size: 1rem; color: var(--text-muted);">/5</span></div>
            </div>
            <div class="stat-icon yellow"><i class="fas fa-star"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-comments"></i>Conversations</div>
        <form method="get" class="table-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <input type="text" name="q" class="form-input" placeholder="Search message content" value="{{ request('q') }}" style="min-width: 220px;">
            <button class="btn btn-secondary">Search</button>
        </form>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Last active</th>
                        <th>Messages</th>
                        <th>User messages</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td><code style="color: var(--accent);">{{ Str::limit($session->session_id, 24) }}</code></td>
                            <td>{{ $session->last_message_at?->diffForHumans() ?? '—' }}</td>
                            <td>{{ $session->total_messages }}</td>
                            <td>{{ $session->user_messages }}</td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="{{ route('admin.ai-messages.show', $session->session_id) }}" class="btn btn-secondary btn-sm">View</a>
                                <form method="POST" action="{{ route('admin.ai-messages.destroy', $session->session_id) }}" style="display: inline;" onsubmit="return confirm('Delete this conversation?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state"><i class="fas fa-comments"></i><div>No conversations yet</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sessions->hasPages())
    <div class="card-footer">
        {{ $sessions->links() }}
    </div>
    @endif
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-star"></i>Recent Feedback</div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feedback as $f)
                        <tr>
                            <td>{{ Str::limit($f->aiMessage?->message ?? '—', 60) }}</td>
                            <td><span class="badge badge-warning">{{ $f->rating }}/5</span></td>
                            <td>{{ $f->comment ?? '—' }}</td>
                            <td>{{ $f->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><i class="fas fa-star"></i><div>No feedback yet</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($feedback->hasPages())
    <div class="card-footer">
        {{ $feedback->links() }}
    </div>
    @endif
</div>
@endsection
