@php
$columns = [
    'open' => ['label' => 'To Do', 'color' => 'slate'],
    'in_progress' => ['label' => 'In Progress', 'color' => 'cyan'],
    'testing' => ['label' => 'Testing', 'color' => 'amber'],
    'resolved' => ['label' => 'Resolved', 'color' => 'green'],
    'closed' => ['label' => 'Closed', 'color' => 'red'],
];
@endphp
@extends('bconnect.layout')
@section('title', ($project?->name ? $project->name . ' — ' : '') . 'Kanban')
@section('content')
<div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-4">
    <div>
        <h3 class="font-bold text-xl">Kanban Board</h3>
        <p class="text-sm text-slate-400">Drag tickets to move between columns.</p>
    </div>
    <form method="GET" class="flex flex-wrap gap-2" id="kanbanFilters">
        <select name="project" onchange="this.form.submit()" class="bc-input py-2 text-sm">
            <option value="">All projects</option>
            @foreach($projects as $p)
            <option value="{{ $p->id }}" {{ optional($project)->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="assignee_id" onchange="this.form.submit()" class="bc-input py-2 text-sm">
            <option value="">All assignees</option>
            <option value="unassigned" {{ request('assignee_id')=='unassigned' ? 'selected' : '' }}>Unassigned</option>
            @foreach($members as $m)
            <option value="{{ $m->id }}" {{ request('assignee_id') == $m->id ? 'selected' : '' }}>{{ $m->user->name }}</option>
            @endforeach
        </select>
        <select name="sprint_id" onchange="this.form.submit()" class="bc-input py-2 text-sm">
            <option value="">All sprints</option>
            @foreach($sprints as $s)
            <option value="{{ $s->id }}" {{ request('sprint_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="priority" onchange="this.form.submit()" class="bc-input py-2 text-sm">
            <option value="">All priorities</option>
            <option value="low" {{ request('priority')=='low' ? 'selected' : '' }}>Low</option>
            <option value="medium" {{ request('priority')=='medium' ? 'selected' : '' }}>Medium</option>
            <option value="high" {{ request('priority')=='high' ? 'selected' : '' }}>High</option>
            <option value="critical" {{ request('priority')=='critical' ? 'selected' : '' }}>Critical</option>
        </select>
        @if($project)
        <a href="{{ route('bconnect.projects.show', $project) }}" class="bc-btn bc-btn-secondary py-2 text-sm"><i class="fas fa-folder-open"></i>Project</a>
        @endif
        <a href="{{ route('bconnect.tickets') }}" class="bc-btn bc-btn-secondary py-2 text-sm"><i class="fas fa-list"></i>List</a>
    </form>
</div>

<div class="bc-kanban" id="kanbanBoard">
    @foreach($columns as $status => $meta)
    <div class="bc-card bc-kanban-column" data-status="{{ $status }}">
        <div class="bc-kanban-header">
            <span>{{ $meta['label'] }}</span>
            <span class="bc-badge bc-badge-{{ $meta['color'] }}" data-count="{{ count($tickets[$status] ?? []) }}">{{ count($tickets[$status] ?? []) }}</span>
        </div>
        <div class="bc-kanban-list" data-status="{{ $status }}">
            @foreach($tickets[$status] ?? [] as $ticket)
            <div class="bc-kanban-card" draggable="true" data-id="{{ $ticket->id }}" data-status="{{ $ticket->status }}">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <a href="{{ route('bconnect.tickets.show', $ticket->id) }}" class="text-cyan-400 hover:underline font-semibold text-sm leading-tight">{{ $ticket->title }}</a>
                    <span class="text-[10px] text-slate-500">#{{ $ticket->id }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-2 flex-wrap">
                    <span class="bc-badge {{ $ticket->priority == 'critical' ? 'bc-badge-red' : ($ticket->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($ticket->priority) }}</span>
                    <span>{{ ucfirst(str_replace('_',' ',$ticket->type)) }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500 mb-2">
                    <span>{{ $ticket->project?->name ?? '—' }}</span>
                    @if($ticket->assignee?->user)
                    <span class="px-1.5 py-0.5 bg-slate-800 rounded" title="{{ $ticket->assignee->user->name }}">{{ substr($ticket->assignee->user->name, 0, 2) }}</span>
                    @endif
                </div>
                <div class="flex items-center justify-between text-xs">
                    @if($ticket->sprint?->name)
                    <span class="text-slate-500" title="{{ $ticket->sprint->name }}"><i class="fas fa-running mr-1"></i>{{ Str::limit($ticket->sprint->name, 12) }}</span>
                    @else
                    <span></span>
                    @endif
                    @if($ticket->due_date)
                    <span class="text-[11px] {{ $ticket->due_date->isPast() && $ticket->status != 'closed' && $ticket->status != 'resolved' ? 'text-red-400' : 'text-slate-500' }}">
                        <i class="far fa-clock mr-1"></i>{{ $ticket->due_date->format('M d') }}
                    </span>
                    @endif
                </div>
            </div>
            @endforeach
            <div class="bc-kanban-dropzone" data-status="{{ $status }}"></div>
        </div>
    </div>
    @endforeach
</div>

<script>
(function() {
    const board = document.getElementById('kanbanBoard');
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let dragged = null;

    board.querySelectorAll('.bc-kanban-card').forEach(card => {
        card.addEventListener('dragstart', e => {
            dragged = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            dragged = null;
            board.querySelectorAll('.over').forEach(el => el.classList.remove('over'));
            persistOrder();
        });
    });

    board.querySelectorAll('.bc-kanban-list, .bc-kanban-dropzone').forEach(zone => {
        zone.addEventListener('dragover', e => {
            e.preventDefault();
            if (!dragged) return;
            zone.classList.add('over');
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('over'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('over');
            if (!dragged) return;
            const status = zone.dataset.status;
            const list = zone.closest('.bc-kanban-list') || zone;
            if (status) dragged.dataset.status = status;
            list.insertBefore(dragged, list.querySelector('.bc-kanban-dropzone'));
            updateBadges();
        });
    });

    function updateBadges() {
        board.querySelectorAll('.bc-kanban-column').forEach(col => {
            const status = col.dataset.status;
            const count = col.querySelectorAll('.bc-kanban-card').length;
            const badge = col.querySelector('.bc-kanban-header .bc-badge');
            if (badge) badge.textContent = count;
        });
    }

    function persistOrder() {
        const payload = [];
        board.querySelectorAll('.bc-kanban-list').forEach(list => {
            const status = list.dataset.status;
            list.querySelectorAll('.bc-kanban-card').forEach((card, idx) => {
                payload.push({ id: parseInt(card.dataset.id), status: status, position: idx });
            });
        });

        fetch('{{ route('bconnect.kanban.reorder') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ tickets: payload }),
        }).catch(err => console.error('Kanban reorder failed', err));
    }
})();
</script>
@endsection
