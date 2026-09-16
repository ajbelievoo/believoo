@extends('bconnect.layout')
@section('title', 'Edit Ticket: ' . $ticket->title)
@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-xl">Edit Ticket #{{ $ticket->id }}</h3>
        <a href="{{ route('bconnect.tickets.show', $ticket->id) }}" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>
    <div class="bc-card p-6">
        <form method="POST" action="{{ route('bconnect.tickets.update', $ticket->id) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf @method('PUT')
            <div class="grid md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-400 mb-1">Title</label>
                    <input type="text" name="title" value="{{ old('title', $ticket->title) }}" required class="bc-input w-full">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Project</label>
                    <select name="project_id" required class="bc-input w-full">
                        @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ old('project_id', $ticket->project_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Status</label>
                    <select name="status" required class="bc-input w-full">
                        <option value="open" {{ old('status', $ticket->status) == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="in_progress" {{ old('status', $ticket->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="testing" {{ old('status', $ticket->status) == 'testing' ? 'selected' : '' }}>Testing</option>
                        <option value="resolved" {{ old('status', $ticket->status) == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        <option value="closed" {{ old('status', $ticket->status) == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Type</label>
                    <select name="type" required class="bc-input w-full">
                        <option value="bug" {{ old('type', $ticket->type) == 'bug' ? 'selected' : '' }}>Bug</option>
                        <option value="feature" {{ old('type', $ticket->type) == 'feature' ? 'selected' : '' }}>Feature</option>
                        <option value="task" {{ old('type', $ticket->type) == 'task' ? 'selected' : '' }}>Task</option>
                        <option value="question" {{ old('type', $ticket->type) == 'question' ? 'selected' : '' }}>Question</option>
                        <option value="support" {{ old('type', $ticket->type) == 'support' ? 'selected' : '' }}>Support</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Priority</label>
                    <select name="priority" required class="bc-input w-full">
                        <option value="low" {{ old('priority', $ticket->priority) == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', $ticket->priority) == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority', $ticket->priority) == 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority', $ticket->priority) == 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Assignee</label>
                    <select name="assignee_id" class="bc-input w-full">
                        <option value="">Unassigned</option>
                        @foreach($members as $m)
                        <option value="{{ $m->id }}" {{ old('assignee_id', $ticket->assignee_id) == $m->id ? 'selected' : '' }}>{{ $m->user->name }} ({{ ucfirst(str_replace('_',' ',$m->role)) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Sprint</label>
                    <select name="sprint_id" class="bc-input w-full">
                        <option value="">No sprint</option>
                        @foreach($sprints as $s)
                        <option value="{{ $s->id }}" {{ old('sprint_id', $ticket->sprint_id) == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->start_date?->format('M d') }}{{ $s->end_date ? ' - '.$s->end_date->format('M d') : '' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Parent Ticket</label>
                    <select name="parent_id" class="bc-input w-full">
                        <option value="">None</option>
                        @foreach($projects as $p)
                        <optgroup label="{{ $p->name }}">
                            @foreach($p->tickets()->where('id', '!=', $ticket->id)->get() as $t)
                            <option value="{{ $t->id }}" {{ old('parent_id', $ticket->parent_id) == $t->id ? 'selected' : '' }}>#{{ $t->id }} {{ $t->title }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $ticket->start_date?->format('Y-m-d')) }}" class="bc-input w-full">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $ticket->due_date?->format('Y-m-d')) }}" class="bc-input w-full">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Estimated Hours</label>
                    <input type="number" step="0.01" name="estimated_hours" value="{{ old('estimated_hours', $ticket->estimated_hours) }}" class="bc-input w-full">
                </div>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Description</label>
                <textarea name="description" rows="5" required class="bc-input w-full">{{ old('description', $ticket->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">New Attachments</label>
                <input type="file" name="attachments[]" multiple class="bc-input text-sm w-full">
            </div>
            @if(!empty($ticket->attachments))
            <div>
                <label class="block text-sm text-slate-400 mb-2">Existing Attachments</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($ticket->attachments as $a)
                    <label class="flex items-center gap-2 px-3 py-2 bg-slate-800 rounded-lg text-xs text-slate-300">
                        <input type="checkbox" name="remove_attachments[]" value="{{ $a }}">
                        <a href="{{ Storage::url($a) }}" target="_blank" class="text-cyan-400 hover:underline">{{ basename($a) }}</a>
                    </label>
                    @endforeach
                </div>
                <p class="text-xs text-slate-500 mt-1">Check to remove attachment.</p>
            </div>
            @endif
            <div class="flex gap-3">
                <button type="submit" class="bc-btn bc-btn-primary"><i class="fas fa-save mr-1"></i>Update Ticket</button>
            </div>
        </form>
        <form method="POST" action="{{ route('bconnect.tickets.destroy', $ticket->id) }}" onsubmit="return confirm('Delete this ticket?')" class="mt-4">
            @csrf @method('DELETE')
            <button type="submit" class="bc-btn bc-btn-danger"><i class="fas fa-trash mr-1"></i>Delete Ticket</button>
        </form>
    </div>
</div>
@endsection
