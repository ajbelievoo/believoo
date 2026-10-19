<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\RemoteSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;

class AgentApiController extends Controller
{
    // Bump these when new agent builds are published — apps poll /version and
    // prompt the user to update.
    const AGENT_LATEST_WINDOWS = '1.3.21';
    const AGENT_LATEST_ANDROID = '1.3.33';

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

    // POST /api/v1/bmydesk/agent/register — desktop agent calls this to get a session code.
    // With device_id the code is STABLE (like an AnyDesk ID): the server reuses
    // the device's session row, rotates the token, and resets it to a fresh
    // waiting state — reconnects never need a new code.
    public function register(Request $r)
    {
        $data = $r->validate([
            'host_name' => 'nullable|string|max:120',
            'version' => 'nullable|string|max:30',
            'os' => 'nullable|string|max:40',
            'member_token' => 'nullable|string|max:80',
            'device_id' => 'nullable|string|max:64',
            'fcm_token' => 'nullable|string|max:300',
        ]);

        $member = null;
        if (!empty($data['member_token'])) {
            $member = \App\Models\Bconnect\Member::where('api_token', $data['member_token'])->where('is_active', true)->first();
        }

        // Track fleet metadata for the admin device dashboard
        if (!empty($data['device_id'])) {
            $fields = [
                'platform' => $data['os'] ?? null,
                'version' => $data['version'] ?? null,
                'label' => $member?->user?->name ?? $data['host_name'] ?? null,
                'company_id' => $member?->company_id,
                'last_seen_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ];
            // never wipe a stored push token just because this register call
            // didn't carry one (e.g. the Windows agent has no FCM)
            if (!empty($data['fcm_token'])) $fields['fcm_token'] = $data['fcm_token'];
            \Illuminate\Support\Facades\DB::table('bconnect_agent_devices')->updateOrInsert(
                ['device_id' => $data['device_id']], $fields);
        }

        if (!empty($data['device_id'])) {
            $existing = RemoteSession::where('device_id', $data['device_id'])
                ->where('host_kind', 'agent')->latest()->first();
            if ($existing) {
                $existing->update([
                    'company_id' => $member?->company_id ?? $existing->company_id,
                    'requested_by' => $member?->id ?? $existing->requested_by,
                    'host_label' => $member?->user?->name ?? $data['host_name'] ?? $existing->host_label,
                    'agent_token' => Str::random(48),
                    'viewer_token' => null,
                    'viewer_member_id' => null,
                    'viewer_joined_at' => null,
                    'status' => 'waiting',
                    'started_at' => null,
                    'ended_at' => null,
                    'expires_at' => now()->addHours(48),
                ]);
                // device restart = new epoch — drop queued signals from any
                // previous session so nothing stale replays into the next one
                \Illuminate\Support\Facades\Cache::forget($this->signalKey($existing->session_code, 'v'));
                \Illuminate\Support\Facades\Cache::forget($this->signalKey($existing->session_code, 'h'));
                return response()->json([
                    'ok' => true,
                    'session_code' => $existing->session_code,
                    'agent_token' => $existing->agent_token,
                    'channel' => 'private-remote-agent.' . $existing->session_code,
                    'expires_at' => $existing->expires_at->toIso8601String(),
                    'connect_url' => 'https://bmydesk.believoo.com/remote/connect',
                    'ice_servers' => \App\Services\TurnCredentialService::iceServers('agent-' . $existing->id),
                ]);
            }
        }

        do {
            $code = strtoupper(Str::random(3) . rand(100, 999) . Str::random(2));
        } while (RemoteSession::where('session_code', $code)->exists());

        $session = RemoteSession::create([
            'company_id' => $member?->company_id,
            'requested_by' => $member?->id,
            'target_id' => null,
            'host_kind' => 'agent',
            'device_id' => $data['device_id'] ?? null,
            'host_label' => $member?->user?->name ?? $data['host_name'] ?? 'BMyDesk Agent',
            'session_code' => $code,
            'agent_token' => Str::random(48),
            'status' => 'waiting',
            'permission' => 'control',
            'expires_at' => now()->addHours(48),
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
        // stable /dl/* URLs — proper MIME + never go stale
        $url = 'https://bmydesk.believoo.com/dl/agent.' . ($platform === 'android' ? 'apk' : 'exe');

        return response()->json([
            'ok' => true,
            'latest' => $latest,
            'url' => $url,
        ]);
    }

    // POST /api/v1/bmydesk/agent/{code}/set-pin — set/clear the unattended-
    // access PIN for this device. Stored keyed by device_id so it survives
    // session-code regeneration.
    public function setPin(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        if (!$session->device_id) {
            return response()->json(['ok' => false, 'error' => 'no device id'], 422);
        }
        $pin = trim((string) $r->input('pin', ''));
        if ($pin !== '' && !preg_match('/^[0-9]{4,12}$/', $pin)) {
            return response()->json(['ok' => false, 'error' => 'PIN must be 4-12 digits'], 422);
        }
        // Optional time window — PIN stops working after ttl minutes.
        $ttl = (int) $r->input('ttl_min', 0);
        \Illuminate\Support\Facades\DB::table('bconnect_agent_devices')->updateOrInsert(
            ['device_id' => $session->device_id],
            ['pin_hash' => $pin === '' ? null : \Illuminate\Support\Facades\Hash::make($pin),
             'pin_expires_at' => $pin === '' ? null : ($ttl > 0 ? now()->addMinutes(min($ttl, 10080)) : null),
             'updated_at' => now(), 'created_at' => now()]
        );
        return response()->json(['ok' => true, 'pin_set' => $pin !== '', 'pin_ttl_min' => $ttl > 0 ? min($ttl, 10080) : null]);
    }

    // ── Trusted viewers: host marks "always allow this device" at accept ──
    // POST /{code}/trust {agent_token, viewer_device, viewer_name}
    public function trust(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session) || !$session->device_id) {
            return response()->json(['ok' => false], 403);
        }
        $vd = Str::limit(trim((string) $r->input('viewer_device', '')), 64, '');
        if ($vd === '') return response()->json(['ok' => false, 'error' => 'viewer_device required'], 422);
        \Illuminate\Support\Facades\DB::table('bconnect_agent_trust')->updateOrInsert(
            ['host_device_id' => $session->device_id, 'viewer_device_id' => $vd],
            ['viewer_name' => Str::limit((string) $r->input('viewer_name', ''), 80, '') ?: null,
             'created_at' => now(), 'updated_at' => now()]
        );
        return response()->json(['ok' => true]);
    }

    // POST /{code}/untrust {agent_token, viewer_device?} — revoke one or all
    public function untrust(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session) || !$session->device_id) {
            return response()->json(['ok' => false], 403);
        }
        $q = \Illuminate\Support\Facades\DB::table('bconnect_agent_trust')->where('host_device_id', $session->device_id);
        $vd = trim((string) $r->input('viewer_device', ''));
        if ($vd !== '') $q->where('viewer_device_id', $vd);
        $q->delete();
        return response()->json(['ok' => true]);
    }

    // GET /{code}/trusted — list devices this host auto-accepts
    public function trusted(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session) || !$session->device_id) {
            return response()->json(['ok' => false], 403);
        }
        $rows = \Illuminate\Support\Facades\DB::table('bconnect_agent_trust')
            ->where('host_device_id', $session->device_id)
            ->orderByDesc('updated_at')->limit(100)
            ->get(['viewer_device_id', 'viewer_name', 'created_at']);
        return response()->json(['ok' => true, 'trusted' => $rows]);
    }

    // ── Address book: saved devices (owner = this agent's device_id) ──
    // GET /{code}/devices
    public function savedDevices(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session) || !$session->device_id) {
            return response()->json(['ok' => false], 403);
        }
        $rows = \Illuminate\Support\Facades\DB::table('bconnect_saved_devices')
            ->where('owner_key', 'dev:' . $session->device_id)
            ->orderByDesc('last_used_at')->orderByDesc('id')->limit(50)->get();
        $out = [];
        foreach ($rows as $row) {
            $target = \Illuminate\Support\Facades\DB::table('bconnect_remote_sessions')
                ->where('session_code', $row->code)->first();
            $out[] = [
                'id' => $row->id, 'code' => $row->code, 'label' => $row->label,
                // device counts as online when its host heartbeat is <3 min fresh
                'online' => $target && $target->updated_at
                    && \Illuminate\Support\Carbon::parse($target->updated_at)->gt(now()->subMinutes(3)),
            ];
        }
        return response()->json(['ok' => true, 'devices' => $out]);
    }

    // POST /{code}/devices  {target_code, label}
    public function saveDevice(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session) || !$session->device_id) {
            return response()->json(['ok' => false], 403);
        }
        $data = $r->validate(['target_code' => 'required|string|min:6|max:12', 'label' => 'nullable|string|max:120']);
        \Illuminate\Support\Facades\DB::table('bconnect_saved_devices')->updateOrInsert(
            ['owner_key' => 'dev:' . $session->device_id, 'code' => strtoupper($data['target_code'])],
            ['label' => $data['label'] ?? null, 'last_used_at' => now(), 'updated_at' => now(), 'created_at' => now()]
        );
        return response()->json(['ok' => true]);
    }

    // DELETE /{code}/devices/{id}
    public function forgetDevice(Request $r, string $code, int $id)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session) || !$session->device_id) {
            return response()->json(['ok' => false], 403);
        }
        \Illuminate\Support\Facades\DB::table('bconnect_saved_devices')
            ->where('owner_key', 'dev:' . $session->device_id)->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    // POST /api/v1/bmydesk/agent/{code}/join — an agent app joining ANOTHER
    // session as viewer (AnyDesk "remote desk" box). Issues a short-lived
    // viewer token; the host still has to accept the join-request.
    public function join(Request $r, string $code)
    {
        // Brute-force guard: 60 join attempts / min / IP. The app legitimately
        // retries join during reconnect loops — 8-char codes can't be brute
        // forced at this rate regardless (~2.8e12 space). PIN grinding has its
        // own tighter limiter below.
        $joinKey = 'bmd-join:' . $r->ip();
        if (RateLimiter::tooManyAttempts($joinKey, 60)) {
            return response()->json(['ok' => false, 'error' => 'too many attempts — wait a minute'], 429);
        }
        RateLimiter::hit($joinKey, 60);

        // Per-device guard: a leaked code shouldn't mean a swarm of join prompts.
        $codeKey = 'bmd-joincode:' . strtoupper($code);
        if (RateLimiter::tooManyAttempts($codeKey, 20)) {
            return response()->json(['ok' => false, 'error' => 'this device is receiving too many requests — wait a minute'], 429);
        }
        RateLimiter::hit($codeKey, 60);

        $session = $this->findByCode($code);
        if (!$session) {
            return response()->json(['ok' => false, 'error' => 'invalid or expired code'], 404);
        }

        // Mobile hosts can be woken from a dead state with an FCM push — a
        // stale heartbeat only hard-fails when the device has no push token.
        $hostFcm = null;
        if ($session->host_kind === 'agent' && $session->device_id) {
            $hostFcm = \Illuminate\Support\Facades\DB::table('bconnect_agent_devices')
                ->where('device_id', $session->device_id)->value('fcm_token');
        }
        $hostStale = !$session->updated_at || $session->updated_at->lt(now()->subMinutes(3));
        $hostWaking = $session->host_kind === 'agent' && $hostStale && !empty($hostFcm);

        $dead = in_array($session->status, ['ended', 'rejected', 'expired'])
            || ($session->expires_at && $session->expires_at->isPast());
        if ($dead) {
            // Agent codes are permanent (AnyDesk-style): a fresh join revives
            // the session — but only if the host device is actually live
            // (its poll loop heartbeats via updated_at).
            if ($session->host_kind !== 'agent') {
                return response()->json(['ok' => false, 'error' => 'session ended — ask the host for a new code'], 404);
            }
            if ($hostStale && !$hostWaking) {
                return response()->json(['ok' => false, 'error' => 'host offline — ask them to open the BMyDesk Agent'], 404);
            }
            // clear the previous viewer so the busy-check below passes and a
            // fresh viewer_token is minted for this join
            $session->update(['status' => 'connecting', 'ended_at' => null,
                'viewer_token' => null, 'viewer_joined_at' => null, 'viewer_name' => null, 'viewer_note' => null]);
        }

        // A live agent host heartbeats via /status every few seconds — a stale
        // heartbeat on a 'waiting' session means the app was closed/crashed.
        // Refuse immediately instead of letting the viewer wait forever —
        // unless the device is FCM-wakeable (Android), in which case we push
        // and give it a short window to come online.
        if ($session->host_kind === 'agent' && $hostStale && !$hostWaking) {
            return response()->json(['ok' => false, 'error' => 'host offline — ask them to open the BMyDesk Agent'], 404);
        }
        if ($hostWaking) {
            \Illuminate\Support\Facades\Cache::put('bmd-wake:' . $session->session_code, true, now()->addMinutes(3));
            \App\Services\BconnectFcmService::send($hostFcm, [
                'kind' => 'join-request',
                'name' => (string) $r->input('name', 'Someone'),
                'code' => $session->session_code,
            ]);
        }

        // Unattended access: a correct PIN skips host approval entirely.
        $autoAccept = false;
        $pin = (string) $r->input('pin', '');
        $viewerDevice = Str::limit(trim((string) $r->input('viewer_device', '')), 64, '') ?: null;
        if ($pin !== '' && $session->device_id) {
            $dev = \Illuminate\Support\Facades\DB::table('bconnect_agent_devices')
                ->where('device_id', $session->device_id)->first(['pin_hash', 'pin_expires_at']);
            // an expired PIN behaves like no PIN — normal approval flow
            $pinHash = ($dev && (!$dev->pin_expires_at || \Carbon\Carbon::parse($dev->pin_expires_at)->isFuture()))
                ? $dev->pin_hash : null;
            $pinKey = 'bmd-pin:' . $session->session_code . ':' . $r->ip();
            if (RateLimiter::tooManyAttempts($pinKey, 5)) {
                return response()->json(['ok' => false, 'error' => 'too many wrong PINs — wait 10 minutes'], 429);
            }
            if ($pinHash && \Illuminate\Support\Facades\Hash::check($pin, $pinHash)) {
                RateLimiter::clear($pinKey);
                $autoAccept = true;
            } elseif ($pinHash) {
                RateLimiter::hit($pinKey, 600);
                return response()->json(['ok' => false, 'error' => 'wrong PIN'], 403);
            }
            // device has no PIN set → fall through to normal approval flow
        }

        // Trusted device: the host marked this viewer "always allow" — skip
        // the prompt (still fully logged in the session audit trail).
        if (!$autoAccept && $viewerDevice && $session->device_id) {
            $autoAccept = \Illuminate\Support\Facades\DB::table('bconnect_agent_trust')
                ->where('host_device_id', $session->device_id)
                ->where('viewer_device_id', $viewerDevice)->exists();
        }

        // One viewer at a time: refuse if a viewer joined within the last 2 min
        if ($session->viewer_token && $session->viewer_joined_at
            && $session->viewer_joined_at->gt(now()->subMinutes(2))) {
            return response()->json(['ok' => false, 'error' => 'session busy — a viewer is already connected'], 409);
        }

        \Illuminate\Support\Facades\Log::info("bmysig {$code} join name=" . (string)$r->input('name','') . " auto=" . (int)!!$autoAccept);
        // New viewer epoch — flush the HTTP signal queues both ways. Leftover
        // 'end'/'reject'/'ice' from a previous cycle would instantly kill the
        // fresh session (join→end ping-pong observed in production logs).
        \Illuminate\Support\Facades\Cache::forget($this->signalKey($session->session_code, 'v'));
        \Illuminate\Support\Facades\Cache::forget($this->signalKey($session->session_code, 'h'));
        $session->update([
            'viewer_token' => Str::random(48),
            'viewer_joined_at' => now(),
            'viewer_name' => Str::limit((string) $r->input('name', ''), 60, '') ?: null,
            'viewer_note' => Str::limit((string) $r->input('note', ''), 140, '') ?: null,
            'viewer_ip' => $r->ip(),
            'viewer_device' => $viewerDevice,
            'status' => $session->status === 'waiting' ? 'connecting' : $session->status,
        ]);

        // Relay-mode viewers can't whisper client-join-request over ws (their
        // socket is dead by definition), so the host's HTTP drain carries the
        // request too. Hosts dedupe against viewer_joined_at/ws delivery.
        if (!$autoAccept) {
            $this->queueSignal($session->session_code, 'h', [
                'kind' => 'join-request',
                'name' => $session->viewer_name ?: 'Someone',
                'note' => $session->viewer_note,
                'vdevice' => $viewerDevice,
            ]);
        }

        if ($autoAccept) {
            $session->update(['status' => 'active', 'started_at' => $session->started_at ?? now()]);
            $payload = ['n' => (string) Str::random(10), 'at' => now()->toIso8601String(), 'unattended' => true];
            \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
                ->broadcast(['private-remote-agent.' . $session->session_code], 'client-join-accept', $payload);
            $this->queueSignal($session->session_code, 'v', ['kind' => 'accept'] + $payload);
        }

        return response()->json([
            'ok' => true,
            'auto_accepted' => $autoAccept,
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
            // viewers get a limited read — enough for a ws-less accept/reject poll.
            // host_alive lets a waiting viewer detect the host app closing
            // mid-approval instead of polling a dead session forever.
            if ($session && $this->checkViewerToken($r, $session)) {
                $fresh = $session->updated_at && $session->updated_at->gt(now()->subMinutes(3));
                // join() set this when it pushed FCM to a dead Android host —
                // the viewer should keep waiting through the wake window
                // instead of instantly reporting "host went offline".
                $waking = !$fresh && \Illuminate\Support\Facades\Cache::has('bmd-wake:' . $session->session_code);
                return response()->json(['ok' => true, 'status' => $session->status,
                    'host_alive' => $session->host_kind !== 'agent' || $fresh,
                    'host_waking' => $waking]);
            }
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        // host heartbeat — touches updated_at so join() knows the device is
        // alive, and rolls the expiry forward while the agent runs.
        $session->touch();
        if ($session->expires_at && $session->expires_at->lt(now()->addHours(24))) {
            $session->update(['expires_at' => now()->addHours(48)]);
        }
        if ($session->expires_at && $session->expires_at->isPast() && $session->status === 'waiting') {
            $session->update(['status' => 'expired']);
        }
        return response()->json([
            'ok' => true,
            'status' => $session->status,
            'viewer' => $session->viewer?->user?->name ?: $session->viewer_name,
            'viewer_note' => $session->viewer_note,
            'viewer_device' => $session->viewer_device,
            'pin_set' => $session->device_id
                ? (bool) \Illuminate\Support\Facades\DB::table('bconnect_agent_devices')->where('device_id', $session->device_id)->value('pin_hash')
                : false,
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
        // already decided — a late/stray respond must not flip a live or dead
        // session (double-click accept→reject race killed real sessions)
        if (in_array($session->status, ['active', 'ended', 'rejected', 'expired'], true)) {
            return response()->json(['ok' => true, 'already' => $session->status]);
        }
        $accept = $r->input('action') === 'accept';
        $payload = ['n' => (string) Str::random(10), 'at' => now()->toIso8601String()];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code],
                $accept ? 'client-join-accept' : 'client-join-reject', $payload);
        $this->queueSignal($session->session_code, 'v', ['kind' => $accept ? 'accept' : 'reject'] + $payload);
        $session->update(['status' => $accept ? 'active' : 'rejected']
            + ($accept ? ['started_at' => $session->started_at ?? now()] : []));
        \Illuminate\Support\Facades\Log::info("bmysig {$code} respond " . ($accept ? 'accept' : 'reject'));
        return response()->json(['ok' => true]);
    }

    // POST /api/v1/bmydesk/agent/{code}/signal — HTTP fallback for WebRTC
    // signaling (offer/answer/ice/end). Broadcasts the client-event for
    // ws-connected peers AND queues it for the other side's HTTP poll, so a
    // fully wedged ws on either end still lets the session complete.
    public function signal(Request $r, string $code)
    {
        // log every attempt first — auth/kind failures must still be visible;
        // ice goes to debug (hundreds per session)
        $sigIn = "bmysig {$code} SIGIN kind=" . $r->input('kind', '?')
            . " sdp_len=" . strlen((string) $r->input('sdp', ''))
            . " tok=" . ($r->bearerToken() || $r->input('agent_token') ? 'y' : 'n');
        if ($r->input('kind') === 'ice') {
            \Illuminate\Support\Facades\Log::debug($sigIn);
        } else {
            \Illuminate\Support\Facades\Log::info($sigIn);
        }
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
        // chunked-offer path: small parts cached until complete, then relayed
        // as a normal offer — receivers never see the split.
        if (in_array($kind, ['offer-part', 'answer-part'], true)) {
            $i = (int) $r->input('i', 0); $total = (int) $r->input('total', 0);
            if ($total < 1 || $total > 200 || $i < 0 || $i >= $total) abort(422);
            $ck = 'rsig.part.' . $session->session_code . '.' . ($isHost ? 'h' : 'v') . '.' . $kind;
            $buf = \Illuminate\Support\Facades\Cache::get($ck, []);
            $buf[$i] = (string) $r->input('chunk', '');
            if (count($buf) < $total) {
                \Illuminate\Support\Facades\Cache::put($ck, $buf, now()->addMinutes(5));
                return response()->json(['ok' => true, 'partial' => count($buf)]);
            }
            \Illuminate\Support\Facades\Cache::forget($ck);
            ksort($buf);
            $r->merge(['kind' => substr($kind, 0, -5), 'sdp' => implode('', $buf)]);
            $kind = substr($kind, 0, -5);
        }
        if (!in_array($kind, ['offer', 'answer', 'ice', 'end'], true)) {
            return response()->json(['ok' => false, 'error' => 'bad kind'], 422);
        }
        // client-supplied nonce keeps the ws broadcast and the queued copy
        // identical so receivers can dedupe double delivery.
        $payload = [
            'n' => (string) ($r->input('n') ?: Str::random(10)),
            'kind' => $kind,
            'sdp' => is_string($r->input('sdp')) ? $this->sanitizeSdp($r->input('sdp')) : $r->input('sdp'),
            'candidate' => $r->input('candidate'),
        ];
        \Illuminate\Support\Facades\Broadcast::connection(config('broadcasting.default'))
            ->broadcast(['private-remote-agent.' . $session->session_code],
                $kind === 'end' ? 'client-end' : 'client-signal', $payload);
        $this->queueSignal($session->session_code, $isHost ? 'v' : 'h', $payload);
        // ICE candidates arrive in the hundreds per session — log them at
        // debug so a busy day doesn't bury the real events.
        $logLine = "bmysig {$code} {$kind} " . ($isHost ? 'h→v' : 'v→h')
            . (isset($payload['sdp']) ? ' sdp=' . strlen($payload['sdp']) . 'b' : '')
            . ($payload['candidate'] ? ' ice' : '');
        if ($kind === 'ice') {
            \Illuminate\Support\Facades\Log::debug($logLine);
        } else {
            \Illuminate\Support\Facades\Log::info($logLine);
        }
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
            $session->touch(); // host heartbeat — join() refuses dead hosts
            if ($session->expires_at && $session->expires_at->lt(now()->addHours(24))) {
                $session->update(['expires_at' => now()->addHours(48)]);
            }
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
        // Sanitize SDP in transit — some libwebrtc builds reject exotic
        // attribute lines (a=max-message-size outside m=application, …).
        // Cleaning here protects OLD agent builds that can't strip on receive.
        if (isset($payload['sdp']) && is_string($payload['sdp'])) {
            $payload['sdp'] = $this->sanitizeSdp($payload['sdp']);
        }
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

    private function sanitizeSdp(string $sdp): string
    {
        $lines = preg_split('/\r?\n/', $sdp);
        $out = [];
        foreach ($lines as $l) {
            if ($l === '') continue;
            if (strpos($l, 'a=max-message-size') === 0) continue;
            // RFC 8841 "a=sctp-port" breaks older libwebrtc builds — rewrite to
            // the legacy "a=sctpmap" form every stack understands.
            if (preg_match('/^a=sctp-port:(\d+)\s*$/', $l, $m)) { $l = 'a=sctpmap:' . $m[1] . ' webrtc-datachannel 1024'; }
            $out[] = $l;
        }
        // SDP requires EVERY line (incl. the last) to end in CRLF — stripping a
        // trailing attribute leaves the final line unterminated → "Invalid SDP
        // line" on strict parsers (this was the real connect-killer).
        return implode("\r\n", $out) . "\r\n";
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

    // POST /{code}/rate — viewer-side CSAT after a session; only the actual
    // viewer token may rate their own session (prevents host self-rating).
    public function guestRate(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkViewerToken($r, $session)) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        $data = $r->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
        ]);
        $session->update([
            'guest_rating' => $data['rating'],
            'guest_feedback' => $data['feedback'] ?? null,
        ]);
        return response()->json(['ok' => true]);
    }

    // POST /{code}/app-rate — "how was the app" rating when an agent quits.
    // Agent-token auth (host devices rate the app itself); viewer CSAT stays
    // on /rate which is session-scoped.
    public function appRate(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        $data = $r->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:500',
            'platform' => 'nullable|string|max:20',
            'version' => 'nullable|string|max:20',
        ]);
        \Illuminate\Support\Facades\DB::table('bmydesk_app_ratings')->insert([
            'device_id' => $session->device_id,
            'session_code' => $session->session_code,
            'platform' => $data['platform'] ?? null,
            'version' => $data['version'] ?? null,
            'rating' => $data['rating'],
            'feedback' => $data['feedback'] ?? null,
            'ip' => $r->ip(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
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
