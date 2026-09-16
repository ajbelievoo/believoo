@extends('bconnect.layout')
@section('title', $ticket->title)
@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
        <div class="flex flex-col md:flex-row justify-between md:items-start gap-4 mb-4">
            <div>
                <h3 class="font-bold text-2xl">{{ $ticket->title }}</h3>
                <p class="text-slate-400 text-sm mt-1">#{{ $ticket->id }} • Reported by {{ $ticket->reporter?->user?->name ?? '—' }} on {{ $ticket->created_at->format('M d, Y') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('bconnect.tickets.edit', $ticket->id) }}" class="bc-btn bc-btn-secondary text-sm"><i class="fas fa-edit mr-1"></i>Edit</a>
                <form method="POST" action="{{ route('bconnect.tickets.destroy', $ticket->id) }}" onsubmit="return confirm('Delete this ticket?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="bc-btn bc-btn-danger text-sm"><i class="fas fa-trash mr-1"></i>Delete</button>
                </form>
            </div>
        </div>
        <div class="flex items-center gap-2 mb-6">
            <form method="POST" action="{{ route('bconnect.tickets.status', $ticket->id) }}" class="flex gap-2">@csrf @method('PUT')
                <select name="status" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 rounded-lg text-sm p-2">
                    <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ $ticket->status == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="testing" {{ $ticket->status == 'testing' ? 'selected' : '' }}>Testing</option>
                    <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </form>
            <span class="bc-badge {{ $ticket->priority == 'critical' ? 'bc-badge-red' : ($ticket->priority == 'high' ? 'bc-badge-amber' : 'bc-badge-slate') }}">{{ ucfirst($ticket->priority) }}</span>
            <span class="bc-badge bc-badge-slate">{{ ucfirst(str_replace('_',' ',$ticket->type)) }}</span>
        </div>
        <div class="prose prose-invert max-w-none"><p class="text-slate-300 whitespace-pre-line">{{ $ticket->description }}</p></div>

        @if($ticket->ai_summary)
        <div class="mt-6 p-4 rounded-lg bg-cyan-500/5 border border-cyan-500/20">
            <div class="text-cyan-400 text-sm font-bold mb-1"><i class="fas fa-robot mr-1"></i>AI Summary</div>
            <p class="text-slate-300 text-sm">{{ $ticket->ai_summary }}</p>
        </div>
        @endif

        @if(!empty($ticket->attachments))
        <div class="mt-6">
            <p class="text-sm font-bold text-slate-400 mb-2">Attachments</p>
            <div class="flex flex-wrap gap-2">
                @foreach($ticket->attachments as $a)
                <a href="{{ Storage::url($a) }}" target="_blank" class="px-3 py-1 bg-slate-800 rounded text-xs hover:bg-cyan-500/20">{{ basename($a) }}</a>
                @endforeach
            </div>
        </div>
        @endif

        @if($ticket->children->count())
        <div class="mt-6 border-t border-slate-800 pt-6">
            <h4 class="font-bold mb-3">Sub-tickets</h4>
            <div class="space-y-2">
                @foreach($ticket->children as $child)
                <a href="{{ route('bconnect.tickets.show', $child->id) }}" class="block p-3 bg-slate-800/50 rounded-lg hover:bg-slate-800 text-sm">
                    <span class="text-cyan-400">#{{ $child->id }}</span> {{ $child->title }}
                    <span class="text-xs text-slate-500 ml-2">{{ ucfirst(str_replace('_',' ',$child->status)) }}</span>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        <div class="mt-8 border-t border-slate-800 pt-6">
            <h4 class="font-bold mb-4">Discussion</h4>
            @foreach($comments as $c)
            <div class="mb-4 flex gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-xs font-bold">{{ substr($c->member->user->name,0,1) }}</div>
                <div class="flex-1 bg-slate-800/50 rounded-lg p-3">
                    <div class="text-sm font-bold">{{ $c->member->user->name }} <span class="text-slate-500 text-xs font-normal">{{ $c->created_at->diffForHumans() }}</span></div>
                    <p class="text-slate-300 text-sm mt-1">{{ $c->message }}</p>
                </div>
            </div>
            @endforeach
            <form method="POST" action="{{ route('bconnect.tickets.comment', $ticket->id) }}" enctype="multipart/form-data" class="mt-4">@csrf
                <textarea name="message" rows="2" placeholder="Write a comment..." class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></textarea>
                <input type="file" name="attachments[]" multiple class="block text-sm text-slate-400 mt-2">
                <button type="submit" class="mt-2 px-4 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Post Comment</button>
            </form>
        </div>
    </div>
    <div class="space-y-4">
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h4 class="font-bold mb-3">Details</h4>
            <div class="text-sm text-slate-400 space-y-2">
                <div class="flex justify-between"><span>Project</span><span class="text-white">{{ $ticket->project?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Assignee</span><span class="text-white">{{ $ticket->assignee?->user?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Sprint</span><span class="text-white">{{ $ticket->sprint?->name ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Parent</span><span class="text-white">{{ $ticket->parent?->title ? '#'.$ticket->parent->id.' '.$ticket->parent->title : '—' }}</span></div>
                <div class="flex justify-between"><span>Priority</span><span class="text-white">{{ ucfirst($ticket->priority) }}</span></div>
                <div class="flex justify-between"><span>AI Priority</span><span class="text-cyan-400">{{ ucfirst($ticket->ai_suggested_priority ?? '—') }}</span></div>
                <div class="flex justify-between"><span>Type</span><span class="text-white">{{ ucfirst(str_replace('_',' ',$ticket->type)) }}</span></div>
                <div class="flex justify-between"><span>Status</span><span class="text-white">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></div>
                <div class="flex justify-between"><span>Start</span><span class="text-white">{{ $ticket->start_date?->format('M d, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Due</span><span class=" {{ $ticket->due_date && $ticket->due_date->isPast() && $ticket->status != 'closed' && $ticket->status != 'resolved' ? 'text-red-400' : 'text-white' }}">{{ $ticket->due_date?->format('M d, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span>Estimated</span><span class="text-white">{{ $ticket->estimated_hours ? $ticket->estimated_hours.'h' : '—' }}</span></div>
                <div class="flex justify-between"><span>Logged</span><span class="text-white">{{ round($ticket->total_logged_seconds / 3600, 2) }}h</span></div>
            </div>
        </div>
        @if(!empty($ticket->ai_tags))
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h4 class="font-bold mb-2">AI Tags</h4>
            <div class="flex flex-wrap gap-2">
                @foreach($ticket->ai_tags as $tag)
                <span class="px-2 py-1 bg-slate-800 rounded text-xs">{{ $tag }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
