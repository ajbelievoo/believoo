@extends('bconnect.layout')
@section('title', 'Projects')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="font-bold text-xl">Projects</h3>
    <a href="{{ route('bconnect.projects.create') }}" class="px-4 py-2 bg-cyan-500 text-slate-900 font-bold rounded-lg hover:bg-cyan-400"><i class="fas fa-plus mr-1"></i>New Project</a>
</div>
<div class="grid grid-cols-3 gap-4">
    @forelse($projects as $p)
    <div class="bg-slate-900 rounded-xl border border-slate-800 p-5 hover:border-cyan-500/50">
        <h4 class="font-bold text-lg text-cyan-400"><a href="{{ route('bconnect.projects.show', $p->id) }}">{{ $p->name }}</a></h4>
        <p class="text-slate-400 text-sm mt-2 line-clamp-2">{{ $p->description }}</p>
        <div class="mt-4 flex justify-between text-xs text-slate-500">
            <span>{{ $p->tickets_count ?? $p->tickets()->count() }} tickets</span>
            <span class="px-2 py-0.5 rounded bg-slate-800">{{ ucfirst($p->status) }}</span>
        </div>
    </div>
    @empty
    <p class="text-slate-500 col-span-3">No projects. Create your first project.</p>
    @endforelse
</div>
{{ $projects->links() }}
@endsection
