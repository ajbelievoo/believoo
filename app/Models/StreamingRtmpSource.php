<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingRtmpSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'name',
        'stream_key',
        'server_ip',
        'source_type',
        'is_active',
        'notes',
        'current_viewers',
        'bitrate_kbps',
        'resolution',
        'fps',
        'started_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_viewers' => 'integer',
        'bitrate_kbps' => 'integer',
        'fps' => 'integer',
        'started_at' => 'datetime',
    ];

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(StreamingApiKey::class, 'api_key_id');
    }

    public function getRtmpUrlAttribute(): string
    {
        return "rtmp://{$this->server_ip}/live/{$this->stream_key}";
    }

    public function getStreamUrlAttribute(): string
    {
        return "https://{$this->server_ip}/live/{$this->stream_key}.m3u8";
    }

    protected static function booted(): void
    {
        static::creating(function ($source) {
            if (empty($source->stream_key)) {
                $source->stream_key = bin2hex(random_bytes(16));
            }
        });
    }
}
