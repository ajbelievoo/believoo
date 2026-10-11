@extends('bconnect.layout')
@section('title', 'Device Fleet')
@section('content')
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="bc-card p-5"><div class="text-xs text-slate-500 mb-1">Registered devices</div><div class="text-2xl font-black">{{ $devices->count() }}</div></div>
    <div class="bc-card p-5"><div class="text-xs text-slate-500 mb-1">Online now</div><div class="text-2xl font-black text-green-400">{{ $devices->where('online', true)->count() }}</div></div>
    <div class="bc-card p-5"><div class="text-xs text-slate-500 mb-1">Platforms</div><div class="text-2xl font-black text-cyan-400">{{ $devices->groupBy('platform')->count() }}</div></div>
</div>

@if($versions->isNotEmpty())
<div class="bc-card p-6 mb-6">
    <h3 class="font-bold mb-4">Version distribution</h3>
    <div class="flex items-end gap-4 h-28">
        @php $max = max($versions->all() ?: [1]); @endphp
        @foreach($versions->sortDesc() as $v => $n)
        <div class="flex flex-col items-center gap-1 flex-1">
            <div class="w-full rounded-t bg-cyan-500/60" style="height:{{ max(6, round($n / $max * 90)) }}px"></div>
            <span class="text-[10px] text-slate-400">{{ $v ?: '?' }} · {{ $n }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="bc-card overflow-hidden">
    <table class="bc-table">
        <thead><tr><th>Device</th><th>Platform</th><th>Version</th><th>Code</th><th>Status</th><th>Last seen</th></tr></thead>
        <tbody>
            @forelse($devices as $d)
            <tr>
                <td class="font-medium">{{ $d->label ?? '—' }}<div class="text-[10px] text-slate-500 font-mono">{{ $d->device_id }}</div></td>
                <td class="text-slate-400">{{ $d->platform ?? '—' }}</td>
                <td class="text-slate-400">{{ $d->version ?? '—' }}</td>
                <td class="font-mono text-cyan-400">{{ $d->session?->session_code ?? '—' }}</td>
                <td><span class="bc-badge {{ $d->online ? 'bc-badge-green' : 'bc-badge-slate' }}">{{ $d->online ? 'Online' : 'Offline' }}</span>
                    @if($d->session?->status === 'active') <span class="bc-badge bc-badge-red">IN SESSION</span>@endif</td>
                <td class="text-slate-400 text-xs">{{ $d->last_seen_at ? \Carbon\Carbon::parse($d->last_seen_at)->diffForHumans() : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="bc-empty">No agents registered yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
