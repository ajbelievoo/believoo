<?php

namespace App\Services;

use App\Models\StreamingApiKey;
use App\Models\StreamingUsageLog;
use App\Models\StreamRecording;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Streaming Metrics Collector
 * 
 * Fetches real-time metrics from streaming servers (SRS/MediaMTX)
 * and updates usage tracking in the database.
 */
class StreamingMetricsCollector
{
    private string $serverType;
    private string $apiBaseUrl;
    private ?string $apiSecret;

    public function __construct()
    {
        $this->serverType = config('streaming.server_type', 'srs');
        $this->apiBaseUrl = config('streaming.api_base_url', 'http://localhost:8080');
        $this->apiSecret = config('streaming.api_secret');
    }

    /**
     * Collect metrics for all active API keys
     */
    public function collectAll(): array
    {
        $results = [
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        $activeKeys = StreamingApiKey::where('status', 'active')->get();

        foreach ($activeKeys as $apiKey) {
            try {
                $metrics = $this->fetchMetricsForApp($apiKey->app_id);
                
                if ($metrics) {
                    $this->updateUsage($apiKey, $metrics);
                    $results['updated']++;
                }
                
                $results['processed']++;
            } catch (\Exception $e) {
                Log::error('Failed to collect streaming metrics', [
                    'api_key_id' => $apiKey->id,
                    'app_id' => $apiKey->app_id,
                    'error' => $e->getMessage(),
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Collect metrics for a single API key (for real-time dashboard)
     */
    public function collectForKey(StreamingApiKey $apiKey): ?array
    {
        try {
            $metrics = $this->fetchMetricsForApp($apiKey->app_id);
            
            if ($metrics) {
                $this->updateUsage($apiKey, $metrics);
                return $metrics;
            }
        } catch (\Exception $e) {
            Log::error('Failed to collect metrics for key', [
                'api_key_id' => $apiKey->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Fetch metrics from streaming server
     */
    private function fetchMetricsForApp(string $appId): ?array
    {
        return match ($this->serverType) {
            'srs' => $this->fetchFromSRS($appId),
            'mediamtx' => $this->fetchFromMediaMTX($appId),
            'nginx' => $this->fetchFromNginxRtmp($appId),
            default => $this->fetchFromSRS($appId),
        };
    }

    /**
     * Fetch from SRS (Simple Realtime Server)
     * API: http://server:8080/api/v1/streams
     */
    private function fetchFromSRS(string $appId): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->apiBaseUrl}/api/v1/streams");
            
            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $streams = $data['streams'] ?? [];

            $totalBandwidth = 0;
            $totalViewers = 0;
            $activeStreams = 0;
            $streamNames = [];

            foreach ($streams as $stream) {
                $streamName = $stream['name'] ?? '';
                
                // Match stream by app_id prefix or exact match
                if (str_contains($streamName, $appId) || str_starts_with($streamName, $appId)) {
                    $activeStreams++;
                    $streamNames[] = $streamName;
                    
                    // SRS provides kbps for video + audio
                    $videoKbps = $stream['video']['kbps'] ?? 0;
                    $audioKbps = $stream['audio']['kbps'] ?? 0;
                    $totalBandwidth += ($videoKbps + $audioKbps);
                    
                    // SRS clients count
                    $clients = $stream['clients'] ?? 0;
                    // Publisher is 1 client, so viewers = clients - 1
                    $totalViewers += max(0, $clients - 1);
                }
            }

            // Convert kbps to GB/hour
            $bandwidthGbPerHour = ($totalBandwidth * 3600) / (8 * 1024 * 1024);

            return [
                'bandwidth_kbps' => $totalBandwidth,
                'bandwidth_gb_per_hour' => round($bandwidthGbPerHour, 4),
                'concurrent_viewers' => $totalViewers,
                'active_streams' => $activeStreams,
                'stream_names' => $streamNames,
                'server_type' => 'srs',
            ];
        } catch (\Exception $e) {
            Log::warning('SRS metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch from MediaMTX
     * API: http://server:40001/v3/paths/list
     */
    private function fetchFromMediaMTX(string $appId): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->apiBaseUrl}/v3/paths/list");

            if (!$response->successful()) {
                return null;
            }

            $data = $response->json();
            $items = $data['items'] ?? [];

            $totalBandwidth = 0;
            $totalViewers = 0;
            $activeStreams = 0;
            $streamNames = [];

            foreach ($items as $item) {
                $pathName = $item['name'] ?? '';

                if (str_contains($pathName, $appId) || str_starts_with($pathName, $appId)) {
                    $activeStreams++;
                    $streamNames[] = $pathName;

                    // MediaMTX v3: no bytesRate field; use inboundBytes to detect active stream
                    // Approximate bitrate from typical stream (500k video + 64k audio ≈ 564 kbps)
                    $hasActiveSource = !empty($item['source']['type'] ?? '');
                    if ($hasActiveSource) {
                        $totalBandwidth += 564; // Estimated kbps when stream is active
                    }

                    // Count readers (viewers)
                    $readers = $item['readers'] ?? [];
                    $totalViewers += is_array($readers) ? count($readers) : ($item['readerCount'] ?? 0);
                }
            }

            $bandwidthGbPerHour = ($totalBandwidth * 3600) / (8 * 1024 * 1024);

            return [
                'bandwidth_kbps' => $totalBandwidth,
                'bandwidth_gb_per_hour' => round($bandwidthGbPerHour, 4),
                'concurrent_viewers' => $totalViewers,
                'active_streams' => $activeStreams,
                'stream_names' => $streamNames,
                'server_type' => 'mediamtx',
            ];
        } catch (\Exception $e) {
            Log::warning('MediaMTX metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch from Nginx-RTMP stats page
     */
    private function fetchFromNginxRtmp(string $appId): ?array
    {
        try {
            $response = Http::timeout(10)->get("{$this->apiBaseUrl}/stat");
            
            if (!$response->successful()) {
                return null;
            }

            $xml = simplexml_load_string($response->body());
            if (!$xml) {
                return null;
            }

            $totalBandwidth = 0;
            $totalViewers = 0;
            $activeStreams = 0;
            $streamNames = [];

            foreach ($xml->server->application as $app) {
                $appName = (string) ($app->name ?? '');
                
                if (str_contains($appName, $appId) || str_starts_with($appName, $appId)) {
                    foreach ($app->live->stream as $stream) {
                        $activeStreams++;
                        $streamNames[] = (string) $stream->name;
                        
                        $bwIn = (int) ($stream->bw_in ?? 0);
                        $totalBandwidth += $bwIn / 1024; // Convert to kbps
                        
                        $nclients = (int) ($stream->nclients ?? 0);
                        $totalViewers += max(0, $nclients - 1);
                    }
                }
            }

            $bandwidthGbPerHour = ($totalBandwidth * 3600) / (8 * 1024 * 1024);

            return [
                'bandwidth_kbps' => $totalBandwidth,
                'bandwidth_gb_per_hour' => round($bandwidthGbPerHour, 4),
                'concurrent_viewers' => $totalViewers,
                'active_streams' => $activeStreams,
                'stream_names' => $streamNames,
                'server_type' => 'nginx',
            ];
        } catch (\Exception $e) {
            Log::warning('Nginx-RTMP metrics fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Update usage tracking in database
     */
    private function updateUsage(StreamingApiKey $apiKey, array $metrics): void
    {
        $now = now();

        // Update real-time viewer count on API key
        $apiKey->update([
            'current_viewers' => $metrics['concurrent_viewers'],
            'last_used_at' => $now,
        ]);

        // Only log if there is active streaming
        if ($metrics['active_streams'] > 0 || $metrics['concurrent_viewers'] > 0) {
            $bandwidthMb = ($metrics['bandwidth_kbps'] * 60) / (8 * 1024); // per minute in MB

            StreamingUsageLog::create([
                'user_id' => $apiKey->user_id,
                'streaming_api_key_id' => $apiKey->id,
                'stream_id' => $metrics['stream_names'][0] ?? $apiKey->app_id,
                'channel_name' => $metrics['stream_names'][0] ?? 'default',
                'stream_type' => 'rtmp',
                'viewer_count' => $metrics['concurrent_viewers'],
                'bandwidth_used_mb' => round($bandwidthMb, 2),
                'duration_minutes' => 1,
                'avg_bitrate_kbps' => round($metrics['bandwidth_kbps']),
                'started_at' => $now,
                'ended_at' => $now,
                'metadata' => [
                    'active_streams' => $metrics['active_streams'],
                    'stream_names' => $metrics['stream_names'],
                    'server_type' => $metrics['server_type'],
                ],
            ]);

            // Update cumulative stats on API key using the model's helper
            $apiKey->recordUsage($bandwidthMb, 1);
        }

        // Update recording stats if applicable
        $this->updateRecordingStats($apiKey);
    }

    /**
     * Update recording storage stats
     */
    private function updateRecordingStats(StreamingApiKey $apiKey): void
    {
        $totalStorage = StreamRecording::where('user_id', $apiKey->user_id)
            ->where('status', 'completed')
            ->sum('file_size_mb');

        if ($totalStorage > 0) {
            $apiKey->update([
                'storage_used_gb' => round($totalStorage / 1024, 2),
            ]);
        }
    }

    /**
     * Get current viewers for a specific API key (real-time)
     */
    public function getCurrentViewers(StreamingApiKey $apiKey): int
    {
        $metrics = $this->fetchMetricsForApp($apiKey->app_id);
        return $metrics['concurrent_viewers'] ?? 0;
    }

    /**
     * Check if a stream is currently live
     */
    public function isStreamLive(StreamingApiKey $apiKey): bool
    {
        $metrics = $this->fetchMetricsForApp($apiKey->app_id);
        return ($metrics['active_streams'] ?? 0) > 0;
    }
}
