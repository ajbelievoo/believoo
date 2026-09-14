<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audio Mixer Session Model
 * Stores FFmpeg audio mixer configuration and status
 */
class AudioMixerSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'mixer_id',
        'stream_id',
        'user_id',
        'streaming_api_key_id',
        'status',
        'started_at',
        'stopped_at',
        'pid',
        'voice_input_url',
        'music_input_url',
        'mixed_output_url',
        'voice_volume',
        'music_volume',
        'bitrate',
        'sample_rate',
        'channels',
        'config',
        'error_message',
        'duration_seconds',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'config' => 'array',
        'voice_volume' => 'float',
        'music_volume' => 'float',
    ];

    /**
     * User who owns this mixer session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Associated streaming API key
     */
    public function streamingApiKey(): BelongsTo
    {
        return $this->belongsTo(StreamingApiKey::class);
    }

    /**
     * Scope: Active mixers
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['initialized', 'running', 'paused']);
    }

    /**
     * Scope: Running mixers
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope: By stream ID
     */
    public function scopeByStream($query, string $streamId)
    {
        return $query->where('stream_id', $streamId);
    }

    /**
     * Check if mixer is currently running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running' && $this->pid && posix_kill($this->pid, 0);
    }

    /**
     * Get duration in human-readable format
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->started_at) {
            return '00:00:00';
        }
        
        $end = $this->stopped_at ?? now();
        $seconds = $this->started_at->diffInSeconds($end);
        
        return gmdate('H:i:s', $seconds);
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'running' => 'bg-green-500',
            'initialized' => 'bg-yellow-500',
            'paused' => 'bg-orange-500',
            'stopped' => 'bg-gray-500',
            'error' => 'bg-red-500',
            default => 'bg-gray-500',
        };
    }
}
