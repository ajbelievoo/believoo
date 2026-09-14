@extends('layouts.admin')

@section('title', 'Agent Leaderboard')

@section('content')
<div class="page-header">
    <h1 class="page-title">Agent Leaderboard</h1>
    <p class="page-subtitle">Last 7 days performance</p>
</div>

<div class="data-table">
    <div class="table-header"><h3 class="table-title">Rankings</h3></div>
    <table>
        <thead>
            <tr>
                <th>Rank</th>
                <th>Agent</th>
                <th>Chats</th>
                <th>Closed</th>
                <th>Avg Rating</th>
                <th>Avg First Reply</th>
                <th>CSAT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agents as $i => $a)
            <tr>
                <td>
                    @if($i === 0) <span class="badge badge-warning">🥇 1st</span>
                    @elseif($i === 1) <span class="badge badge-secondary">🥈 2nd</span>
                    @elseif($i === 2) <span class="badge badge-info">🥉 3rd</span>
                    @else #{{ $i + 1 }}
                    @endif
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        @if($a->photo)
                            <img src="{{ asset('storage/' . $a->photo) }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                        @else
                            <div style="width:32px;height:32px;border-radius:50%;background:#f59e0b;display:flex;align-items:center;justify-content:center;color:#000;font-weight:800;font-size:0.8rem;">{{ substr($a->display_name ?: $a->name, 0, 1) }}</div>
                        @endif
                        <div>
                            <div style="font-weight:700;">{{ $a->display_name ?: $a->name }}</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">{{ $a->email }}</div>
                        </div>
                    </div>
                </td>
                <td>{{ $a->total_chats }}</td>
                <td>{{ $a->closed_chats }}</td>
                <td>{{ $a->avg_rating ? number_format($a->avg_rating, 1) . '⭐' : '—' }}</td>
                <td>{{ $a->avg_first_reply ? round($a->avg_first_reply) . 's' : '—' }}</td>
                <td>
                    @if($a->satisfaction !== null)
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:60px;height:6px;background:var(--bg-tertiary);border-radius:3px;overflow:hidden;">
                                <div style="width:{{ $a->satisfaction }}%;height:100%;background:{{ $a->satisfaction >= 80 ? '#22c55e' : ($a->satisfaction >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
                            </div>
                            <span style="font-size:0.8rem;">{{ $a->satisfaction }}%</span>
                        </div>
                    @else —
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No agents yet</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
