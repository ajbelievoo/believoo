@extends('bconnect.layout')
@section('title', 'Calendar')
@section('content')
@php
$prev = $month->copy()->subMonth()->format('Y-m');
$next = $month->copy()->addMonth()->format('Y-m');
$current = $month->format('Y-m');
$daysInMonth = $month->daysInMonth;
$firstDay = $month->copy()->startOfMonth()->dayOfWeek;
@endphp
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
    <div class="flex items-center gap-3">
        <h3 class="font-bold text-xl">{{ $month->format('F Y') }}</h3>
        <div class="flex gap-1">
            <a href="{{ route('bconnect.calendar', ['month' => $prev, 'project_id' => $projectId]) }}" class="bc-btn bc-btn-secondary p-2 text-sm"><i class="fas fa-chevron-left"></i></a>
            <a href="{{ route('bconnect.calendar', ['month' => $current, 'project_id' => $projectId]) }}" class="bc-btn bc-btn-secondary p-2 text-sm"><i class="fas fa-dot-circle"></i></a>
            <a href="{{ route('bconnect.calendar', ['month' => $next, 'project_id' => $projectId]) }}" class="bc-btn bc-btn-secondary p-2 text-sm"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
    <form method="GET" action="{{ route('bconnect.calendar') }}" class="flex gap-2">
        <input type="hidden" name="month" value="{{ $current }}">
        <select name="project_id" class="bc-input text-sm py-1.5" onchange="this.form.submit()">
            <option value="">All projects</option>
            @foreach($projects as $id => $name)
            <option value="{{ $id }}" {{ $projectId == $id ? 'selected' : '' }}>{{ $name }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bc-card p-4">
    <div class="grid grid-cols-7 gap-2 mb-2 text-center text-sm font-bold text-slate-400">
        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
    </div>
    <div class="grid grid-cols-7 gap-2">
        @for($i = 0; $i < $firstDay; $i++)
        <div class="min-h-[80px] md:min-h-[120px] bg-slate-900/50 rounded-lg"></div>
        @endfor
        @for($day = 1; $day <= $daysInMonth; $day++)
        @php
        $date = $month->copy()->day($day)->format('Y-m-d');
        $isToday = $date === now()->format('Y-m-d');
        $dayEvents = $events->get($date, collect());
        @endphp
        <div class="min-h-[80px] md:min-h-[120px] bg-slate-900 rounded-lg p-2 border {{ $isToday ? 'border-cyan-500' : 'border-slate-800' }}">
            <div class="text-right text-xs font-bold mb-1 {{ $isToday ? 'text-cyan-400' : 'text-slate-500' }}">{{ $day }}</div>
            <div class="space-y-1">
                @foreach($dayEvents->take(3) as $e)
                <a href="{{ $e['url'] }}" class="block text-[10px] md:text-xs px-1.5 py-0.5 rounded truncate
                    {{ $e['type'] === 'meeting' ? 'bg-cyan-500/20 text-cyan-300' : ($e['type'] === 'sprint' ? 'bg-purple-500/20 text-purple-300' : 'bg-amber-500/20 text-amber-300') }}"
                    title="{{ $e['title'] }}{{ $e['project'] ? ' • '.$e['project'] : '' }}">
                    @if($e['type'] === 'meeting')<i class="fas fa-video mr-1"></i>@elseif($e['type'] === 'sprint')<i class="fas fa-running mr-1"></i>@else<i class="fas fa-ticket-alt mr-1"></i>@endif
                    {{ $e['title'] }}
                </a>
                @endforeach
                @if($dayEvents->count() > 3)
                <div class="text-[10px] text-slate-500 pl-1">+{{ $dayEvents->count() - 3 }} more</div>
                @endif
            </div>
        </div>
        @endfor
    </div>
</div>
@endsection
