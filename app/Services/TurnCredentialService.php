<?php

namespace App\Services;

/**
 * Generates time-limited TURN credentials (coturn use-auth-secret mode).
 * username = "<unix-expiry>:<label>", credential = base64(hmac-sha1(username, secret)).
 * Creds self-expire — safe to embed in pages/API responses.
 */
class TurnCredentialService
{
    public static function iceServers(string $userLabel = 'bmydesk', int $ttlSeconds = 86400): array
    {
        $servers = [
            ['urls' => ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302']],
        ];

        $host = config('services.turn.host');
        $tlsHost = config('services.turn.tls_host');
        $secret = config('services.turn.secret');
        if ($host && $secret) {
            $username = (time() + $ttlSeconds) . ':' . $userLabel;
            $urls = [
                "turn:{$host}:3478?transport=udp",
                "turn:{$host}:3478?transport=tcp",
            ];
            // TURNS over TLS on 5349 — the fallback for networks that block
            // UDP entirely (corporate firewalls, captive-portal-ish NATs).
            if ($tlsHost) {
                $urls[] = "turns:{$tlsHost}:5349?transport=tcp";
            }
            $servers[] = [
                'urls' => $urls,
                'username' => $username,
                'credential' => base64_encode(hash_hmac('sha1', $username, $secret, true)),
            ];
        }

        return $servers;
    }
}
