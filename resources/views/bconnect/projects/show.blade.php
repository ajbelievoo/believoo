@extends('bconnect.layout')
@section('title', $project->name)
@section('content')
<div class="bg-slate-900 rounded-xl border border-slate-800 p-6 mb-6">
    <div class="flex justify-between items-start">
        <div>
            <h3 class="font-bold text-2xl">{{ $project->name }}</h3>
            <p class="text-slate-400 mt-2">{{ $project->description }}</p>
        </div>
        <span class="px-3 py-1 rounded-full bg-slate-800 text-xs">{{ ucfirst($project->status) }}</span>
    </div>
</div>
<div class="flex gap-3 mb-6">
    <a href="{{ route('bconnect.projects.chat', $project->id) }}" class="px-4 py-2 bg-cyan-500/20 text-cyan-400 rounded-lg font-bold text-sm hover:bg-cyan-500/30"><i class="fas fa-comments mr-1"></i>Project Chat</a>
    <a href="{{ route('bconnect.projects.whiteboard', $project->id) }}" class="px-4 py-2 bg-pink-500/20 text-pink-400 rounded-lg font-bold text-sm hover:bg-pink-500/30"><i class="fas fa-chalkboard mr-1"></i>Whiteboard</a>
    <a href="{{ route('bconnect.meetings') }}?project_id={{ $project->id }}" class="px-4 py-2 bg-green-500/20 text-green-400 rounded-lg font-bold text-sm hover:bg-green-500/30"><i class="fas fa-video mr-1"></i>Start Meeting</a>
</div>
<div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
    <h4 class="font-bold mb-4">Tickets</h4>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Title</th><th>Status</th><th>Priority</th></tr></thead>
        <tbody>
            @forelse($project->tickets as $t)
            <tr class="border-b border-slate-800"><td class="py-2"><a href="{{ route('bconnect.tickets.show', $t->id) }}" class="text-cyan-400 hover:underline">{{ $t->title }}</a></td><td>{{ ucfirst($t->status) }}</td><td>{{ ucfirst($t->priority) }}</td></tr>
            @empty<tr><td colspan="3" class="py-4 text-slate-500">No tickets yet.</td></tr>@endforelse
        </tbody>
    </table>
</div>
@endsection
