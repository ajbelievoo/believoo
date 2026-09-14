<?php

namespace App\Services;

use App\Models\StreamingProject;
use App\Models\StreamingSubscription;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StreamingKeyService
{
    // Region → subdomain map
    private const REGION_SUBDOMAINS = [
        'global' => 'stream.believoo.com',
        'in'     => 'in.stream.believoo.com',
    ];

    /**
     * Generate a cryptographically random AppID (32 alphanumeric chars).
     */
    public function generateAppId(): string
    {
        $raw = base64_encode(random_bytes(32));
        $alphanum = preg_replace('/[^a-zA-Z0-9]/', '', $raw);
        return substr(str_pad($alphanum, 32, $alphanum), 0, 32);
    }

    /**
     * Generate a 64-character hex AppCertificate.
     */
    public function generateAppCertificate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Generate a 64-character hex REST API Key.
     */
    public function generateRestApiKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Derive region-aware Stream URLs from AppID and region.
     *
     * @return array{rtmp_url: string, webrtc_url: string}
     */
    public function deriveUrls(string $appId, string $region = 'global'): array
    {
        $host = self::REGION_SUBDOMAINS[$region] ?? self::REGION_SUBDOMAINS['global'];
        return [
            'rtmp_url'   => "rtmp://{$host}/live/{$appId}",
            'webrtc_url' => "wss://{$host}/webrtc/{$appId}",
        ];
    }

    /**
     * Create a new StreamingProject with generated keys for a given subscription.
     * Retries once on failure (Requirement 4.6).
     *
     * @throws \RuntimeException if both attempts fail
     */
    public function createProjectForSubscription(
        StreamingSubscription $subscription,
        string $name = 'Default Project',
        string $region = 'global'
    ): StreamingProject {
        $attempt = 0;
        $lastException = null;

        while ($attempt < 2) {
            try {
                $appId  = $this->generateAppId();
                $cert   = $this->generateAppCertificate();
                $apiKey = $this->generateRestApiKey();
                $urls   = $this->deriveUrls($appId, $region);

                $project = StreamingProject::create([
                    'user_id'                   => $subscription->user_id,
                    'streaming_subscription_id' => $subscription->id,
                    'name'                      => $name,
                    'app_id'                    => $appId,
                    'app_certificate'           => Crypt::encryptString($cert),
                    'rest_api_key'              => Crypt::encryptString($apiKey),
                    'region'                    => $region,
                    'rtmp_url'                  => $urls['rtmp_url'],
                    'webrtc_url'                => $urls['webrtc_url'],
                    'status'                    => 'active',
                ]);

                return $project;
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;
                Log::error('StreamingKeyService: project creation failed', [
                    'user_id'                   => $subscription->user_id,
                    'streaming_subscription_id' => $subscription->id,
                    'attempt'                   => $attempt,
                    'error'                     => $e->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException(
            "Key generation failed after 2 attempts for subscription {$subscription->id}",
            0,
            $lastException
        );
    }

    /**
     * Regenerate AppCertificate and REST_API_Key for an existing project.
     * AppID is never changed. Uses a DB transaction for atomicity.
     */
    public function regenerateKeys(StreamingProject $project): StreamingProject
    {
        DB::transaction(function () use ($project) {
            $project->update([
                'app_certificate' => Crypt::encryptString($this->generateAppCertificate()),
                'rest_api_key'    => Crypt::encryptString($this->generateRestApiKey()),
            ]);
        });

        Log::info('streaming_key_regenerated', [
            'user_id'              => $project->user_id,
            'streaming_project_id' => $project->id,
            'timestamp'            => now()->toIso8601String(),
        ]);

        return $project->fresh();
    }

    /**
     * Generate a short-lived HMAC stream authentication token.
     * Token is NOT stored anywhere.
     *
     * @return array{token: string, expires_at: int}
     */
    public function generateStreamToken(
        string $appId,
        string $appCertificate,
        int $expirySeconds = 3600
    ): array {
        $issuedAt  = time();
        $expiresAt = $issuedAt + $expirySeconds;

        $payload = base64_encode(json_encode([
            'app_id' => $appId,
            'iat'    => $issuedAt,
            'exp'    => $expiresAt,
        ]));

        $signature = hash_hmac('sha256', $payload, $appCertificate);
        $token     = $payload . '.' . $signature;

        return [
            'token'      => $token,
            'expires_at' => $expiresAt,
        ];
    }
}
