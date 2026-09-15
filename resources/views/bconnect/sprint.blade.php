@php $progress = $sprint->progress; @endphp
@extends('bconnect.layout')
@section('title', $sprint->name)
@section('content')
<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-2">
        <div>
            <h3 class="font-bold text-2xl">{{ $sprint->name }}</h3>
            <p class="text-slate-400 text-sm">{{ $sprint->project?->name }} &bull; {{ $sprint->start_date->format('M d') }} — {{ $sprint->end_date->format('M d') }}</p>
        </div>
        <span class="bc-badge bc-badge-{{ $sprint->status == 'active' ? 'cyan' : ($sprint->status == 'completed' ? 'green' : 'red') }}">{{ ucfirst($sprint->status) }}</span>
    </div>
    <p class="text-slate-300 mt-2">{{ $sprint->goal ?? 'No goal set.' }}</p>
    <div class="mt-4 flex items-center gap-3">
        <div class="w-full max-w-md h-3 bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-cyan-500" style="width: {{ $progress['percent'] }}%"></div>
        </div>
        <span class="text-sm font-semibold">{{ $progress['percent'] }}%</span>
    </div>
    <p class="text-xs text-slate-400 mt-1">{{ $progress['done'] }} of {{ $progress['total'] }} tickets completed</p>
</div>

<div class="bc-card p-6">
    <h4 class="font-bold mb-4">Sprint Tickets</h4>
    <table class="bc-table">
        <thead>
            <tr><th>Ticket</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Due</th></tr>
        </thead>
        <tbody>
            @forelse($sprint->tickets as $ticket)
            <tr>
                <td><a href="{{ route('bconnect.tickets.show', $ticket) }}" class="text-cyan-400 hover:underline font-medium">{{ $ticket->title }}</a></td>
                <td><span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></td>
                <td><span class="bc-badge {{ $ticket->priority == 'critical' ? 'bc-badge-red' : ($ticket->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($ticket->priority) }}</span></td>
                <td class="text-slate-400 text-sm">{{ $ticket->assignee?->user?->name ?? '—' }}</td>
                <td class="text-slate-400 text-sm">{{ $ticket->due_date?->format('M d') ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="bc-empty">No tickets assigned to this sprint.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
