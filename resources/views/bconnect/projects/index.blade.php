@extends('bconnect.layout')
@section('title', 'Projects')
@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="font-bold text-xl">Projects</h3>
    <a href="{{ route('bconnect.projects.create') }}" class="bc-btn bc-btn-primary"><i class="fas fa-plus"></i>New Project</a>
</div>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($projects as $p)
    <div class="bc-card p-5 bc-card-hover">
        <div class="flex justify-between items-start mb-3">
            <h4 class="font-bold text-lg text-cyan-400"><a href="{{ route('bconnect.projects.show', $p->id) }}">{{ $p->name }}</a></h4>
            <span class="bc-badge {{ $p->status == 'active' ? 'bc-badge-green' : 'bc-badge-slate' }}">{{ ucfirst($p->status) }}</span>
        </div>
        <p class="text-slate-400 text-sm line-clamp-2 mb-4">{{ $p->description }}</p>
        <div class="flex justify-between text-xs text-slate-500 border-t border-[var(--bc-border)] pt-3">
            <span><i class="fas fa-bug mr-1 text-pink-400"></i>{{ $p->tickets_count ?? $p->tickets()->count() }} tickets</span>
            <span><i class="fas fa-user mr-1 text-cyan-400"></i>{{ $p->client?->user?->name ?? 'No client' }}</span>
        </div>
    </div>
    @empty
    <div class="bc-empty col-span-full">No projects. Create your first project.</div>
    @endforelse
</div>
{{ $projects->links() }}
@endsection
