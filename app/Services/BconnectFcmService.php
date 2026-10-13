<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends FCM v1 data pushes to BMyDesk Android agents.
 *
 * Used to wake a closed/doze host app when a viewer requests a session —
 * the app registers its fcm_token via /register; we sign a Google OAuth
 * token from the shared service account (same Firebase project as HiTune)
 * and POST to the FCM v1 endpoint. Tokens are cached ~50 min.
 */
class BconnectFcmService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /** Service account JSON — shared hitune-live-box Firebase project. */
    private static function serviceAccount(): ?array
    {
        $path = env('BMYDESK_FCM_SA', storage_path('app/fcm-service-account.json'));
        if (!is_file($path)) return null;
        $sa = json_decode((string) @file_get_contents($path), true);
        return (is_array($sa) && !empty($sa['client_email']) && !empty($sa['private_key'])) ? $sa : null;
    }

    private static function accessToken(array $sa): ?string
    {
        return Cache::remember('bmd-fcm-token', 3000, function () use ($sa) {
            try {
                $jwt = JWT::encode([
                    'iss' => $sa['client_email'],
                    'sub' => $sa['client_email'],
                    'aud' => self::TOKEN_URL,
                    'scope' => self::SCOPE,
                    'iat' => time(),
                    'exp' => time() + 3600,
                ], $sa['private_key'], 'RS256');
                $res = Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
                return $res->json('access_token');
            } catch (\Throwable $e) {
                Log::warning('bmd fcm token: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Fire a high-priority data push. Never throws — push is best-effort,
     * the ws/HTTP-poll path is the primary delivery.
     */
    public static function send(string $fcmToken, array $data): bool
    {
        try {
            $sa = self::serviceAccount();
            if (!$sa) return false;
            $token = self::accessToken($sa);
            if (!$token) return false;
            $res = Http::withToken($token)->timeout(10)->post(
                'https://fcm.googleapis.com/v1/projects/' . $sa['project_id'] . '/messages:send',
                [
                    'message' => [
                        'token' => $fcmToken,
                        'data' => array_map('strval', $data),
                        'android' => ['priority' => 'HIGH'],
                    ],
                ]
            );
            if (!$res->successful()) Log::warning('bmd fcm send: ' . $res->status() . ' ' . substr($res->body(), 0, 200));
            return $res->successful();
        } catch (\Throwable $e) {
            Log::warning('bmd fcm send: ' . $e->getMessage());
            return false;
        }
    }
}
