@extends('bconnect.layout')
@section('title', 'Live Session Monitor')
@section('content')
<div class="bc-card overflow-hidden">
    <div class="p-6 border-b border-[var(--bc-border)] flex justify-between items-center">
        <h3 class="font-bold text-lg">Live remote sessions</h3>
        <button onclick="location.reload()" class="bc-btn bc-btn-secondary text-xs"><i class="fas fa-rotate mr-1"></i>Refresh</button>
    </div>
    <table class="bc-table">
        <thead><tr><th>Code</th><th>Host</th><th>Viewer</th><th>Type</th><th>Status</th><th>Started</th><th></th></tr></thead>
        <tbody>
            @forelse($live as $s)
            <tr>
                <td class="font-mono text-cyan-400">{{ $s->session_code }}</td>
                <td>{{ $s->host_label ?? '—' }}</td>
                <td>{{ $s->viewer_name ?? $s->viewer?->user?->name ?? '—' }}</td>
                <td class="text-slate-400">{{ $s->host_kind }}</td>
                <td><span class="bc-badge {{ $s->status === 'active' ? 'bc-badge-green' : 'bc-badge-amber' }}">{{ ucfirst($s->status) }}</span></td>
                <td class="text-xs text-slate-400">{{ $s->started_at?->diffForHumans() ?? '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('bconnect.admin.forceend', $s->id) }}" onsubmit="return confirm('Force-end this session?')">@csrf
                        <button class="bc-btn bc-btn-danger text-xs py-1 px-2">End</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="bc-empty">No live sessions right now.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>setTimeout(() => location.reload(), 15000);</script>
@endsection
