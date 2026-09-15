@extends('bconnect.layout')
@section('title', ($project?->name ? $project->name . ' — ' : '') . 'Sprints')
@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-6">
            <div>
                <h3 class="font-bold text-xl">Sprints</h3>
                <p class="text-sm text-slate-400">Plan releases, set goals, and track progress.</p>
            </div>
            <form method="GET" class="flex gap-2">
                <select name="project" onchange="this.form.submit()" class="bc-input py-2 text-sm">
                    <option value="">All projects</option>
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ optional($project)->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <table class="bc-table">
            <thead>
                <tr><th>Sprint</th><th>Project</th><th>Dates</th><th>Progress</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($sprints as $sprint)
                @php $progress = $sprint->progress; @endphp
                <tr>
                    <td><a href="{{ route('bconnect.sprints.show', $sprint) }}" class="text-cyan-400 hover:underline font-medium">{{ $sprint->name }}</a></td>
                    <td class="text-slate-400">{{ $sprint->project?->name ?? '—' }}</td>
                    <td class="text-slate-400 text-sm">{{ $sprint->start_date->format('M d') }} — {{ $sprint->end_date->format('M d') }}</td>
                    <td>
                        <div class="w-32 h-2 bg-slate-700 rounded-full overflow-hidden">
                            <div class="h-full bg-cyan-500" style="width: {{ $progress['percent'] }}%"></div>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $progress['done'] }}/{{ $progress['total'] }}</span>
                    </td>
                    <td><span class="bc-badge bc-badge-{{ $sprint->status == 'active' ? 'cyan' : ($sprint->status == 'completed' ? 'green' : 'red') }}">{{ ucfirst($sprint->status) }}</span></td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('bconnect.sprints.destroy', $sprint) }}" onsubmit="return confirm('Delete this sprint?')" class="inline">@csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-300 text-sm"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="bc-empty">No sprints yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $sprints->links() }}
    </div>

    @if(in_array($bconnectRole ?? '', ['company_admin','manager']))
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Create Sprint</h3>
        <form method="POST" action="{{ route('bconnect.sprints.store') }}" class="space-y-4">@csrf
            <select name="project_id" required class="bc-input">
                @foreach($projects as $p)
                <option value="{{ $p->id }}" {{ optional($project)->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
            <input type="text" name="name" placeholder="Sprint name" required class="bc-input">
            <textarea name="goal" rows="2" placeholder="Sprint goal" class="bc-input"></textarea>
            <div class="grid grid-cols-2 gap-2">
                <input type="date" name="start_date" required class="bc-input">
                <input type="date" name="end_date" required class="bc-input">
            </div>
            <button type="submit" class="bc-btn bc-btn-primary w-full"><i class="fas fa-plus"></i>Create Sprint</button>
        </form>
    </div>
    @endif
</div>
@endsection
