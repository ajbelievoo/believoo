@extends('bconnect.layout')
@section('title', 'Tickets')
@section('content')
<div class="grid grid-cols-4 gap-6">
    <div class="col-span-3 bg-slate-900 rounded-xl border border-slate-800 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold">All Tickets</h3>
            <form method="GET" class="flex gap-2">
                <select name="status" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 rounded-lg text-sm p-2"><option value="">All Status</option><option value="open" {{ request('status')=='open' ? 'selected' : '' }}>Open</option><option value="in-progress" {{ request('status')=='in-progress' ? 'selected' : '' }}>In Progress</option><option value="testing" {{ request('status')=='testing' ? 'selected' : '' }}>Testing</option><option value="resolved" {{ request('status')=='resolved' ? 'selected' : '' }}>Resolved</option></select>
                <select name="project_id" onchange="this.form.submit()" class="bg-slate-800 border border-slate-600 rounded-lg text-sm p-2"><option value="">All Projects</option>@foreach($projects as $p)<option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach</select>
            </form>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Ticket</th><th>Project</th><th>Reporter</th><th>Status</th><th>Priority</th></tr></thead>
            <tbody>
                @forelse($tickets as $t)
                <tr class="border-b border-slate-800">
                    <td class="py-3"><a href="{{ route('bconnect.tickets.show', $t->id) }}" class="text-cyan-400 hover:underline">{{ $t->title }}</a></td>
                    <td>{{ $t->project?->name ?? '—' }}</td>
                    <td>{{ $t->reporter?->user?->name ?? '—' }}</td>
                    <td><span class="px-2 py-1 rounded text-[10px] font-bold bg-slate-700">{{ ucfirst($t->status) }}</span></td>
                    <td><span class="px-2 py-1 rounded text-[10px] font-bold {{ $t->priority == 'critical' ? 'bg-red-500/20 text-red-400' : ($t->priority == 'high' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-700') }}">{{ ucfirst($t->priority) }}</span></td>
                </tr>
                @empty<tr><td colspan="5" class="py-6 text-center text-slate-500">No tickets found.</td></tr>@endforelse
            </tbody>
        </table>
        {{ $tickets->links() }}
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">New Ticket</h3>
        <form method="POST" action="{{ route('bconnect.tickets.store') }}" enctype="multipart/form-data" class="space-y-4">@csrf
            <select name="project_id" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <input type="text" name="title" placeholder="Bug title" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
            <textarea name="description" rows="3" placeholder="Describe the issue..." required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"></textarea>
            <select name="type" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="bug">Bug</option><option value="feature">Feature</option><option value="task">Task</option><option value="support">Support</option></select>
            <select name="priority" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
            <input type="file" name="attachments[]" multiple class="block text-sm text-slate-400">
            <button type="submit" class="w-full py-2 bg-pink-500 text-white font-bold rounded-lg hover:bg-pink-400">Create (AI will analyze)</button>
        </form>
    </div>
</div>
@endsection
