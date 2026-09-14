@extends('bconnect.layout')
@section('title', 'Meetings')
@section('content')
<div class="grid grid-cols-3 gap-6">
    <div class="col-span-2 bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Meetings</h3>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-slate-400 border-b border-slate-700"><th>Title</th><th>Room</th><th>Created</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($meetings as $m)
                <tr class="border-b border-slate-800">
                    <td class="py-3">{{ $m->title }}</td>
                    <td><code class="bg-slate-800 px-2 py-1 rounded text-xs">{{ $m->room_id }}</code></td>
                    <td>{{ $m->created_at->diffForHumans() }}</td>
                    <td>{{ $m->ended_at ? 'Ended' : 'Live' }}</td>
                    <td><a href="{{ route('bconnect.meeting.room', $m->room_id) }}" target="_blank" class="text-cyan-400 hover:underline">{{ $m->ended_at ? 'View Summary' : 'Join' }}</a></td>
                </tr>
                @empty<tr><td colspan="5" class="py-6 text-center text-slate-500">No meetings yet.</td></tr>@endforelse
            </tbody>
        </table>
        {{ $meetings->links() }}
    </div>
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-6">
        <h3 class="font-bold mb-4">Start Meeting</h3>
        <form method="POST" action="{{ route('bconnect.meetings.store') }}" class="space-y-4">@csrf
            <input type="text" name="title" placeholder="Meeting title" required class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white">
            <select name="project_id" class="w-full p-3 rounded-lg bg-slate-800 border border-slate-600 text-white"><option value="">— No Project —</option>@foreach(\App\Models\Bconnect\Project::where('company_id', request()->input('bconnect_company_id'))->get() as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <button type="submit" class="w-full py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400">Create Room</button>
        </form>
    </div>
</div>
@endsection
