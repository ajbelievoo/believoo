<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\RemoteSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentApiController extends Controller
{
    // Bump these when new agent builds are published — apps poll /version and
    // prompt the user to update.
    const AGENT_LATEST_WINDOWS = '1.0.7';
    const AGENT_LATEST_ANDROID = '1.0.7';

    protected function findByCode(string $code): ?RemoteSession
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $code));
        return RemoteSession::where('session_code', $code)->first();
    }

    protected function checkToken(Request $r, RemoteSession $session): bool
    {
        $token = $r->bearerToken() ?: $r->input('agent_token');
        return $token && hash_equals((string) $session->agent_token, (string) $token);
    }

    protected function checkViewerToken(Request $r, RemoteSession $session): bool
    {
        $token = $r->bearerToken() ?: $r->input('agent_token');
        return $token && $session->viewer_token && hash_equals((string) $session->viewer_token, (string) $token);
    }

    // POST /api/v1/bmydesk/agent/login — sign in with BMyDesk workspace credentials.
    // Returns a member token the apps store and send on later calls.
    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = \App\Models\User::where('email', $data['email'])->first();
        if (!$user || !\Illuminate\Support\Facades\Hash::check($data['password'], (string) $user->password)) {
            return response()->json(['ok' => false, 'error' => 'Invalid email or password'], 401);
        }
        $member = \App\Models\Bconnect\Member::where('user_id', $user->id)->where('is_active', true)->first();
        if (!$member) {
            return response()->json(['ok' => false, 'error' => 'No BMyDesk workspace membership'], 403);
        }
        if (!$member->api_token) {
            $member->api_token = Str::random(48);
            $member->save();
        }
        return response()->json([
            'ok' => true,
            'name' => $user->name,
            'company' => $member->company?->name,
            'member_token' => $member->api_token,
        ]);
    }

    // POST /api/v1/bmydesk/agent/register — desktop agent calls this to get a session code
    public function register(Request $r)
    {
        $data = $r->validate([
            'host_name' => 'nullable|string|max:120',
            'version' => 'nullable|string|max:30',
            'os' => 'nullable|string|max:40',
            'member_token' => 'nullable|string|max:80',
        ]);

        $member = null;
        if (!empty($data['member_token'])) {
            $member = \App\Models\Bconnect\Member::where('api_token', $data['member_token'])->where('is_active', true)->first();
        }

        do {
            $code = strtoupper(Str::random(3) . rand(100, 999) . Str::random(2));
        } while (RemoteSession::where('session_code', $code)->exists());

        $session = RemoteSession::create([
            'company_id' => $member?->company_id,
            'requested_by' => $member?->id,
            'target_id' => null,
            'host_kind' => 'agent',
            'host_label' => $member?->user?->name ?? $data['host_name'] ?? 'BMyDesk Agent',
            'session_code' => $code,
            'agent_token' => Str::random(48),
            'status' => 'waiting',
            'permission' => 'control',
            'expires_at' => now()->addMinutes(60),
        ]);

        return response()->json([
            'ok' => true,
            'session_code' => $code,
            'agent_token' => $session->agent_token,
            'channel' => 'private-remote-agent.' . $code,
            'expires_at' => $session->expires_at->toIso8601String(),
            'connect_url' => 'https://bmydesk.believoo.com/remote/connect',
            'ice_servers' => \App\Services\TurnCredentialService::iceServers('agent-' . $session->id),
        ]);
    }

    // GET /api/v1/bmydesk/agent/version?platform=windows|android — update check
    public function version(Request $r)
    {
        $platform = strtolower((string) $r->query('platform', 'windows'));
        $latest = $platform === 'android' ? self::AGENT_LATEST_ANDROID : self::AGENT_LATEST_WINDOWS;
        $base = 'https://bmydesk.believoo.com/downloads/';
        $file = $platform === 'android'
            ? 'BMyDesk-Agent-v' . $latest . '.apk'
            : 'BMyDesk-Agent-Setup-' . $latest . '.exe';

        return response()->json([
            'ok' => true,
            'latest' => $latest,
            'url' => $base . $file,
        ]);
    }

    // POST /api/v1/bmydesk/agent/{code}/join — an agent app joining ANOTHER
    // session as viewer (AnyDesk "remote desk" box). Issues a short-lived
    // viewer token; the host still has to accept the join-request.
    public function join(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || $session->status === 'expired' || $session->status === 'ended'
            || ($session->expires_at && $session->expires_at->isPast())) {
            return response()->json(['ok' => false, 'error' => 'invalid or expired code'], 404);
        }

        // One viewer at a time: refuse if a viewer joined within the last 2 min
        if ($session->viewer_token && $session->viewer_joined_at
            && $session->viewer_joined_at->gt(now()->subMinutes(2))) {
            return response()->json(['ok' => false, 'error' => 'session busy — a viewer is already connected'], 409);
        }

        $session->update([
            'viewer_token' => Str::random(48),
            'viewer_joined_at' => now(),
            'status' => $session->status === 'waiting' ? 'connecting' : $session->status,
        ]);

        return response()->json([
            'ok' => true,
            'viewer_token' => $session->viewer_token,
            'channel' => 'private-remote-agent.' . $session->session_code,
            'host_label' => $session->host_label,
            'ice_servers' => \App\Services\TurnCredentialService::iceServers('agentv-' . $session->id),
        ]);
    }

    // GET /api/v1/bmydesk/agent/{code}/status — agent polls waiting/connected state
    public function status(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            // viewers get a limited read — enough for a ws-less accept/reject poll
            if ($session && $this->checkViewerToken($r, $session)) {
                return response()->json(['ok' => true, 'status' => $session->status]);
            }
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        if ($session->expires_at && $session->expires_at->isPast() && $session->status === 'waiting') {
            $session->update(['status' => 'expired']);
        }
        return response()->json([
            'ok' => true,
            'status' => $session->status,
            'viewer' => $session->viewer?->user?->name,
            'viewer_joined_at' => $session->viewer_joined_at?->toIso8601String(),
            'expires_at' => $session->expires_at?->toIso8601String(),
        ]);
    }

    // POST /api/v1/bmydesk/agent/{code}/respond — HTTP fallback for accept/reject
    // when the host ws channel is wedged: server broadcasts the client-event.
    // Also queues it for the viewer's HTTP fallback poll and updates status so
    // plain /status polling reflects the decision.
    public function respond(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        $accept = $r->input('action') === 'accept';
        $payload = ['n' => (string) Str::random(10), 'at' => now()->toIso8601String()];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code],
                $accept ? 'client-join-accept' : 'client-join-reject', $payload);
        $this->queueSignal($session->session_code, 'v', ['kind' => $accept ? 'accept' : 'reject'] + $payload);
        $session->update(['status' => $accept ? 'active' : 'rejected']);
        return response()->json(['ok' => true]);
    }

    // POST /api/v1/bmydesk/agent/{code}/signal — HTTP fallback for WebRTC
    // signaling (offer/answer/ice/end). Broadcasts the client-event for
    // ws-connected peers AND queues it for the other side's HTTP poll, so a
    // fully wedged ws on either end still lets the session complete.
    public function signal(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        $isHost = $this->checkToken($r, $session);
        $isViewer = !$isHost && $this->checkViewerToken($r, $session);
        if (!$isHost && !$isViewer) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        $kind = (string) $r->input('kind', '');
        if (!in_array($kind, ['offer', 'answer', 'ice', 'end'], true)) {
            return response()->json(['ok' => false, 'error' => 'bad kind'], 422);
        }
        // client-supplied nonce keeps the ws broadcast and the queued copy
        // identical so receivers can dedupe double delivery.
        $payload = [
            'n' => (string) ($r->input('n') ?: Str::random(10)),
            'kind' => $kind,
            'sdp' => $r->input('sdp'),
            'candidate' => $r->input('candidate'),
        ];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code],
                $kind === 'end' ? 'client-end' : 'client-signal', $payload);
        $this->queueSignal($session->session_code, $isHost ? 'v' : 'h', $payload);
        if ($kind === 'end') {
            $session->update(['status' => 'ended', 'ended_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }

    // GET /api/v1/bmydesk/agent/{code}/signals — drain queued signals for the
    // caller's role (host token → signals destined for host, viewer → viewer).
    public function signals(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        if ($this->checkToken($r, $session)) {
            $role = 'h';
        } elseif ($this->checkViewerToken($r, $session)) {
            $role = 'v';
        } else {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        return response()->json(['ok' => true, 'signals' => $this->drainSignals($session->session_code, $role)]);
    }

    protected function signalKey(string $code, string $role): string
    {
        return 'rsig.' . $code . '.' . $role;
    }

    protected function queueSignal(string $code, string $toRole, array $payload): void
    {
        $payload['at'] = $payload['at'] ?? now()->toIso8601String();
        $key = $this->signalKey($code, $toRole);
        $list = \Illuminate\Support\Facades\Cache::get($key, []);
        $list[] = $payload;
        if (count($list) > 200) {
            $list = array_slice($list, -200);
        }
        \Illuminate\Support\Facades\Cache::put($key, $list, now()->addMinutes(90));
    }

    protected function drainSignals(string $code, string $role): array
    {
        return \Illuminate\Support\Facades\Cache::pull($this->signalKey($code, $role), []) ?: [];
    }

    // POST /api/v1/bmydesk/agent/{code}/end — agent ends its own session
    public function end(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            return response()->json(['ok' => false], 403);
        }
        $session->update(['status' => 'ended', 'ended_at' => now()]);
        // tell the viewer — ws broadcast plus queued copy for pollers
        $payload = ['n' => (string) Str::random(10), 'kind' => 'end'];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code], 'client-end', $payload);
        $this->queueSignal($session->session_code, 'v', $payload);
        return response()->json(['ok' => true]);
    }

    // POST /api/v1/bmydesk/agent/broadcast-auth — Reverb private-channel auth for the agent.
    // Agents aren't logged-in users, so we sign the socket ourselves instead of Broadcast::auth.
    public function broadcastAuth(Request $r)
    {
        $channel = (string) $r->input('channel_name', '');
        $socketId = (string) $r->input('socket_id', '');
        if (!preg_match('/^private-remote-agent\.([A-Z0-9]+)$/', $channel, $m) || $socketId === '') {
            return response()->json(['ok' => false, 'error' => 'bad channel'], 403);
        }
        $session = $this->findByCode($m[1]);
        if (!$session || !($this->checkToken($r, $session) || $this->checkViewerToken($r, $session))
            || $session->status === 'expired' || $session->status === 'ended') {
            return response()->json(['ok' => false, 'error' => 'invalid token'], 403);
        }

        $conn = config('broadcasting.default');
        $key = config("broadcasting.connections.{$conn}.key");
        $secret = config("broadcasting.connections.{$conn}.secret");
        if (!$key || !$secret) {
            return response()->json(['ok' => false, 'error' => 'broadcast not configured'], 500);
        }

        return response()->json([
            'auth' => $key . ':' . hash_hmac('sha256', $socketId . ':' . $channel, $secret),
        ]);
    }
}
