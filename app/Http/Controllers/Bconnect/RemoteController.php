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

    // GET /remote/guest/{code?} — public "Remote Desktop Access" page.
    // No login: the guest joins an agent session with a viewer_token from the
    // public agent API, so this works for hosts running the desktop/mobile app.
    public function guestRoom(string $code = null) {
        return view('bconnect.guest-remote', [
            'prefillCode' => $code ? strtoupper(preg_replace('/[^A-Z0-9]/i', '', $code)) : null,
            'reverbKey' => config('broadcasting.connections.reverb.key'),
        ]);
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
        if (in_array($data['permission'] ?? 'view', ['control', 'clipboard'])
            && !\App\Services\BconnectPlanService::canUseRemoteControl($r->input('bconnect_company_id'))) {
            return back()->with('error', 'Mouse & keyboard control requires a Pro/Enterprise plan.');
        }

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

    // ── AnyDesk-style code flow ────────────────────────────────────

    // GET /remote/connect — viewer enters the host's session code
    public function connect(Request $r) {
        return view('bconnect.remote-connect');
    }

    // POST /remote/join — resolve code and open the viewer room
    public function joinByCode(Request $r) {
        $data = $r->validate(['code' => 'required|string|max:20']);
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $data['code']));
        $session = RemoteSession::where('session_code', $code)->first();

        if (!$session) {
            return back()->with('error', 'Invalid code. Check the code shown on the host device.');
        }
        if (in_array($session->status, ['ended', 'rejected', 'expired'])) {
            return back()->with('error', 'This session has ended. Ask the host for a new code.');
        }
        if ($session->expires_at && $session->expires_at->isPast() && in_array($session->status, ['waiting', 'pending'])) {
            $session->update(['status' => 'expired']);
            return back()->with('error', 'This code has expired. Ask the host for a new code.');
        }
        return redirect()->route('bconnect.remote.code', $code);
    }

    // GET /remote/code/{code} — viewer room for code sessions
    public function codeRoom(Request $r, string $code) {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        $session = RemoteSession::where('session_code', $code)->firstOrFail();
        if (in_array($session->status, ['ended', 'rejected', 'expired'])) {
            return redirect()->route('bconnect.remote.connect')->with('error', 'This session has ended.');
        }
        $member = $r->input('bconnect_member');
        if ($session->viewer_member_id === null) {
            $session->update(['viewer_member_id' => $member->id, 'status' => 'connecting', 'viewer_joined_at' => now()]);
        } elseif ($session->viewer_member_id !== $member->id && $session->requested_by !== $member->id) {
            return redirect()->route('bconnect.remote.connect')->with('error', 'This session already has a connected viewer.');
        }
        return view('bconnect.agent-room', [
            'session' => $session,
            'viewerName' => $r->input('bconnect_member')->user->name ?? 'Viewer',
            'viewerId' => $r->input('bconnect_member')->id,
            'canControl' => \App\Services\BconnectPlanService::canUseRemoteControl($r->input('bconnect_company_id')),
            'iceServers' => \App\Services\TurnCredentialService::iceServers('viewer-' . $member->id),
        ]);
    }

    // POST /remote/host/start — browser host: share screen via a code (no agent needed)
    public function hostStart(Request $r) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        do {
            $code = strtoupper(Str::random(3) . rand(100, 999) . Str::random(2));
        } while (RemoteSession::where('session_code', $code)->exists());

        $session = RemoteSession::create([
            'company_id' => $r->input('bconnect_company_id'),
            'requested_by' => $r->input('bconnect_member')->id,
            'host_kind' => 'member',
            'host_label' => $r->input('bconnect_member')->user->name ?? 'Member',
            'session_code' => $code,
            'status' => 'waiting',
            'permission' => 'view',
            'expires_at' => now()->addMinutes(60),
        ]);
        return response()->json(['ok' => true, 'code' => $code, 'url' => route('bconnect.remote.host', $code)]);
    }

    // GET /remote/host/{code} — browser host room (shows code + waits for viewer)
    public function hostRoom(Request $r, string $code) {
        $this->ensureRemote($r->input('bconnect_company_id'));
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        $session = RemoteSession::where('session_code', $code)
            ->where('requested_by', $r->input('bconnect_member')->id)
            ->firstOrFail();
        return view('bconnect.host-room', [
            'session' => $session,
            'iceServers' => \App\Services\TurnCredentialService::iceServers('host-' . $session->id),
        ]);
    }

    // POST /remote/code/{code}/end — either side ends a code session
    public function endByCode(Request $r, string $code) {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        $session = RemoteSession::where('session_code', $code)->firstOrFail();
        $memberId = $r->input('bconnect_member')->id;
        if (!in_array($memberId, [$session->requested_by, $session->viewer_member_id])) abort(403);
        $session->update(['status' => 'ended', 'ended_at' => now()]);
        $payload = ['n' => (string) Str::random(10), 'kind' => 'end'];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code], 'client-end', $payload);
        $this->queueRelay($session->session_code, $memberId == $session->requested_by ? 'v' : 'h', $payload);
        return response()->json(['ok' => true]);
    }

    // ── HTTP signaling relay (mirrors the agent API) ────────────────
    // Browser rooms POST signals here: the server broadcasts to ws peers
    // AND queues for the peer's poll, so a dead ws on either side still
    // completes the session. Nonces dedupe double delivery.

    // POST /remote/code/{code}/signal — offer/answer/ice/end relay
    public function signalByCode(Request $r, string $code) {
        [$session, $role] = $this->codeParticipant($r, $code);
        $kind = (string) $r->input('kind', '');
        if (!in_array($kind, ['offer', 'answer', 'ice', 'end'], true)) abort(422);
        $payload = [
            'n' => (string) ($r->input('n') ?: Str::random(10)),
            'kind' => $kind,
            'sdp' => $r->input('sdp'),
            'candidate' => $r->input('candidate'),
        ];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code],
                $kind === 'end' ? 'client-end' : 'client-signal', $payload);
        $this->queueRelay($session->session_code, $role === 'host' ? 'v' : 'h', $payload);
        if ($kind === 'end') $session->update(['status' => 'ended', 'ended_at' => now()]);
        return response()->json(['ok' => true]);
    }

    // GET /remote/code/{code}/signals — drain queued signals for the caller
    public function signalsByCode(Request $r, string $code) {
        [$session, $role] = $this->codeParticipant($r, $code);
        $key = $this->relayKey($session->session_code, $role === 'host' ? 'h' : 'v');
        return response()->json(['ok' => true, 'signals' => \Illuminate\Support\Facades\Cache::pull($key, []) ?: []]);
    }

    // POST /remote/code/{code}/respond — host accept/reject over HTTP;
    // broadcasts + queues for the viewer's poll + updates status.
    public function respondByCode(Request $r, string $code) {
        [$session, $role] = $this->codeParticipant($r, $code);
        if ($role !== 'host') abort(403);
        $accept = $r->input('action') === 'accept';
        $payload = ['n' => (string) Str::random(10), 'at' => now()->toIso8601String()];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code],
                $accept ? 'client-join-accept' : 'client-join-reject', $payload);
        $this->queueRelay($session->session_code, 'v', ['kind' => $accept ? 'accept' : 'reject'] + $payload);
        $session->update(['status' => $accept ? 'active' : 'rejected']);
        return response()->json(['ok' => true]);
    }

    // GET /remote/code/{code}/session-status — accept/reject + join-request poll
    public function statusByCode(Request $r, string $code) {
        [$session] = $this->codeParticipant($r, $code);
        return response()->json([
            'ok' => true,
            'status' => $session->status,
            'viewer' => $session->viewer?->user?->name,
            'viewer_joined_at' => $session->viewer_joined_at?->toIso8601String(),
        ]);
    }

    /** Resolves the session + caller's role (host/viewer) for code routes. */
    protected function codeParticipant(Request $r, string $code): array {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        $session = RemoteSession::where('session_code', $code)->firstOrFail();
        $memberId = $r->input('bconnect_member')->id;
        if ($memberId == $session->requested_by) return [$session, 'host'];
        if ($memberId == $session->viewer_member_id) return [$session, 'viewer'];
        abort(403);
    }

    protected function relayKey(string $code, string $role): string {
        return 'rsig.' . $code . '.' . $role;
    }

    protected function queueRelay(string $code, string $toRole, array $payload): void {
        $payload['at'] = $payload['at'] ?? now()->toIso8601String();
        $key = $this->relayKey($code, $toRole);
        $list = \Illuminate\Support\Facades\Cache::get($key, []);
        $list[] = $payload;
        if (count($list) > 200) $list = array_slice($list, -200);
        \Illuminate\Support\Facades\Cache::put($key, $list, now()->addMinutes(90));
    }
}
