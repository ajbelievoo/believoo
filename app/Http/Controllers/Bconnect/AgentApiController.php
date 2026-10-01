<?php

namespace App\Http\Controllers\Bconnect;

use App\Http\Controllers\Controller;
use App\Models\Bconnect\RemoteSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentApiController extends Controller
{
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

    // POST /api/v1/bmydesk/agent/register — desktop agent calls this to get a session code
    public function register(Request $r)
    {
        $data = $r->validate([
            'host_name' => 'nullable|string|max:120',
            'version' => 'nullable|string|max:30',
            'os' => 'nullable|string|max:40',
        ]);

        do {
            $code = strtoupper(Str::random(3) . rand(100, 999) . Str::random(2));
        } while (RemoteSession::where('session_code', $code)->exists());

        $session = RemoteSession::create([
            'company_id' => null,
            'requested_by' => null,
            'target_id' => null,
            'host_kind' => 'agent',
            'host_label' => $data['host_name'] ?? 'BMyDesk Agent',
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
        ]);
    }

    // GET /api/v1/bmydesk/agent/{code}/status — agent polls waiting/connected state
    public function status(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            return response()->json(['ok' => false, 'error' => 'invalid'], 403);
        }
        if ($session->expires_at && $session->expires_at->isPast() && $session->status === 'waiting') {
            $session->update(['status' => 'expired']);
        }
        return response()->json([
            'ok' => true,
            'status' => $session->status,
            'viewer' => $session->viewer?->user?->name,
            'expires_at' => $session->expires_at?->toIso8601String(),
        ]);
    }

    // POST /api/v1/bmydesk/agent/{code}/end — agent ends its own session
    public function end(Request $r, string $code)
    {
        $session = $this->findByCode($code);
        if (!$session || !$this->checkToken($r, $session)) {
            return response()->json(['ok' => false], 403);
        }
        $session->update(['status' => 'ended', 'ended_at' => now()]);
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
        if (!$session || !$this->checkToken($r, $session) || $session->status === 'expired' || $session->status === 'ended') {
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
