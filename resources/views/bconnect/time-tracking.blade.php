@extends('bconnect.layout')
@section('title', ($project?->name ? $project->name . ' — ' : '') . 'Time Tracking')
@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-3 mb-6">
            <div>
                <h3 class="font-bold text-xl">Time Tracking</h3>
                <p class="text-sm text-slate-400">Log billable and non-billable hours.</p>
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

        @if($activeEntry)
        <div class="mb-6 p-4 rounded-xl border border-green-500/20 bg-green-500/10 flex items-center justify-between">
            <div>
                <p class="text-green-400 font-bold text-sm"><i class="fas fa-circle text-[8px] mr-2"></i>Timer running</p>
                <p class="text-slate-300 text-sm">{{ $activeEntry->description ?? 'No description' }} — {{ $activeEntry->project?->name }}</p>
                <p class="text-xs text-slate-400" id="activeTimer" data-started="{{ $activeEntry->started_at->timestamp }}">00:00:00</p>
            </div>
            <form method="POST" action="{{ route('bconnect.time_entries.stop', $activeEntry) }}">@csrf
                <button type="submit" class="bc-btn bc-btn-danger text-sm"><i class="fas fa-stop"></i>Stop</button>
            </form>
        </div>
        @endif

        <table class="bc-table">
            <thead>
                <tr><th>Project</th><th>Ticket</th><th>Description</th><th>Duration</th><th>Billable</th><th>Amount</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                <tr>
                    <td class="text-slate-400">{{ $entry->project?->name ?? '—' }}</td>
                    <td class="text-slate-400 text-sm">{{ $entry->ticket?->title ?? '—' }}</td>
                    <td>{{ $entry->description ?? '—' }}</td>
                    <td class="font-mono">{{ $entry->duration_hours }}h</td>
                    <td>{!! $entry->is_billable ? '<span class="bc-badge bc-badge-green">Yes</span>' : '<span class="bc-badge bc-badge-slate">No</span>' !!}</td>
                    <td class="text-slate-400">{{ $entry->billed_amount ? '₹' . number_format($entry->billed_amount, 2) : '—' }}</td>
                    <td class="text-xs text-slate-500">{{ $entry->started_at->format('M d, H:i') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="bc-empty">No time entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $entries->links() }}
    </div>

    <div class="space-y-6">
        <div class="bc-card p-6">
            <h3 class="font-bold mb-4">Start Timer</h3>
            <form method="POST" action="{{ route('bconnect.time_entries.start') }}" class="space-y-4">@csrf
                <select name="project_id" required class="bc-input">
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ optional($project)->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="description" placeholder="What are you working on?" class="bc-input">
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="is_billable" value="1" class="rounded bg-slate-800 border-slate-600 text-cyan-500 focus:ring-cyan-500"> Billable
                </label>
                <input type="number" step="0.01" name="hourly_rate" placeholder="Hourly rate" class="bc-input">
                <button type="submit" class="bc-btn bc-btn-primary w-full" {{ $activeEntry ? 'disabled' : '' }}><i class="fas fa-play"></i>Start</button>
            </form>
        </div>

        <div class="bc-card p-6">
            <h3 class="font-bold mb-4">Manual Entry</h3>
            <form method="POST" action="{{ route('bconnect.time_entries.store') }}" class="space-y-4">@csrf
                <select name="project_id" required class="bc-input">
                    @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ optional($project)->id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="description" placeholder="Description" class="bc-input">
                <div class="grid grid-cols-2 gap-2">
                    <input type="datetime-local" name="started_at" required class="bc-input">
                    <input type="datetime-local" name="ended_at" required class="bc-input">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" name="is_billable" value="1" class="rounded bg-slate-800 border-slate-600 text-cyan-500 focus:ring-cyan-500"> Billable
                </label>
                <input type="number" step="0.01" name="hourly_rate" placeholder="Hourly rate" class="bc-input">
                <button type="submit" class="bc-btn bc-btn-secondary w-full"><i class="fas fa-plus"></i>Add Entry</button>
            </form>
        </div>
    </div>
</div>

@if($activeEntry)
<script>
function updateTimer() {
    const el = document.getElementById('activeTimer');
    if (!el) return;
    const started = parseInt(el.dataset.started) * 1000;
    const diff = Math.floor((Date.now() - started) / 1000);
    const h = String(Math.floor(diff / 3600)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const s = String(diff % 60).padStart(2, '0');
    el.textContent = h + ':' + m + ':' + s;
}
setInterval(updateTimer, 1000);
updateTimer();
</script>
@endif
@endsection
