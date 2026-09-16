@php
$limitLabel = $ticketLimit === null ? 'Unlimited' : $ticketLimit;
@endphp
@extends('bconnect.layout')
@section('title', 'Tickets')
@section('content')
@if(!$canCreate)
<div class="bc-card p-4 mb-6 border-l-4 border-amber-500">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-circle text-amber-400 mt-1"></i>
        <div>
            <p class="font-bold text-amber-400">Ticket limit reached</p>
            <p class="text-sm text-slate-400">You have used {{ $ticketUsage }} of {{ $limitLabel }} tickets. <a href="{{ route('bconnect.billing.upgrade') }}" class="text-cyan-400 hover:underline">Upgrade plan</a> for unlimited tickets.</p>
        </div>
    </div>
</div>
@endif

<div class="grid lg:grid-cols-4 gap-6">
    <div class="lg:col-span-3 bc-card p-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-4">
            <h3 class="font-bold">All Tickets</h3>
            <form method="GET" class="flex flex-wrap gap-2">
                <select name="status" onchange="this.form.submit()" class="bc-input py-2 text-sm">
                    <option value="">All Status</option>
                    <option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status')=='in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="testing" {{ request('status')=='testing' ? 'selected' : '' }}>Testing</option>
                    <option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status')=='closed' ? 'selected' : '' }}>Closed</option>
                </select>
                <select name="priority" onchange="this.form.submit()" class="bc-input py-2 text-sm">
                    <option value="">All Priorities</option>
                    <option value="low" {{ request('priority')=='low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ request('priority')=='medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ request('priority')=='high' ? 'selected' : '' }}>High</option>
                    <option value="critical" {{ request('priority')=='critical' ? 'selected' : '' }}>Critical</option>
                </select>
                <select name="project_id" onchange="this.form.submit()" class="bc-input py-2 text-sm">
                    <option value="">All Projects</option>
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <select name="assignee_id" onchange="this.form.submit()" class="bc-input py-2 text-sm">
                    <option value="">All Assignees</option>
                    <option value="unassigned" {{ request('assignee_id')=='unassigned' ? 'selected' : '' }}>Unassigned</option>
                    @foreach($members as $m)
                    <option value="{{ $m->id }}" {{ request('assignee_id') == $m->id ? 'selected' : '' }}>{{ $m->user->name }}</option>
                    @endforeach
                </select>
                <select name="sprint_id" onchange="this.form.submit()" class="bc-input py-2 text-sm">
                    <option value="">All Sprints</option>
                    @foreach($sprints as $s)
                    <option value="{{ $s->id }}" {{ request('sprint_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
        <table class="bc-table">
            <thead><tr><th>Ticket</th><th>Project</th><th>Assignee</th><th>Sprint</th><th>Due</th><th>Status</th><th>Priority</th><th></th></tr></thead>
            <tbody>
                @forelse($tickets as $t)
                <tr>
                    <td>
                        <a href="{{ route('bconnect.tickets.show', $t->id) }}" class="text-cyan-400 hover:underline font-medium">{{ $t->title }}</a>
                        @if($t->estimated_hours)<span class="text-[10px] text-slate-500 ml-1">({{ $t->estimated_hours }}h)</span>@endif
                    </td>
                    <td class="text-slate-400">{{ $t->project?->name ?? '—' }}</td>
                    <td class="text-slate-400">{{ $t->assignee?->user?->name ?? '—' }}</td>
                    <td class="text-slate-400 text-xs">{{ $t->sprint?->name ?? '—' }}</td>
                    <td class="text-xs {{ $t->due_date && $t->due_date->isPast() && $t->status != 'closed' && $t->status != 'resolved' ? 'text-red-400' : 'text-slate-500' }}">{{ $t->due_date?->format('M d') ?? '—' }}</td>
                    <td><span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_', ' ', $t->status)) }}</span></td>
                    <td><span class="bc-badge {{ $t->priority == 'critical' ? 'bc-badge-red' : ($t->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($t->priority) }}</span></td>
                    <td>
                        <a href="{{ route('bconnect.tickets.edit', $t->id) }}" class="text-xs text-cyan-400 hover:underline">Edit</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="bc-empty">No tickets found.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $tickets->links() }}
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">New Ticket</h3>
        <form method="POST" action="{{ route('bconnect.tickets.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <select name="project_id" required class="bc-input" @if(!$canCreate) disabled @endif>
                @foreach($projects as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
            <input type="text" name="title" placeholder="Bug title" required class="bc-input" @if(!$canCreate) disabled @endif>
            <textarea name="description" rows="3" placeholder="Describe the issue..." required class="bc-input" @if(!$canCreate) disabled @endif></textarea>
            <div class="grid grid-cols-2 gap-2">
                <select name="type" class="bc-input" @if(!$canCreate) disabled @endif>
                    <option value="bug">Bug</option>
                    <option value="feature">Feature</option>
                    <option value="task">Task</option>
                    <option value="question">Question</option>
                    <option value="support">Support</option>
                </select>
                <select name="priority" class="bc-input" @if(!$canCreate) disabled @endif>
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <select name="assignee_id" class="bc-input" @if(!$canCreate) disabled @endif>
                <option value="">Assignee (optional)</option>
                @foreach($members as $m)
                <option value="{{ $m->id }}">{{ $m->user->name }}</option>
                @endforeach
            </select>
            <select name="sprint_id" class="bc-input" @if(!$canCreate) disabled @endif>
                <option value="">Sprint (optional)</option>
                @foreach($sprints as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
            <div class="grid grid-cols-2 gap-2">
                <input type="date" name="start_date" class="bc-input text-sm" @if(!$canCreate) disabled @endif>
                <input type="date" name="due_date" class="bc-input text-sm" @if(!$canCreate) disabled @endif>
            </div>
            <input type="number" step="0.01" name="estimated_hours" placeholder="Estimated hours" class="bc-input" @if(!$canCreate) disabled @endif>
            <input type="file" name="attachments[]" multiple class="bc-input text-sm" @if(!$canCreate) disabled @endif>
            <button type="submit" class="bc-btn bc-btn-primary w-full" @if(!$canCreate) disabled @endif><i class="fas fa-plus"></i>Create Ticket</button>
        </form>
    </div>
</div>
@endsection
