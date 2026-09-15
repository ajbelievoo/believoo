<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Meeting;
use App\Models\Bconnect\MeetingNote;
use App\Services\MeetingAiService;
use App\Services\MeetingTranscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MeetingTranscriptController extends Controller
{
    public function saveTranscript(Request $r, $room)
    {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))->where('room_id', $room)->firstOrFail();

        $data = $r->validate([
            'text' => 'required|string|max:10000',
            'speaker' => 'nullable|string|max:255',
            'starts_at' => 'nullable|numeric|min:0',
            'duration' => 'nullable|numeric|min:0',
        ]);

        $meeting->transcripts()->create([
            'speaker' => $data['speaker'] ?? null,
            'text' => $data['text'],
            'starts_at' => $data['starts_at'] ?? 0,
            'duration' => $data['duration'] ?? null,
            'source' => $data['speaker'] ? 'browser' : 'browser',
        ]);

        $meeting->update(['transcript_status' => 'completed']);

        return response()->json(['success' => true]);
    }

    public function uploadAudio(Request $r, $room)
    {
        $companyId = $r->input('bconnect_company_id');
        $meeting = Meeting::where('company_id', $companyId)->where('room_id', $room)->firstOrFail();

        if (!\App\Services\BconnectPlanService::canUseAi($companyId)) {
            return response()->json(['error' => 'AI transcription is available on Enterprise plan.'], 403);
        }

        $data = $r->validate([
            'audio' => 'required|file|mimetypes:audio/wav,audio/webm,audio/mp4,audio/mpeg,audio/ogg,audio/flac|max:51200',
        ]);

        try {
            $result = MeetingTranscriptionService::handleUpload($meeting, $data['audio']);
            return response()->json(['success' => true] + $result);
        } catch (\Exception $e) {
            Log::error('Meeting audio upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Transcription failed.'], 500);
        }
    }

    public function generateNotes(Request $r, $room)
    {
        $companyId = $r->input('bconnect_company_id');
        $meeting = Meeting::where('company_id', $companyId)->where('room_id', $room)->firstOrFail();

        if (!\App\Services\BconnectPlanService::canUseAi($companyId)) {
            return response()->json(['error' => 'AI notes are available on Enterprise plan.'], 403);
        }

        $meeting->update(['notes_status' => 'processing']);

        $note = MeetingNote::firstOrCreate(
            ['meeting_id' => $meeting->id],
            ['summary' => '', 'key_points' => [], 'action_items' => [], 'decisions' => []]
        );

        MeetingAiService::generateNotes($note);

        return response()->json([
            'success' => true,
            'notes' => [
                'summary' => $note->summary,
                'key_points' => $note->key_points,
                'action_items' => $note->action_items,
                'decisions' => $note->decisions,
                'ai_model' => $note->ai_model,
            ],
        ]);
    }

    public function showNotes(Request $r, $room)
    {
        $meeting = Meeting::where('company_id', $r->input('bconnect_company_id'))
            ->where('room_id', $room)
            ->with(['transcripts', 'notes', 'project', 'creator.user'])
            ->firstOrFail();

        return view('bconnect.meeting_notes', compact('meeting'));
    }
}
