<?php
namespace App\Http\Controllers\Bconnect;
use App\Http\Controllers\Controller;
use App\Models\Bconnect\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MeetingController extends Controller {
    public function index(Request $r) {
        $meetings = Meeting::where('company_id', $r->input('bconnect_company_id'))
            ->with('creator.user')
            ->latest()
            ->paginate(20);
        return view('bconnect.meetings', compact('meetings'));
    }

    public function store(Request $r) {
        if (!\App\Services\BconnectPlanService::canCreateMeeting($r->input('bconnect_company_id'))) {
            return back()->with('error', 'Meeting limit reached for your plan. Upgrade to Pro/Enterprise.');
        }
        $data = $r->validate(['title' => 'required', 'project_id' => 'nullable|exists:bconnect_projects,id']);
        $room = 'bc-' . uniqid();
        $meeting = Meeting::create([
            'company_id' => $r->input('bconnect_company_id'),
            'project_id' => $data['project_id'] ?? null,
            'created_by' => $r->input('bconnect_member')->id,
            'room_id' => $room,
            'title' => $data['title'],
            'started_at' => now(),
        ]);
        return redirect()->route('bconnect.meeting.room', $room)->with('success', 'Meeting room created');
    }

    public function room(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $companyId = $r->input('bconnect_company_id');
        $canRecord = \App\Services\BconnectPlanService::canUseRecording($companyId);
        $canAi = \App\Services\BconnectPlanService::canUseAi($companyId);
        return view('bconnect.room', compact('room', 'meeting', 'canRecord', 'canAi'));
    }

    public function endMeeting(Request $r, $room) {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();
        $transcript = $r->input('transcript', '');
        $summary = '';
        $actionItems = [];

        $companyId = $r->input('bconnect_company_id');
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $apiKey = $settings['ai_gemini_api_key'] ?? $settings['ai_api_key'] ?? '';

        if ($apiKey && $transcript && \App\Services\BconnectPlanService::canUseAi($companyId)) {
            try {
                $model = $settings['ai_gemini_model'] ?? 'gemini-1.5-flash';
                $resp = Http::timeout(20)->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                    ['contents' => [['parts' => [['text' => "Summarize this meeting transcript into 3 bullet points, and list 3 action items in the format 'Name - Task'.\n\nTranscript:\n{$transcript}"]]]],
                    'generationConfig' => ['maxOutputTokens' => 500]]
                );
                if ($resp->successful()) {
                    $text = trim($resp->json('candidates.0.content.parts.0.text'));
                    $summary = $text;
                    if (preg_match_all('/[-*]\s*(.*?:.*?)\n/i', $text, $m)) {
                        $actionItems = $m[1];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Meeting summary failed: ' . $e->getMessage());
            }
        }

        $meeting->update([
            'ended_at' => now(),
            'ai_summary' => $summary,
            'action_items' => $actionItems,
        ]);

        return response()->json(['ok' => true, 'summary' => $summary, 'action_items' => $actionItems]);
    }
}
