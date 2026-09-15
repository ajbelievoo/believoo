@extends('layouts.admin')

@section('title', 'AI Chat Conversations')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">AI Chat</h1>
    </div>

    @include('admin.partials.alerts')

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Total Messages</div>
                    <div class="h4">{{ number_format($stats['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Today</div>
                    <div class="h4">{{ number_format($stats['today']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Sessions</div>
                    <div class="h4">{{ number_format($stats['sessions']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Avg Rating</div>
                    <div class="h4">{{ $stats['avg_rating'] }} / 5</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <strong>Conversations</strong>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 mb-3">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control" placeholder="Search message content" value="{{ request('q') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100">Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Session</th>
                            <th>Last active</th>
                            <th>Messages</th>
                            <th>User messages</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td><code>{{ Str::limit($session->session_id, 24) }}</code></td>
                                <td>{{ $session->last_message_at?->diffForHumans() ?? '—' }}</td>
                                <td>{{ $session->total_messages }}</td>
                                <td>{{ $session->user_messages }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.ai-messages.show', $session->session_id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <form method="POST" action="{{ route('admin.ai-messages.destroy', $session->session_id) }}" class="d-inline" onsubmit="return confirm('Delete this conversation?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No conversations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sessions->links() }}
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong>Recent Feedback</strong>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr><th>Message</th><th>Rating</th><th>Comment</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        @forelse($feedback as $f)
                            <tr>
                                <td>{{ Str::limit($f->aiMessage?->message ?? '—', 60) }}</td>
                                <td>{{ $f->rating }}/5</td>
                                <td>{{ $f->comment ?? '—' }}</td>
                                <td>{{ $f->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">No feedback yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $feedback->links() }}
        </div>
    </div>
</div>
@endsection
