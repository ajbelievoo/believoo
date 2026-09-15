@php
$canAi = \App\Services\BconnectPlanService::canUseAi(request()->input('bconnect_company_id'));
$note = $meeting->latestNote;
@endphp
@extends('bconnect.layout')
@section('title', 'Meeting Notes: ' . $meeting->title)
@section('content')
<div class="bc-card p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-bold text-lg">{{ $meeting->title }}</h3>
            <p class="text-slate-400 text-sm">Room: <code class="text-slate-500">{{ $meeting->room_id }}</code></p>
        </div>
        <a href="{{ route('bconnect.meetings') }}" class="bc-btn bc-btn-secondary py-1 px-3 text-sm"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div>
            <h4 class="font-bold mb-3"><i class="fas fa-closed-captioning mr-1 text-cyan-400"></i>Transcript</h4>
            @if($meeting->transcripts->isEmpty())
                <p class="text-slate-500 text-sm italic">No transcript available yet.</p>
            @else
                <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                    @foreach($meeting->transcripts as $t)
                    <div class="p-3 bg-slate-900 rounded-lg border border-slate-800">
                        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                            <span class="font-semibold text-slate-300">{{ $t->speaker ?: 'Speaker' }}</span>
                            <span>{{ gmdate('H:i:s', (int) $t->starts_at) }}</span>
                        </div>
                        <p class="text-sm text-slate-300">{{ $t->text }}</p>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h4 class="font-bold mb-3"><i class="fas fa-robot mr-1 text-cyan-400"></i>AI Notes</h4>
            @if(!$canAi)
                <p class="text-slate-500 text-sm italic">AI notes are available on the Enterprise plan.</p>
            @elseif(!$note)
                <p class="text-slate-500 text-sm italic">No AI notes generated yet. End the meeting to generate notes.</p>
            @else
                <div class="space-y-4">
                    <div class="p-3 bg-slate-900 rounded-lg border border-slate-800">
                        <h5 class="text-sm font-bold text-slate-300 mb-1">Summary</h5>
                        <p class="text-sm text-slate-400">{{ $note->summary ?: '—' }}</p>
                    </div>

                    @if(!empty($note->key_points))
                    <div class="p-3 bg-slate-900 rounded-lg border border-slate-800">
                        <h5 class="text-sm font-bold text-slate-300 mb-1">Key Points</h5>
                        <ul class="list-disc list-inside text-sm text-slate-400 space-y-1">
                            @foreach($note->key_points as $point)
                            <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if(!empty($note->action_items))
                    <div class="p-3 bg-slate-900 rounded-lg border border-slate-800">
                        <h5 class="text-sm font-bold text-slate-300 mb-1">Action Items</h5>
                        <ul class="list-disc list-inside text-sm text-slate-400 space-y-1">
                            @foreach($note->action_items as $item)
                            <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if(!empty($note->decisions))
                    <div class="p-3 bg-slate-900 rounded-lg border border-slate-800">
                        <h5 class="text-sm font-bold text-slate-300 mb-1">Decisions</h5>
                        <ul class="list-disc list-inside text-sm text-slate-400 space-y-1">
                            @foreach($note->decisions as $decision)
                            <li>{{ $decision }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if($note->ai_model)
                    <p class="text-xs text-slate-500">Generated with {{ $note->ai_model }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
