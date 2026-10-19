@extends('layouts.admin')
@section('title', 'BMyDesk App Ratings')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title"><i class="fas fa-star" style="color:#fbbf24;margin-right:10px;"></i>BMyDesk App Ratings</h1>
        <p class="page-subtitle">Quit-time "How was the app?" feedback — Android, desktop and web clients.</p>
    </div>
</div>

<div class="stats-grid mb-6">
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-list"></i></div></div>
        <div class="stat-label">Total ratings</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon green"><i class="fas fa-star"></i></div></div>
        <div class="stat-label">Average</div>
        <div class="stat-value">{{ $stats['avg'] }} / 5</div>
    </div>
    @foreach(range(5, 1) as $s)
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon" style="background:rgba(251,191,36,.12);color:#fbbf24;"><i class="fas fa-star"></i></div></div>
        <div class="stat-label">{{ $s }} star</div>
        <div class="stat-value">{{ $stats['stars'][$s] ?? 0 }}</div>
    </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-comments"></i>Feedback</div>
        <form method="get" style="display:flex;gap:8px;align-items:center;">
            <select name="platform" class="form-select" onchange="this.form.submit()">
                <option value="">All platforms</option>
                @foreach($stats['platforms'] as $p => $c)
                    <option value="{{ $p }}" @selected(request('platform') === $p)>{{ $p }} ({{ $c }})</option>
                @endforeach
            </select>
            <select name="stars" class="form-select" onchange="this.form.submit()">
                <option value="">All stars</option>
                @foreach(range(5,1) as $s)<option value="{{ $s }}" @selected((string)request('stars') === (string)$s)>{{ $s }}★</option>@endforeach
            </select>
        </form>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr><th>When</th><th>Stars</th><th>Platform</th><th>Version</th><th>Session</th><th>Feedback</th><th>IP</th></tr></thead>
            <tbody>
            @forelse($ratings as $r)
                <tr>
                    <td>{{ $r->created_at }}</td>
                    <td style="color:#fbbf24;white-space:nowrap;">{{ str_repeat('★', $r->rating) . str_repeat('☆', 5 - $r->rating) }}</td>
                    <td>{{ $r->platform ?? '—' }}</td>
                    <td>{{ $r->version ?? '—' }}</td>
                    <td><code>{{ $r->session_code }}</code></td>
                    <td style="max-width:420px;">{{ $r->feedback ?: '—' }}</td>
                    <td style="color:var(--admin-muted,#64748b);font-size:12px;">{{ $r->ip }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;color:var(--admin-muted,#64748b);padding:40px;">No ratings yet — they appear when users rate the app on quit.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($ratings->hasPages())<div style="padding:14px;">{{ $ratings->links() }}</div>@endif
</div>
@endsection
