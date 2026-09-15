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
<div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-6">
    <div>
        <h3 class="font-bold text-xl">Kanban Board</h3>
        <p class="text-sm text-slate-400">Drag tickets to move between columns. Right-click card for details.</p>
    </div>
    <form method="GET" class="flex gap-2" id="projectFilter">
        <select name="project" onchange="this.form.submit()" class="bc-input py-2 text-sm">
            <option value="">All projects</option>
            @foreach($projects as $p)
            <option value="{{ $p->id }}" {{ optional($project)->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        @if($project)
        <a href="{{ route('bconnect.projects.show', $project) }}" class="bc-btn bc-btn-secondary py-2 text-sm"><i class="fas fa-folder-open"></i>Project</a>
        @endif
    </form>
</div>

<div class="bc-kanban" id="kanbanBoard">
    @foreach($columns as $status => $meta)
    <div class="bc-card bc-kanban-column" data-status="{{ $status }}">
        <div class="bc-kanban-header">
            <span>{{ $meta['label'] }}</span>
            <span class="bc-badge bc-badge-{{ $meta['color'] }}">{{ count($tickets[$status] ?? []) }}</span>
        </div>
        <div class="bc-kanban-list" data-status="{{ $status }}">
            @foreach($tickets[$status] ?? [] as $ticket)
            <div class="bc-kanban-card" draggable="true" data-id="{{ $ticket->id }}" data-status="{{ $ticket->status }}">
                <div class="flex items-start justify-between gap-2 mb-2">
                    <a href="{{ route('bconnect.tickets.show', $ticket->id) }}" class="text-cyan-400 hover:underline font-semibold text-sm leading-tight">{{ $ticket->title }}</a>
                    <span class="text-[10px] text-slate-500">#{{ $ticket->id }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-400 mb-2">
                    <span class="bc-badge {{ $ticket->priority == 'critical' ? 'bc-badge-red' : ($ticket->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($ticket->priority) }}</span>
                    <span>{{ ucfirst(str_replace('_',' ',$ticket->type)) }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span>{{ $ticket->project?->name ?? '—' }}</span>
                    @if($ticket->assignee?->user)
                    <span title="{{ $ticket->assignee->user->name }}">{{ substr($ticket->assignee->user->name, 0, 2) }}</span>
                    @endif
                </div>
                @if($ticket->due_date)
                <div class="mt-2 text-[11px] {{ $ticket->due_date->isPast() ? 'text-red-400' : 'text-slate-500' }}">
                    <i class="far fa-clock mr-1"></i>{{ $ticket->due_date->format('M d') }}
                </div>
                @endif
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
