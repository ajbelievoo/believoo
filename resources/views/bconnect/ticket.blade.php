@extends('bconnect.layout')
@section('title', $ticket->title)
@section('content')
<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-bold text-2xl">{{ $ticket->title }}</h3>
                <p class="text-slate-400 text-sm mt-1">#{{ $ticket->id }} • Reported by {{ $ticket->reporter?->user?->name ?? '—' }}</p>
            </div>
            <form method="POST" action="{{ route('bconnect.tickets.status', $ticket->id) }}" class="flex gap-2">@csrf @method('PUT')
                <select name="status" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 rounded-lg text-sm p-2">
                    <option value="open" {{ $ticket->status == 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in-progress" {{ $ticket->status == 'in-progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="testing" {{ $ticket->status == 'testing' ? 'selected' : '' }}>Testing</option>
                    <option value="resolved" {{ $ticket->status == 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ $ticket->status == 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </form>
        </div>
        <div class="prose prose-invert max-w-none"><p class="text-slate-300">{{ $ticket->description }}</p></div>
        @if($ticket->ai_summary)<div class="mt-4 p-4 rounded-lg bg-cyan-500/5 border border-cyan-500/20"><div class="text-cyan-400 text-sm font-bold mb-1"><i class="fas fa-robot mr-1"></i>AI Summary</div><p class="text-slate-300 text-sm">{{ $ticket->ai_summary }}</p></div>@endif
        @if(!empty($ticket->attachments))
        <div class="mt-4"><p class="text-sm font-bold text-slate-400 mb-2">Attachments</p><div class="flex gap-2">@foreach($ticket->attachments as $a)<a href="{{ Storage::url($a) }}" target="_blank" class="px-3 py-1 bg-slate-800 rounded text-xs hover:bg-cyan-500/20">View File</a>@endforeach</div></div>
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
                <div class="flex justify-between"><span>Priority</span><span class="text-white">{{ ucfirst($ticket->priority) }}</span></div>
                <div class="flex justify-between"><span>AI Priority</span><span class="text-cyan-400">{{ ucfirst($ticket->ai_suggested_priority ?? '—') }}</span></div>
                <div class="flex justify-between"><span>Type</span><span class="text-white">{{ ucfirst($ticket->type) }}</span></div>
                <div class="flex justify-between"><span>Status</span><span class="text-white">{{ ucfirst($ticket->status) }}</span></div>
            </div>
        </div>
        @if(!empty($ticket->ai_tags))
        <div class="bg-slate-900 rounded-xl border border-slate-800 p-5">
            <h4 class="font-bold mb-2">AI Tags</h4>
            <div class="flex flex-wrap gap-2">@foreach($ticket->ai_tags as $tag)<span class="px-2 py-1 bg-slate-800 rounded text-xs">{{ $tag }}</span>@endforeach</div>
        </div>
        @endif
    </div>
</div>
@endsection
