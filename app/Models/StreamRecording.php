<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamRecording extends Model
{
    protected $fillable = [
        'user_id',
        'hosting_id',
        'api_key_id',
        'recording_name',
        'file_path',
        'file_url',
        'file_size_mb',
        'duration_seconds',
        'started_at',
        'ended_at',
        'status',
        'auto_delete_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_delete_at' => 'datetime',
        'file_size_mb' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(UserHosting::class, 'hosting_id');
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(StreamingApiKey::class, 'api_key_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['recording', 'completed']);
    }

    public function scopeReadyToDelete($query)
    {
        return $query->where('status', '!=', 'deleted')
            ->whereNotNull('auto_delete_at')
            ->where('auto_delete_at', '<=', now());
    }

    public function getDurationFormattedAttribute(): string
    {
        $seconds = $this->duration_seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
