@extends('bconnect.layout')
@section('title', 'Attendance: ' . $meeting->title)
@section('content')
<div class="bc-card overflow-hidden">
    <div class="p-6 border-b border-[var(--bc-border)] flex justify-between items-center">
        <div>
            <h3 class="font-bold text-lg">Attendance — {{ $meeting->title }}</h3>
            <p class="text-xs text-slate-500">{{ $rows->count() }} participant(s) tracked</p>
        </div>
        <a href="{{ route('bconnect.meeting.room', $room) }}" class="bc-btn bc-btn-secondary text-sm">Back to meeting</a>
    </div>
    <table class="bc-table">
        <thead><tr><th>Name</th><th>Status</th><th>Joined</th><th>Left</th><th>Duration</th></tr></thead>
        <tbody>
            @forelse($rows as $p)
            <tr>
                <td class="font-medium">{{ $p->name }}</td>
                <td><span class="bc-badge {{ $p->status === 'joined' ? 'bc-badge-green' : ($p->status === 'denied' ? 'bc-badge-red' : ($p->status === 'waiting' ? 'bc-badge-amber' : 'bc-badge-slate')) }}">{{ ucfirst($p->status) }}</span></td>
                <td class="text-slate-400 text-xs">{{ $p->joined_at ? \Carbon\Carbon::parse($p->joined_at)->format('H:i:s') : '—' }}</td>
                <td class="text-slate-400 text-xs">{{ $p->left_at ? \Carbon\Carbon::parse($p->left_at)->format('H:i:s') : '—' }}</td>
                <td class="text-slate-400 text-xs">{{ $p->joined_at ? \Carbon\Carbon::parse($p->left_at ?? now())->diffInMinutes($p->joined_at) . ' min' : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="bc-empty">No attendance records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
