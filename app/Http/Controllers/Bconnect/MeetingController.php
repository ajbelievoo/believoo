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
        $canRecord = \App\Services\BconnectPlanService::canUseRecording($companyId);
        $canAi = \App\Services\BconnectPlanService::canUseAi($companyId);
        return view('bconnect.room', compact('room', 'meeting', 'canRecord', 'canAi'));
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
