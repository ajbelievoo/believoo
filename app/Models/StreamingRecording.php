<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingRecording extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'channel_name',
        'title',
        'description',
        'file_path',
        'file_size_mb',
        'duration_seconds',
        'status',
        'is_public',
        'download_url',
        'playback_url',
        'started_at',
        'ended_at',
        'retention_days',
    ];

    protected $casts = [
        'file_size_mb' => 'decimal:2',
        'is_public' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(StreamingApiKey::class, 'api_key_id');
    }

    public function getThumbnailUrlAttribute(): string
    {
        return asset('storage/recordings/' . $this->id . '/thumbnail.jpg');
    }

    public function isExpired(): bool
    {
        if (!$this->retention_days) return false;
        
        return $this->created_at->addDays($this->retention_days)->isPast();
    }

    protected static function booted(): void
    {
        static::deleting(function ($recording) {
            // Delete physical file
            if (file_exists($recording->file_path)) {
                unlink($recording->file_path);
            }
            
            // Delete thumbnail
            $thumbPath = 'recordings/' . $recording->id . '/thumbnail.jpg';
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($thumbPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($thumbPath);
            }
        });
    }
}
