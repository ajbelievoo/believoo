@extends('bconnect.layout')
@section('title', 'Recording: ' . $meeting->title)
@section('content')
<div class="bc-card p-6 mb-6">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h3 class="font-bold text-lg">{{ $meeting->title }}</h3>
            <p class="text-slate-400 text-sm">Room <code class="text-slate-500">{{ $meeting->room_id }}</code> &bull; Recording status: <span class="font-bold {{ $meeting->recording_status == 'stopped' ? 'text-green-400' : 'text-amber-400' }}">{{ ucfirst($meeting->recording_status ?: 'none') }}</span></p>
        </div>
        <a href="{{ route('bconnect.meetings') }}" class="bc-btn bc-btn-secondary py-1 px-3 text-sm"><i class="fas fa-arrow-left mr-1"></i>Back</a>
    </div>

    @if($meeting->recording_status === 'started')
        <div class="p-4 bg-amber-500/10 border border-amber-500/30 rounded-xl text-amber-300 text-sm">
            Recording is still in progress. Stop it from the meeting room first.
        </div>
    @elseif(!empty($meeting->recording_file_list) && is_array($meeting->recording_file_list))
        <div class="space-y-4">
            @foreach($meeting->recording_file_list as $file)
            <div class="bg-slate-900 rounded-xl p-4 border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <p class="font-bold truncate">{{ $file['fileName'] ?? 'Recording file' }}</p>
                    <p class="text-xs text-slate-500">{{ $file['trackType'] ?? 'mixed' }} &bull; {{ $file['uid'] ?? '—' }} &bull; {{ $file['mixedAllUser'] ?? '' }}</p>
                </div>
                @if(!empty($file['url']))
                <a href="{{ $file['url'] }}" target="_blank" class="bc-btn bc-btn-primary text-sm py-1.5 px-3 whitespace-nowrap"><i class="fas fa-play mr-1"></i>Play / Download</a>
                @else
                <span class="text-slate-500 text-sm">Storage URL not configured</span>
                @endif
            </div>
            @endforeach
        </div>
    @else
        <div class="p-4 bg-slate-900 rounded-xl text-slate-400 text-sm">No recording files available. Cloud recording files may still be uploading or S3 configuration may be missing.</div>
    @endif

    <div class="mt-6">
        <h4 class="font-bold mb-2">AI Notes</h4>
        @php $note = $meeting->latestNote; @endphp
        @if($note)
            <p class="text-slate-300 text-sm mb-2">{{ $note->summary ?: 'No summary.' }}</p>
            <a href="{{ route('bconnect.meeting.notes', $meeting->room_id) }}" class="bc-btn bc-btn-secondary text-sm py-1 px-3">View Notes</a>
        @else
            <p class="text-slate-500 text-sm">No AI notes generated for this meeting.</p>
        @endif
    </div>
</div>
@endsection
