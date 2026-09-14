<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AudioMixerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Audio Mixer API Controller
 * Backend Audio Mixing for BelieVoo Live Streaming
 * Provides REST API for controlling FFmpeg audio mixer
 */
class AudioMixerController extends Controller
{
    private AudioMixerService $audioMixer;
    
    public function __construct(AudioMixerService $audioMixer)
    {
        $this->audioMixer = $audioMixer;
    }
    
    /**
     * Initialize audio mixer for a stream
     * POST /api/audio-mixer/init
     */
    public function initialize(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stream_id' => 'required|string|max:255',
            'music_url' => 'nullable|url',
            'voice_volume' => 'nullable|numeric|min:0|max:2',
            'music_volume' => 'nullable|numeric|min:0|max:2',
            'bitrate' => 'nullable|integer|min:64|max:320',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }
        
        $config = [
            'music_url' => $request->input('music_url'),
            'voice_volume' => $request->input('voice_volume', 1.0),
            'music_volume' => $request->input('music_volume', 0.3),
            'voice_weight' => $request->input('voice_volume', 1.0),
            'music_weight' => $request->input('music_volume', 0.5),
            'bitrate' => $request->input('bitrate', 192),
        ];
        
        try {
            $result = $this->audioMixer->initializeMixer(
                $request->input('stream_id'),
                $config
            );
            
            // Auto-start mixer
            $this->audioMixer->startMixer($result['mixer_id']);
            
            Log::info('Audio mixer API initialized', [
                'stream_id' => $request->input('stream_id'),
                'mixer_id' => $result['mixer_id']
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Audio mixer initialized and started'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to initialize audio mixer', [
                'error' => $e->getMessage(),
                'stream_id' => $request->input('stream_id')
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize mixer',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Start audio mixer
     * POST /api/audio-mixer/{mixer_id}/start
     */
    public function start(string $mixerId)
    {
        $success = $this->audioMixer->startMixer($mixerId);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Mixer started' : 'Failed to start mixer'
        ]);
    }
    
    /**
     * Stop audio mixer
     * POST /api/audio-mixer/{mixer_id}/stop
     */
    public function stop(string $mixerId)
    {
        $success = $this->audioMixer->stopMixer($mixerId);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Mixer stopped' : 'Mixer not found'
        ]);
    }
    
    /**
     * Adjust volumes in real-time
     * POST /api/audio-mixer/{mixer_id}/volume
     */
    public function adjustVolume(Request $request, string $mixerId)
    {
        $validator = Validator::make($request->all(), [
            'voice_volume' => 'required|numeric|min:0|max:2',
            'music_volume' => 'required|numeric|min:0|max:2',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }
        
        $result = $this->audioMixer->adjustVolumes(
            $mixerId,
            $request->input('voice_volume'),
            $request->input('music_volume')
        );
        
        return response()->json($result);
    }
    
    /**
     * Switch music track
     * POST /api/audio-mixer/{mixer_id}/music
     */
    public function switchMusic(Request $request, string $mixerId)
    {
        $validator = Validator::make($request->all(), [
            'music_url' => 'required|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }
        
        $success = $this->audioMixer->switchMusicTrack(
            $mixerId,
            $request->input('music_url')
        );
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Music track switched' : 'Failed to switch track'
        ]);
    }
    
    /**
     * Get mixer status
     * GET /api/audio-mixer/{mixer_id}/status
     */
    public function status(string $mixerId)
    {
        $status = $this->audioMixer->getMixerStatus($mixerId);
        
        if (!$status) {
            return response()->json([
                'success' => false,
                'error' => 'Mixer not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }
    
    /**
     * Get all active mixers
     * GET /api/audio-mixer/active
     */
    public function active()
    {
        $mixers = $this->audioMixer->getActiveMixers();
        
        return response()->json([
            'success' => true,
            'count' => count($mixers),
            'data' => $mixers
        ]);
    }
    
    /**
     * Check FFmpeg status
     * GET /api/audio-mixer/ffmpeg-check
     */
    public function checkFFmpeg()
    {
        $status = AudioMixerService::checkFFmpeg();
        
        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }
    
    /**
     * Health check endpoint
     * GET /api/audio-mixer/health
     */
    public function health()
    {
        $ffmpegStatus = AudioMixerService::checkFFmpeg();
        $activeMixers = $this->audioMixer->getActiveMixers();
        
        return response()->json([
            'success' => true,
            'status' => 'healthy',
            'ffmpeg_available' => $ffmpegStatus['available'],
            'ffmpeg_version' => $ffmpegStatus['version'],
            'active_mixers' => count($activeMixers),
            'timestamp' => now()->toIso8601String()
        ]);
    }
}
