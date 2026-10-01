@php
$progress = $sprint->progress;
$burndown = $sprint->burndown_data;
@endphp
@extends('bconnect.layout')
@section('title', $sprint->name)
@section('content')
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-2">
        <div>
            <h3 class="font-bold text-2xl">{{ $sprint->name }}</h3>
            <p class="text-slate-400 text-sm">{{ $sprint->project?->name }} &bull; {{ $sprint->start_date->format('M d') }} — {{ $sprint->end_date->format('M d') }}</p>
        </div>
        <div class="flex gap-2">
            <span class="bc-badge bc-badge-{{ $sprint->status == 'active' ? 'cyan' : ($sprint->status == 'completed' ? 'green' : 'red') }}">{{ ucfirst($sprint->status) }}</span>
            <a href="{{ route('bconnect.kanban', ['sprint_id' => $sprint->id, 'project' => $sprint->project_id]) }}" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-columns mr-1"></i>Sprint Board</a>
        </div>
    </div>
    <p class="text-slate-300 mt-2">{{ $sprint->goal ?? 'No goal set.' }}</p>
    <div class="mt-4 flex items-center gap-3">
        <div class="w-full max-w-md h-3 bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-cyan-500" style="width: {{ $progress['percent'] }}%"></div>
        </div>
        <span class="text-sm font-semibold">{{ $progress['percent'] }}%</span>
    </div>
    <p class="text-xs text-slate-400 mt-1">{{ $progress['done'] }} of {{ $progress['total'] }} tickets completed</p>

    <div class="grid md:grid-cols-4 gap-4 mt-6">
        <div class="bc-card p-4 text-center">
            <div class="text-2xl font-black text-cyan-400">{{ $progress['total'] }}</div>
            <div class="text-xs text-slate-400">Total Tickets</div>
        </div>
        <div class="bc-card p-4 text-center">
            <div class="text-2xl font-black text-green-400">{{ $progress['done'] }}</div>
            <div class="text-xs text-slate-400">Completed</div>
        </div>
        <div class="bc-card p-4 text-center">
            <div class="text-2xl font-black text-amber-400">{{ $sprint->total_estimated_hours }}h</div>
            <div class="text-xs text-slate-400">Estimated</div>
        </div>
        <div class="bc-card p-4 text-center">
            <div class="text-2xl font-black text-pink-400">{{ $sprint->total_logged_hours }}h</div>
            <div class="text-xs text-slate-400">Logged</div>
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bc-card p-6">
        <h4 class="font-bold mb-4">Burndown Chart</h4>
        @if(count($burndown['labels']))
        <div class="h-64 w-full">
            <svg viewBox="0 0 {{ count($burndown['labels']) * 60 }} 200" preserveAspectRatio="none" class="w-full h-full">
                @php
                $max = max(1, max(max($burndown['ideal']), max($burndown['actual'])));
                $pointsIdeal = '';
                $pointsActual = '';
                foreach($burndown['labels'] as $i => $label) {
                    $x = $i * 60 + 30;
                    $yIdeal = 200 - ($burndown['ideal'][$i] / $max * 180) - 10;
                    $yActual = 200 - ($burndown['actual'][$i] / $max * 180) - 10;
                    $pointsIdeal .= ($i ? ' ' : '') . $x . ',' . $yIdeal;
                    $pointsActual .= ($i ? ' ' : '') . $x . ',' . $yActual;
                }
                @endphp
                <polyline fill="none" stroke="rgba(148,163,184,0.4)" stroke-width="2" stroke-dasharray="4,4" points="{{ $pointsIdeal }}" />
                <polyline fill="none" stroke="{{ $bconnectBrand['brand_color_light'] ?? '#a78bfa' }}" stroke-width="3" points="{{ $pointsActual }}" />
                @foreach($burndown['labels'] as $i => $label)
                <text x="{{ $i * 60 + 30 }}" y="195" text-anchor="middle" font-size="10" fill="#64748b">{{ $label }}</text>
                @endforeach
            </svg>
        </div>
        <div class="flex gap-4 text-xs mt-2">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-cyan-400"></span>Actual remaining</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full border border-slate-400 border-dashed"></span>Ideal remaining</span>
        </div>
        @else
        <p class="text-slate-500">No data available for burndown.</p>
        @endif
    </div>
    <div class="bc-card p-6">
        <h4 class="font-bold mb-4">Backlog</h4>
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @forelse($sprint->tickets()->where('status', 'open')->orderBy('priority')->get() as $ticket)
            <a href="{{ route('bconnect.tickets.show', $ticket->id) }}" class="block p-3 bg-slate-800/50 rounded-lg hover:bg-slate-800 text-sm">
                <div class="flex justify-between items-start">
                    <span><span class="text-cyan-400">#{{ $ticket->id }}</span> {{ Str::limit($ticket->title, 35) }}</span>
                    <span class="bc-badge {{ $ticket->priority == 'critical' ? 'bc-badge-red' : ($ticket->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }} text-[10px]">{{ ucfirst($ticket->priority) }}</span>
                </div>
            </a>
            @empty
            <p class="text-slate-500 text-sm">No open backlog items.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="bc-card p-6">
    <h4 class="font-bold mb-4">Sprint Tickets</h4>
    <table class="bc-table">
        <thead>
            <tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Due</th><th>Est.</th><th>Logged</th></tr>
        </thead>
        <tbody>
            @forelse($sprint->tickets as $ticket)
            <tr>
                <td><a href="{{ route('bconnect.tickets.show', $ticket) }}" class="text-cyan-400 hover:underline font-medium">{{ $ticket->title }}</a></td>
                <td><span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></td>
                <td><span class="bc-badge {{ $ticket->priority == 'critical' ? 'bc-badge-red' : ($ticket->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($ticket->priority) }}</span></td>
                <td class="text-slate-400 text-sm">{{ $ticket->assignee?->user?->name ?? '—' }}</td>
                <td class="text-slate-400 text-sm">{{ $ticket->due_date?->format('M d') ?? '—' }}</td>
                <td class="text-slate-400 text-sm">{{ $ticket->estimated_hours ? $ticket->estimated_hours.'h' : '—' }}</td>
                <td class="text-slate-400 text-sm">{{ round($ticket->total_logged_seconds / 3600, 2) }}h</td>
            </tr>
            @empty
            <tr><td colspan="7" class="bc-empty">No tickets assigned to this sprint.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
