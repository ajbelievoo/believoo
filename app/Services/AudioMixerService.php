<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

/**
 * BelieVoo Audio Mixer Service
 * Real-time FFmpeg-based audio mixing for live streaming
 * Mixes host voice + digital music into single high-quality stream
 */
class AudioMixerService
{
    private string $tempDir;
    private array $activeMixers = [];
    
    public function __construct()
    {
        $this->tempDir = storage_path('app/audio-mix');
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    /**
     * Initialize audio mixer for a stream
     * Creates FFmpeg pipeline: [Host Voice] + [Music] → [Mixed Output]
     */
    public function initializeMixer(string $streamId, array $config = []): array
    {
        $mixerId = 'mixer_' . $streamId . '_' . Str::random(8);
        $outputUrl = $config['output_url'] ?? 'rtmp://localhost:1935/mixed/' . $streamId;
        
        // FFmpeg command for real-time audio mixing
        $ffmpegCmd = $this->buildFFmpegCommand($mixerId, $config);
        
        $this->activeMixers[$mixerId] = [
            'stream_id' => $streamId,
            'started_at' => now(),
            'config' => $config,
            'output_url' => $outputUrl,
            'voice_input' => 'rtmp://localhost:1935/voice/' . $streamId,
            'music_input' => $config['music_url'] ?? null,
            'pid' => null,
        ];
        
        Log::info("Audio mixer initialized", [
            'mixer_id' => $mixerId,
            'stream_id' => $streamId,
            'output' => $outputUrl
        ]);
        
        return [
            'mixer_id' => $mixerId,
            'voice_endpoint' => $this->activeMixers[$mixerId]['voice_input'],
            'music_endpoint' => $this->getMusicInputEndpoint($mixerId),
            'mixed_output' => $outputUrl,
            'status' => 'initialized'
        ];
    }
    
    /**
     * Build FFmpeg command for real-time mixing
     * Uses amix filter for professional audio blending
     */
    private function buildFFmpegCommand(string $mixerId, array $config): string
    {
        $voiceInput = $this->activeMixers[$mixerId]['voice_input'] ?? 'rtmp://localhost:1935/voice/' . $mixerId;
        $musicInput = $config['music_url'] ?? 'anullsrc=r=44100:cl=stereo';
        $output = $this->activeMixers[$mixerId]['output_url'] ?? 'rtmp://localhost:1935/mixed/' . $mixerId;
        
        // Professional audio mixing pipeline
        $cmd = sprintf(
            'ffmpeg -loglevel error ' .
            // Input 1: Host Voice Stream (RTMP)
            '-thread_queue_size 512 -i %s ' .
            // Input 2: Digital Music Stream (HTTP/RTMP/File)
            '-thread_queue_size 512 -i %s ' .
            // Audio filter complex: Mix with volume control
            '-filter_complex "' .
            '[0:a]volume=%s,aresample=async=1:first_pts=0[a1];' .
            '[1:a]volume=%s,aresample=async=1:first_pts=0[a2];' .
            '[a1][a2]amix=inputs=2:duration=longest:dropout_transition=3:weights=\'%s %s\'[amixed]' .
            '" ' .
            // Audio codec settings (High quality AAC)
            '-map "[amixed]" ' .
            '-c:a aac -b:a %sk -ar 48000 -ac 2 ' .
            '-bufsize %sk ' .
            // Output: Mixed stream to RTMP
            '-f flv %s',
            escapeshellarg($voiceInput),
            escapeshellarg($musicInput),
            $config['voice_volume'] ?? '1.0',
            $config['music_volume'] ?? '0.3',
            $config['voice_weight'] ?? '1.0',
            $config['music_weight'] ?? '0.5',
            $config['bitrate'] ?? '192',
            ($config['bitrate'] ?? '192') * 2,
            escapeshellarg($output)
        );
        
        return $cmd;
    }
    
    /**
     * Start the FFmpeg mixer process
     */
    public function startMixer(string $mixerId): bool
    {
        if (!isset($this->activeMixers[$mixerId])) {
            Log::error("Mixer not found: {$mixerId}");
            return false;
        }
        
        $cmd = $this->buildFFmpegCommand($mixerId, $this->activeMixers[$mixerId]['config']);
        
        // Start FFmpeg in background
        $process = proc_open(
            $cmd . ' 2>&1 &',
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );
        
        if (is_resource($process)) {
            $status = proc_get_status($process);
            $this->activeMixers[$mixerId]['pid'] = $status['pid'];
            $this->activeMixers[$mixerId]['status'] = 'running';
            
            Log::info("Audio mixer started", [
                'mixer_id' => $mixerId,
                'pid' => $status['pid']
            ]);
            
            return true;
        }
        
        Log::error("Failed to start audio mixer", ['mixer_id' => $mixerId]);
        return false;
    }
    
    /**
     * Update music track (switch songs without stopping mixer)
     */
    public function switchMusicTrack(string $mixerId, string $newMusicUrl): bool
    {
        if (!isset($this->activeMixers[$mixerId])) {
            return false;
        }
        
        // Graceful transition: Fade out old, fade in new
        $this->activeMixers[$mixerId]['config']['music_url'] = $newMusicUrl;
        
        Log::info("Music track switched", [
            'mixer_id' => $mixerId,
            'new_track' => $newMusicUrl
        ]);
        
        // For live switching, we'd use FFmpeg's sendcmd or reconnect
        // For now, restart mixer with new track
        $this->restartMixer($mixerId);
        
        return true;
    }
    
    /**
     * Adjust volumes in real-time
     */
    public function adjustVolumes(string $mixerId, float $voiceVolume, float $musicVolume): array
    {
        if (!isset($this->activeMixers[$mixerId])) {
            return ['success' => false, 'error' => 'Mixer not found'];
        }
        
        $this->activeMixers[$mixerId]['config']['voice_volume'] = max(0, min(2.0, $voiceVolume));
        $this->activeMixers[$mixerId]['config']['music_volume'] = max(0, min(2.0, $musicVolume));
        
        // Send volume update to running FFmpeg instance
        // Using zmqsend or reload mechanism
        $this->sendCommandToMixer($mixerId, "volume|voice|{$voiceVolume}");
        $this->sendCommandToMixer($mixerId, "volume|music|{$musicVolume}");
        
        return [
            'success' => true,
            'voice_volume' => $voiceVolume,
            'music_volume' => $musicVolume
        ];
    }
    
    /**
     * Get music input endpoint (where app sends music)
     */
    public function getMusicInputEndpoint(string $mixerId): string
    {
        // Option 1: HTTP Progressive Download
        // Option 2: Local file path
        // Option 3: RTMP push endpoint
        return 'http://localhost:8080/audio/music/' . $mixerId;
    }
    
    /**
     * Send control command to running FFmpeg instance
     */
    private function sendCommandToMixer(string $mixerId, string $command): bool
    {
        $fifoPath = $this->tempDir . '/' . $mixerId . '.fifo';
        
        if (file_exists($fifoPath)) {
            return file_put_contents($fifoPath, $command . "\n") !== false;
        }
        
        return false;
    }
    
    /**
     * Stop and cleanup mixer
     */
    public function stopMixer(string $mixerId): bool
    {
        if (!isset($this->activeMixers[$mixerId])) {
            return false;
        }
        
        $pid = $this->activeMixers[$mixerId]['pid'] ?? null;
        
        if ($pid) {
            // Graceful shutdown
            posix_kill($pid, SIGTERM);
            sleep(1);
            
            // Force kill if still running
            if (posix_kill($pid, 0)) {
                posix_kill($pid, SIGKILL);
            }
        }
        
        // Cleanup
        $fifoPath = $this->tempDir . '/' . $mixerId . '.fifo';
        if (file_exists($fifoPath)) {
            unlink($fifoPath);
        }
        
        unset($this->activeMixers[$mixerId]);
        
        Log::info("Audio mixer stopped", ['mixer_id' => $mixerId]);
        
        return true;
    }
    
    /**
     * Restart mixer (for config changes)
     */
    private function restartMixer(string $mixerId): void
    {
        $this->stopMixer($mixerId);
        sleep(1);
        $this->startMixer($mixerId);
    }
    
    /**
     * Get mixer status
     */
    public function getMixerStatus(string $mixerId): ?array
    {
        if (!isset($this->activeMixers[$mixerId])) {
            return null;
        }
        
        $mixer = $this->activeMixers[$mixerId];
        $pid = $mixer['pid'] ?? null;
        
        $isRunning = $pid ? posix_kill($pid, 0) : false;
        
        return [
            'mixer_id' => $mixerId,
            'stream_id' => $mixer['stream_id'],
            'status' => $isRunning ? 'running' : 'stopped',
            'pid' => $pid,
            'started_at' => $mixer['started_at'],
            'voice_volume' => $mixer['config']['voice_volume'] ?? 1.0,
            'music_volume' => $mixer['config']['music_volume'] ?? 0.3,
            'output_url' => $mixer['output_url']
        ];
    }
    
    /**
     * Get all active mixers
     */
    public function getActiveMixers(): array
    {
        $result = [];
        foreach ($this->activeMixers as $mixerId => $mixer) {
            $result[] = $this->getMixerStatus($mixerId);
        }
        return array_filter($result);
    }
    
    /**
     * Check FFmpeg availability
     */
    public static function checkFFmpeg(): array
    {
        $output = [];
        $returnVar = 0;
        
        exec('ffmpeg -version 2>&1 | head -1', $output, $returnVar);
        
        return [
            'available' => $returnVar === 0,
            'version' => $output[0] ?? 'Unknown',
            'path' => trim(shell_exec('which ffmpeg') ?: 'Not found')
        ];
    }
}
