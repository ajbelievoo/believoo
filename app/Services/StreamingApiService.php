<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StreamingApiKey;
use App\Models\StreamingPlan;
use App\Models\User;
use App\Models\UserHosting;
use Illuminate\Support\Facades\Log;

class StreamingApiService
{
    /**
     * Provision streaming API keys for a new order
     */
    public function provisionFromOrder(Order $order): ?StreamingApiKey
    {
        try {
            // Check if order has streaming addon or is a streaming plan
            if (!$order->has_streaming_addon && !$order->streaming_plan_id) {
                return null;
            }

            $planId = $order->streaming_plan_id;
            if (!$planId) {
                return null;
            }

            $plan = StreamingPlan::find($planId);
            if (!$plan || !$plan->is_active) {
                Log::error('Streaming plan not found or inactive', [
                    'order_id' => $order->id,
                    'plan_id' => $planId,
                ]);
                return null;
            }

            return $this->createApiKey($order->user, $plan, $order);
        } catch (\Exception $e) {
            Log::error('Failed to provision streaming API keys', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create new API keys for a user
     */
    public function createApiKey(User $user, StreamingPlan $plan, ?Order $order = null): StreamingApiKey
    {
        // Check if user already has active keys for this plan
        $existingKey = StreamingApiKey::forUser($user->id)
            ->where('streaming_plan_id', $plan->id)
            ->where('status', 'active')
            ->first();

        if ($existingKey) {
            return $existingKey;
        }

        // Generate unique stream URLs based on plan configuration
        $appId = StreamingApiKey::generateAppId();
        $streamBase = config('streaming.ingest_base_url', 'rtmp://live.believoo.com');
        $playbackBase = config('streaming.playback_base_url', 'https://live.believoo.com');
        $webrtcBase = config('streaming.webrtc_base_url', 'wss://live.believoo.com');

        $apiKey = StreamingApiKey::create([
            'user_id' => $user->id,
            'streaming_plan_id' => $plan->id,
            'order_id' => $order?->id,
            'app_id' => $appId,
            'app_certificate' => StreamingApiKey::generateCertificate(),
            'rest_api_key' => StreamingApiKey::generateRestApiKey(),
            'rest_api_secret' => StreamingApiKey::generateRestApiSecret(),
            
            // Stream URLs
            'rtmp_ingest_url' => "{$streamBase}/live/{$appId}",
            'webrtc_ingest_url' => "{$webrtcBase}/ingest/{$appId}",
            'hls_playback_url' => "{$playbackBase}/hls/{$appId}.m3u8",
            'dash_playback_url' => $plan->dash_support ? "{$playbackBase}/dash/{$appId}.mpd" : null,
            'web_rtc_url' => $plan->webrtc_support ? "{$webrtcBase}/play/{$appId}" : null,
            
            'status' => 'active',
            'expires_at' => now()->addMonths($order?->billing_months ?? 1),
            'allowed_domains' => [],
            'allowed_ips' => [],
        ]);

        Log::info('Streaming API keys created', [
            'user_id' => $user->id,
            'api_key_id' => $apiKey->id,
            'app_id' => $apiKey->app_id,
            'plan' => $plan->name,
        ]);

        return $apiKey;
    }

    /**
     * Attach streaming addon to existing VPS hosting
     */
    public function attachToHosting(UserHosting $hosting, StreamingPlan $plan, Order $order): ?StreamingApiKey
    {
        try {
            $apiKey = $this->createApiKey($hosting->user, $plan, $order);

            if ($apiKey) {
                $hosting->update([
                    'has_streaming_addon' => true,
                    'streaming_api_key_id' => $apiKey->id,
                ]);
            }

            return $apiKey;
        } catch (\Exception $e) {
            Log::error('Failed to attach streaming addon to hosting', [
                'hosting_id' => $hosting->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Regenerate API keys (with security check)
     */
    public function regenerateKeys(StreamingApiKey $apiKey, bool $force = false): StreamingApiKey
    {
        if ($apiKey->regeneration_locked && !$force) {
            throw new \Exception('Key regeneration is locked for this account. Contact support.');
        }

        $oldAppId = $apiKey->app_id;

        // Generate new keys
        $apiKey->update([
            'app_id' => StreamingApiKey::generateAppId(),
            'app_certificate' => StreamingApiKey::generateCertificate(),
            'rest_api_key' => StreamingApiKey::generateRestApiKey(),
            'rest_api_secret' => StreamingApiKey::generateRestApiSecret(),
        ]);

        // Update stream URLs with new App ID
        $streamBase = config('streaming.ingest_base_url', 'rtmp://live.believoo.com');
        $playbackBase = config('streaming.playback_base_url', 'https://live.believoo.com');
        $webrtcBase = config('streaming.webrtc_base_url', 'wss://live.believoo.com');

        $apiKey->update([
            'rtmp_ingest_url' => "{$streamBase}/live/{$apiKey->app_id}",
            'webrtc_ingest_url' => "{$webrtcBase}/ingest/{$apiKey->app_id}",
            'hls_playback_url' => "{$playbackBase}/hls/{$apiKey->app_id}.m3u8",
            'dash_playback_url' => $apiKey->plan?->dash_support ? "{$playbackBase}/dash/{$apiKey->app_id}.mpd" : null,
            'web_rtc_url' => $apiKey->plan?->webrtc_support ? "{$webrtcBase}/play/{$apiKey->app_id}" : null,
        ]);

        Log::info('Streaming API keys regenerated', [
            'api_key_id' => $apiKey->id,
            'user_id' => $apiKey->user_id,
            'old_app_id' => $oldAppId,
            'new_app_id' => $apiKey->app_id,
        ]);

        return $apiKey->fresh();
    }

    /**
     * Validate stream token
     */
    public function validateStreamToken(string $token, string $channel, string $appId): bool
    {
        try {
            $data = json_decode(base64_decode($token), true);
            
            if (!$data || !is_array($data)) {
                return false;
            }

            // Check required fields
            if (!isset($data['app_id'], $data['channel'], $data['timestamp'], $data['expiration'], $data['signature'])) {
                return false;
            }

            // Verify App ID matches
            if ($data['app_id'] !== $appId) {
                return false;
            }

            // Verify channel matches
            if ($data['channel'] !== $channel) {
                return false;
            }

            // Check expiration
            if ($data['expiration'] < time()) {
                return false;
            }

            // Get API key for certificate
            $apiKey = StreamingApiKey::where('app_id', $appId)->first();
            if (!$apiKey) {
                return false;
            }

            // Verify signature
            $uid = $data['uid'] ?? 0;
            $payload = $appId . $channel . $uid . $data['timestamp'] . $data['expiration'];
            $expectedSignature = hash_hmac('sha256', $payload, $apiKey->app_certificate);

            return hash_equals($expectedSignature, $data['signature']);
        } catch (\Exception $e) {
            Log::error('Token validation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get usage statistics for a user
     */
    public function getUserStats(int $userId): array
    {
        $apiKeys = StreamingApiKey::forUser($userId)->with('plan')->get();

        $totalBandwidth = 0;
        $totalStorage = 0;
        $totalMinutes = 0;
        $activeKeys = 0;

        foreach ($apiKeys as $key) {
            $totalBandwidth += $key->bandwidth_used_gb;
            $totalStorage += $key->storage_used_gb;
            $totalMinutes += $key->total_stream_minutes;
            if ($key->isActive()) {
                $activeKeys++;
            }
        }

        return [
            'total_api_keys' => $apiKeys->count(),
            'active_keys' => $activeKeys,
            'total_bandwidth_gb' => round($totalBandwidth, 2),
            'total_storage_gb' => round($totalStorage, 2),
            'total_stream_hours' => round($totalMinutes / 60, 1),
            'api_keys' => $apiKeys,
        ];
    }

    /**
     * Admin: Suspend user's streaming access
     */
    public function suspendAccess(int $userId, ?string $reason = null): void
    {
        $keys = StreamingApiKey::forUser($userId)->where('status', 'active')->get();
        
        foreach ($keys as $key) {
            $key->suspend();
        }

        Log::info('Streaming access suspended by admin', [
            'user_id' => $userId,
            'reason' => $reason,
            'keys_suspended' => $keys->count(),
        ]);
    }

    /**
     * Admin: Restore user's streaming access
     */
    public function restoreAccess(int $userId): void
    {
        $keys = StreamingApiKey::forUser($userId)->where('status', 'suspended')->get();
        
        foreach ($keys as $key) {
            $key->activate();
        }

        Log::info('Streaming access restored by admin', [
            'user_id' => $userId,
            'keys_activated' => $keys->count(),
        ]);
    }

    /**
     * Admin: Upgrade user's streaming plan
     */
    public function upgradePlan(StreamingApiKey $apiKey, StreamingPlan $newPlan): StreamingApiKey
    {
        $oldPlanName = $apiKey->plan?->name ?? 'Unknown';

        $apiKey->update([
            'streaming_plan_id' => $newPlan->id,
        ]);

        // Regenerate URLs with new plan features
        $streamBase = config('streaming.ingest_base_url', 'rtmp://live.believoo.com');
        $playbackBase = config('streaming.playback_base_url', 'https://live.believoo.com');
        $webrtcBase = config('streaming.webrtc_base_url', 'wss://live.believoo.com');

        $apiKey->update([
            'rtmp_ingest_url' => "{$streamBase}/live/{$apiKey->app_id}",
            'webrtc_ingest_url' => "{$webrtcBase}/ingest/{$apiKey->app_id}",
            'hls_playback_url' => "{$playbackBase}/hls/{$apiKey->app_id}.m3u8",
            'dash_playback_url' => $newPlan->dash_support ? "{$playbackBase}/dash/{$apiKey->app_id}.mpd" : null,
            'web_rtc_url' => $newPlan->webrtc_support ? "{$webrtcBase}/play/{$apiKey->app_id}" : null,
        ]);

        Log::info('Streaming plan upgraded', [
            'api_key_id' => $apiKey->id,
            'user_id' => $apiKey->user_id,
            'old_plan' => $oldPlanName,
            'new_plan' => $newPlan->name,
        ]);

        return $apiKey->fresh();
    }

    /**
     * Check if domain is allowed for CORS
     */
    public function isDomainAllowed(StreamingApiKey $apiKey, string $domain): bool
    {
        if (empty($apiKey->allowed_domains)) {
            return true; // No restrictions
        }

        // Strip protocol and www for comparison
        $cleanDomain = preg_replace('#^https?://#', '', $domain);
        $cleanDomain = preg_replace('#^www\.#', '', $cleanDomain);

        foreach ($apiKey->allowed_domains as $allowed) {
            $cleanAllowed = preg_replace('#^https?://#', '', $allowed);
            $cleanAllowed = preg_replace('#^www\.#', '', $cleanAllowed);
            
            // Support wildcard subdomains
            if (str_starts_with($cleanAllowed, '*.')) {
                $pattern = '/^.+' . preg_quote(substr($cleanAllowed, 1), '/') . '$/';
                if (preg_match($pattern, $cleanDomain)) {
                    return true;
                }
            } elseif ($cleanAllowed === $cleanDomain) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIpAllowed(StreamingApiKey $apiKey, string $ip): bool
    {
        if (empty($apiKey->allowed_ips)) {
            return true; // No restrictions
        }

        return in_array($ip, $apiKey->allowed_ips);
    }
}
