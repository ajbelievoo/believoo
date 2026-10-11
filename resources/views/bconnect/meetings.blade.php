@php
$canMeet = \App\Services\BconnectPlanService::canCreateMeeting(request()->input('bconnect_company_id'));
$meetingLimit = \App\Services\BconnectPlanService::check(request()->input('bconnect_company_id'), 'meetings');
$meetingUsage = \App\Models\Bconnect\Meeting::where('company_id', request()->input('bconnect_company_id'))->count();
@endphp
@extends('bconnect.layout')
@section('title', 'Meetings')
@section('content')
@if(!$canMeet)
<div class="bc-card p-4 mb-6 border-l-4 border-amber-500">
    <div class="flex items-start gap-3">
        <i class="fas fa-exclamation-circle text-amber-400 mt-1"></i>
        <div>
            <p class="font-bold text-amber-400">Meeting limit reached</p>
            <p class="text-sm text-slate-400">You have used {{ $meetingUsage }} of {{ $meetingLimit }} meetings. <a href="{{ route('bconnect.billing.upgrade') }}" class="text-cyan-400 hover:underline">Upgrade to Pro</a> for unlimited meetings.</p>
        </div>
    </div>
</div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bc-card p-6">
        <h3 class="font-bold mb-4">Meetings</h3>
        <table class="bc-table">
            <thead><tr><th>Title</th><th>Scheduled</th><th>Created by</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($meetings as $m)
                <tr>
                    <td class="font-medium">{{ $m->title }}
                        @if($m->password)<i class="fas fa-lock text-[10px] text-slate-500 ml-1" title="Password protected"></i>@endif
                        @if($m->waiting_room)<i class="fas fa-hourglass-half text-[10px] text-amber-400 ml-1" title="Waiting room on"></i>@endif
                        @if($m->recurrence)<i class="fas fa-repeat text-[10px] text-cyan-400 ml-1" title="Repeats {{ $m->recurrence }}"></i>@endif
                    </td>
                    <td class="text-slate-400">{{ $m->scheduled_at?->format('M d, Y H:i') ?? 'Instant' }}</td>
                    <td class="text-slate-400">{{ $m->creator?->user?->name ?? '—' }}</td>
                    <td>
                        <span class="bc-badge {{ $m->ended_at ? 'bc-badge-slate' : ($m->scheduled_at?->isFuture() ? 'bc-badge-cyan' : 'bc-badge-green') }}">{{ $m->ended_at ? 'Ended' : ($m->scheduled_at?->isFuture() ? 'Upcoming' : 'Live') }}</span>
                        @if($m->ended_at && $m->transcript_status === 'completed')<br><span class="text-xs text-cyan-400">Transcript</span>@endif
                    </td>
                    <td class="whitespace-nowrap">
                        <a href="{{ route('bconnect.meeting.room', $m->room_id) }}" target="_blank" class="bc-btn bc-btn-primary py-1 px-2 text-xs">{{ $m->ended_at ? 'View' : 'Join' }}</a>
                        <a href="{{ route('bconnect.meeting.ics', $m->room_id) }}" class="bc-btn bc-btn-secondary py-1 px-2 text-xs ml-1" title="Add to calendar (.ics)"><i class="fas fa-calendar-plus"></i></a>
                        <a href="{{ route('bconnect.meeting.attendance', $m->room_id) }}" class="bc-btn bc-btn-secondary py-1 px-2 text-xs ml-1" title="Attendance"><i class="fas fa-clipboard-user"></i></a>
                        @if($m->ended_at)
                        <a href="{{ route('bconnect.meeting.notes', $m->room_id) }}" class="bc-btn bc-btn-secondary py-1 px-2 text-xs ml-1">Notes</a>
                        @endif
                        @if($m->recording_status)
                        <a href="{{ route('bconnect.meeting.recording', $m->room_id) }}" class="bc-btn bc-btn-secondary py-1 px-2 text-xs ml-1"><i class="fas fa-circle text-pink-400 mr-1"></i>Recording</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="bc-empty">No meetings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $meetings->links() }}
    </div>
    <div class="bc-card p-6">
        <h3 class="font-bold mb-4">Schedule / Start Meeting</h3>
        <form method="POST" action="{{ route('bconnect.meetings.store') }}" class="space-y-4">@csrf
            <input type="text" name="title" placeholder="Meeting title" required class="bc-input" @if(!$canMeet) disabled @endif>
            <select name="project_id" class="bc-input" @if(!$canMeet) disabled @endif><option value="">— No Project —</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select>
            <input type="datetime-local" name="scheduled_at" class="bc-input" @if(!$canMeet) disabled @endif>
            <input type="number" name="duration_minutes" placeholder="Duration (minutes)" min="1" class="bc-input" @if(!$canMeet) disabled @endif>
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="password" placeholder="Password (optional)" maxlength="60" class="bc-input text-sm" @if(!$canMeet) disabled @endif>
                <select name="recurrence" class="bc-input text-sm" @if(!$canMeet) disabled @endif>
                    <option value="">One-time</option>
                    <option value="daily">Repeats daily</option>
                    <option value="weekly">Repeats weekly</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-400"><input type="checkbox" name="waiting_room" value="1" class="accent-cyan-500" @if(!$canMeet) disabled @endif>Waiting room — host admits each participant</label>
            <button type="submit" class="bc-btn bc-btn-primary w-full" @if(!$canMeet) disabled @endif><i class="fas fa-video"></i>Create / Schedule</button>
        </form>
    </div>
</div>
@endsection
