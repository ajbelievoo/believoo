@extends('layouts.admin')

@section('title', 'Agent Performance')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ $supportAgent->display_name ?: $supportAgent->name }}</h1>
    <p class="page-subtitle">Chat performance & compliance report</p>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;">
    <div class="data-table" style="padding:20px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:var(--primary);">{{ $supportAgent->total_chats }}</div>
        <div style="font-size:0.75rem;color:var(--text-muted);">Total Chats</div>
    </div>
    <div class="data-table" style="padding:20px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:#f59e0b;">{{ $supportAgent->late_replies }}</div>
        <div style="font-size:0.75rem;color:var(--text-muted);">Late First Replies (>{{ \App\Models\ChatAssignment::SLA_SECONDS }}s)</div>
    </div>
    <div class="data-table" style="padding:20px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:#ef4444;">{{ $supportAgent->unpermitted_closes }}</div>
        <div style="font-size:0.75rem;color:var(--text-muted);">Closed w/o Consent (ZTP)</div>
    </div>
    <div class="data-table" style="padding:20px;text-align:center;">
        <div style="font-size:1.8rem;font-weight:800;color:#22c55e;">{{ $supportAgent->avg_rating ? number_format($supportAgent->avg_rating,1) : '—' }}</div>
        <div style="font-size:0.75rem;color:var(--text-muted);">Avg Rating ({{ $supportAgent->rating_count }} ratings)</div>
    </div>
</div>

<div class="data-table">
    <div class="table-header"><h3 class="table-title">Chat History</h3></div>
    <table>
        <thead>
            <tr>
                <th>Session</th>
                <th>Status</th>
                <th>First Reply</th>
                <th>Closed By</th>
                <th>ZTP</th>
                <th>Client Rating</th>
                <th>Feedback</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assignments as $a)
            <tr>
                <td style="font-family:monospace;font-size:0.75rem;">{{ Str::limit($a->session_id, 12) }}</td>
                <td><span class="badge badge-{{ $a->status === 'closed' ? 'secondary' : ($a->status === 'active' ? 'success' : 'warning') }}">{{ $a->status }}</span></td>
                <td>
                    @if($a->first_reply_seconds !== null)
                        <span style="color:{{ $a->first_reply_seconds > \App\Models\ChatAssignment::SLA_SECONDS ? '#ef4444' : '#22c55e' }};font-weight:600;">
                            {{ $a->first_reply_seconds }}s
                        </span>
                    @else
                        <span style="color:var(--text-muted);">No reply yet</span>
                    @endif
                </td>
                <td>{{ $a->closed_by ?? '—' }}</td>
                <td>
                    @if($a->closed_without_consent)
                        <span class="badge badge-danger">ZTP</span>
                    @else
                        <span style="color:var(--text-muted);">—</span>
                    @endif
                </td>
                <td>{{ $a->client_rating ? $a->client_rating . ' ★' : '—' }}</td>
                <td style="font-size:0.8rem;color:var(--text-secondary);">{{ Str::limit($a->client_feedback, 50) ?? '—' }}</td>
                <td style="font-size:0.8rem;">{{ $a->assigned_at->format('M d, H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">No chat assignments yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top:20px;">
    <a href="{{ route('admin.support-agents.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Team</a>
</div>
@endsection
