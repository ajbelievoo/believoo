<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\Member;
use App\Models\Bconnect\Notification;
use App\Models\Bconnect\RemoteSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RemoteController extends Controller {
    protected function ensureRemote($companyId) {
        if (!\App\Services\BconnectPlanService::canUseRemote($companyId)) {
            abort(403, 'Remote desktop is available on Pro/Enterprise plans.');
        }
    }

    public function index(Request $r) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        $sessions = RemoteSession::where('company_id', $r->input('bconnect_company_id'))
            ->with('requester.user', 'target.user')
            ->latest()
            ->paginate(20);
        $members = Member::where('company_id', $r->input('bconnect_company_id'))
            ->where('id', '!=', $r->input('bconnect_member')->id)
            ->with('user')
            ->get();
        return view('bconnect.remote', compact('sessions', 'members'));
    }

    public function requestControl(Request $r) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        $data = $r->validate([
            'target_id' => 'required|exists:bconnect_members,id',
            'permission' => 'nullable|in:view,control,clipboard',
        ]);

        $target = Member::where('company_id', $r->input('bconnect_company_id'))->findOrFail($data['target_id']);
        $session = RemoteSession::create([
            'company_id' => $r->input('bconnect_company_id'),
            'requested_by' => $r->input('bconnect_member')->id,
            'target_id' => $target->id,
            'session_code' => strtoupper(Str::random(8)),
            'status' => 'pending',
            'permission' => $data['permission'] ?? 'view',
        ]);

        if ($target->user?->email) {
            try {
                Notification::create([
                    'company_id' => $r->input('bconnect_company_id'),
                    'member_id' => $target->id,
                    'type' => 'remote_request',
                    'title' => 'Remote control request',
                    'message' => $r->input('bconnect_member')->user->name . ' requested ' . $session->permission . ' access. Code: ' . $session->session_code,
                    'url' => route('bconnect.remote'),
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Remote request notification failed: ' . $e->getMessage());
            }
        }

        return redirect()->route('bconnect.remote')->with('success', 'Control request sent. Code: ' . $session->session_code);
    }

    public function respond(Request $r, RemoteSession $session) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        if ($session->company_id != $r->input('bconnect_company_id')) abort(403);
        if ($session->target_id != $r->input('bconnect_member')->id) abort(403);

        $status = $r->validate(['status' => 'required|in:active,rejected,ended'])['status'];

        $session->update(['status' => $status, 'started_at' => $status === 'active' ? now() : $session->started_at]);

        if ($status === 'active') {
            return redirect()->route('bconnect.remote.room', $session->id);
        }
        if ($status === 'ended' && !$session->ended_at) {
            $session->update(['ended_at' => now()]);
        }
        return redirect()->route('bconnect.remote')->with('success', 'Response recorded');
    }

    public function start(Request $r, RemoteSession $session) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        if ($session->company_id != $r->input('bconnect_company_id')) abort(403);
        $session->update(['status' => 'active', 'started_at' => now()]);
        return response()->json(['success' => true, 'session' => $session->session_code]);
    }

    public function end(Request $r, RemoteSession $session) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        if ($session->company_id != $r->input('bconnect_company_id')) abort(403);
        $session->update(['status' => 'ended', 'ended_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function room(Request $r, RemoteSession $session) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        if ($session->company_id != $r->input('bconnect_company_id')) abort(403);
        if ($session->status != 'active') return redirect()->route('bconnect.remote')->with('error', 'Session not active');
        return view('bconnect.remote-room', compact('session'));
    }
}
