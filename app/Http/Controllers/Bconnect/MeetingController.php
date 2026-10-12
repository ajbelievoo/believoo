<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\MeetingNote;
use App\Services\MeetingAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MeetingController extends Controller {
    public function index(Request $r) {
        $meetings = Meeting::where('company_id', $r->input('bconnect_company_id'))
            ->with('creator.user')
            ->orderByRaw('ISNULL(scheduled_at)')
            ->orderBy('scheduled_at', 'asc')
            ->latest('started_at')
            ->paginate(20);
        $projects = \App\Models\Bconnect\Project::where('company_id', $r->input('bconnect_company_id'))->get();
        return view('bconnect.meetings', compact('meetings', 'projects'));
    }

    public function store(Request $r) {
        if (!\App\Services\BconnectPlanService::canCreateMeeting($r->input('bconnect_company_id'))) {
            return back()->with('error', 'Meeting limit reached for your plan. Upgrade to Pro/Enterprise.');
        }
        $data = $r->validate([
            'title' => 'required',
            'project_id' => ['nullable', Rule::exists('bconnect_projects', 'id')->where('company_id', $r->input('bconnect_company_id'))],
            'scheduled_at' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'password' => 'nullable|string|max:60',
            'waiting_room' => 'nullable|boolean',
            'recurrence' => 'nullable|in:daily,weekly',
        ]);
        $room = 'bc-' . uniqid();
        $meeting = Meeting::create([
            'company_id' => $r->input('bconnect_company_id'),
            'project_id' => $data['project_id'] ?? null,
            'created_by' => $r->input('bconnect_member')->id,
            'room_id' => $room,
            'title' => $data['title'],
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'started_at' => $data['scheduled_at'] ? null : now(),
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'password' => $data['password'] ? \Illuminate\Support\Facades\Hash::make($data['password']) : null,
            'waiting_room' => !empty($data['waiting_room']),
            'recurrence' => $data['recurrence'] ?? null,
        ]);

        // Notify project members about scheduled meeting
        if ($meeting->scheduled_at && $meeting->scheduled_at->isFuture() && $meeting->project_id) {
            $projectMembers = \App\Models\Bconnect\Member::where('company_id', $meeting->company_id)
                ->where('is_active', true)
                ->get();
            foreach ($projectMembers as $m) {
                \App\Services\BconnectNotificationService::send($m, 'meeting', 'Meeting scheduled', $meeting->title . ' at ' . $meeting->scheduled_at->format('M d, Y H:i'), route('bconnect.meeting.room', $room), $meeting->company_id);
            }
        }

        return redirect()->route('bconnect.meeting.room', $room)->with('success', 'Meeting room created');
    }

    public function room(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $companyId = $r->input('bconnect_company_id');
        $member = $r->input('bconnect_member');
        $isHost = $meeting->created_by === $member->id;

        // Password gate
        if ($meeting->password && !$isHost && session("meeting_pw_ok_{$meeting->id}") !== true) {
            return view('bconnect.meeting-gate', ['meeting' => $meeting, 'room' => $room, 'kind' => 'password']);
        }

        // Waiting room — non-host lands in lobby until admitted
        if ($meeting->waiting_room && !$isHost && $meeting->ended_at === null) {
            $row = \DB::table('bconnect_meeting_participants')->where('meeting_id', $meeting->id)->where('member_id', $member->id)->first();
            if (!$row) {
                \DB::table('bconnect_meeting_participants')->insert([
                    'meeting_id' => $meeting->id, 'member_id' => $member->id,
                    'name' => $member->user->name ?? 'Guest', 'status' => 'waiting',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $row = \DB::table('bconnect_meeting_participants')->where('meeting_id', $meeting->id)->where('member_id', $member->id)->first();
            }
            if ($row->status === 'waiting') {
                return view('bconnect.meeting-gate', ['meeting' => $meeting, 'room' => $room, 'kind' => 'waiting']);
            }
            if ($row->status === 'denied') {
                return view('bconnect.meeting-gate', ['meeting' => $meeting, 'room' => $room, 'kind' => 'denied']);
            }
        }

        // Attendance — joined
        if ($meeting->ended_at === null) {
            \DB::table('bconnect_meeting_participants')->updateOrInsert(
                ['meeting_id' => $meeting->id, 'member_id' => $member->id],
                ['name' => $member->user->name ?? 'Guest', 'status' => 'joined', 'joined_at' => now(), 'left_at' => null, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $canRecord = \App\Services\BconnectPlanService::canUseRecording($companyId);
        $canAi = \App\Services\BconnectPlanService::canUseAi($companyId);
        return view('bconnect.room', compact('room', 'meeting', 'canRecord', 'canAi', 'isHost', 'member'));
    }

    public function checkPassword(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $ok = $meeting->password && (
            \Illuminate\Support\Facades\Hash::check((string) $r->password, $meeting->password)
            || hash_equals($meeting->password, (string) $r->password) // legacy plaintext rows
        );
        if ($ok) {
            if (hash_equals($meeting->password, (string) $r->password)) {
                $meeting->update(['password' => \Illuminate\Support\Facades\Hash::make($r->password)]);
            }
            session(["meeting_pw_ok_{$meeting->id}" => true]);
            return response()->json(['ok' => true]);
        }
        return response()->json(['ok' => false, 'error' => 'Wrong password'], 403);
    }

    public function waitStatus(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $member = $r->input('bconnect_member');
        $row = \DB::table('bconnect_meeting_participants')->where('meeting_id', $meeting->id)->where('member_id', $member->id)->first();
        return response()->json(['status' => $row?->status ?? 'waiting']);
    }

    public function participants(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $list = \DB::table('bconnect_meeting_participants')->where('meeting_id', $meeting->id)->orderBy('updated_at', 'desc')->get();
        return response()->json(['participants' => $list]);
    }

    public function admit(Request $r, $room, $pid) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        if ($meeting->created_by !== $r->input('bconnect_member')->id) abort(403);
        \DB::table('bconnect_meeting_participants')->where('id', $pid)->where('meeting_id', $meeting->id)->update(['status' => 'joined', 'joined_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function deny(Request $r, $room, $pid) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        if ($meeting->created_by !== $r->input('bconnect_member')->id) abort(403);
        \DB::table('bconnect_meeting_participants')->where('id', $pid)->where('meeting_id', $meeting->id)->update(['status' => 'denied', 'left_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function leave(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $member = $r->input('bconnect_member');
        \DB::table('bconnect_meeting_participants')->where('meeting_id', $meeting->id)->where('member_id', $member->id)->update(['status' => 'left', 'left_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function attendance(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $rows = \DB::table('bconnect_meeting_participants')->where('meeting_id', $meeting->id)->orderBy('joined_at')->get();
        return view('bconnect.meeting-attendance', compact('meeting', 'rows', 'room'));
    }

    public function ics(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $start = $meeting->scheduled_at ?? $meeting->created_at;
        $end = $start->copy()->addMinutes($meeting->duration_minutes ?? 60);
        $fmt = fn($d) => $d->utc()->format('Ymd\THis\Z');
        $url = url(route('bconnect.meeting.room', $room, false));
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//BMyDesk//EN',
            'BEGIN:VEVENT',
            'UID:' . $meeting->room_id . '@bmydesk.believoo.com',
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($start),
            'DTEND:' . $fmt($end),
            'SUMMARY:' . str_replace([',',';'], ' ', $meeting->title),
            'DESCRIPTION:Join: https://bmydesk.believoo.com' . $url,
            'URL:https://bmydesk.believoo.com' . $url,
            ($meeting->recurrence === 'daily' ? 'RRULE:FREQ=DAILY' : ''),
            ($meeting->recurrence === 'weekly' ? 'RRULE:FREQ=WEEKLY' : ''),
            'END:VEVENT', 'END:VCALENDAR',
        ]);
        return response($ics, 200, ['Content-Type' => 'text/calendar', 'Content-Disposition' => 'attachment; filename="meeting-' . $room . '.ics"']);
    }

    // In-meeting file share — upload once, broadcast link to everyone.
    public function shareFile(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $pol = \App\Models\Bconnect\Company::find($meeting->company_id)?->remote_policies;
        if ($pol && empty($pol['file_transfer'])) {
            return response()->json(['ok' => false, 'error' => 'File transfer disabled by company policy'], 403);
        }
        $r->validate(['file' => 'required|file|max:51200']);
        $path = $r->file('file')->storePublicly('bconnect/meetings/' . $meeting->id, 'public');
        return response()->json(['ok' => true, 'url' => \Storage::url($path), 'name' => $r->file('file')->getClientOriginalName()]);
    }

    public function recording(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        return view('bconnect.meeting_recording', compact('meeting'));
    }

    public function endMeeting(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $transcript = $r->input('transcript', '');

        $companyId = $r->input('bconnect_company_id');

        // Store the final browser transcript as one segment
        if ($transcript) {
            $meeting->transcripts()->create([
                'speaker' => $r->input('speaker') ?: null,
                'text' => $transcript,
                'starts_at' => 0,
                'source' => 'browser',
            ]);
            $meeting->update(['transcript_status' => 'completed']);
        }

        // Generate structured AI notes on Enterprise plans
        $note = null;
        if ($transcript && \App\Services\BconnectPlanService::canUseAi($companyId)) {
            $note = MeetingNote::firstOrCreate(
                ['meeting_id' => $meeting->id],
                ['summary' => '', 'key_points' => [], 'action_items' => [], 'decisions' => []]
            );
            $meeting->update(['notes_status' => 'processing']);
            MeetingAiService::generateNotes($note);
        }

        $meeting->update([
            'ended_at' => now(),
            'ai_summary' => $note?->summary ?? $meeting->ai_summary,
            'action_items' => $note?->action_items ?? $meeting->action_items,
        ]);

        return response()->json([
            'ok' => true,
            'summary' => $note?->summary ?? $meeting->ai_summary ?? '',
            'action_items' => $note?->action_items ?? $meeting->action_items ?? [],
            'key_points' => $note?->key_points ?? [],
            'decisions' => $note?->decisions ?? [],
        ]);
    }
}
