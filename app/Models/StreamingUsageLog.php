<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StreamingUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'streaming_api_key_id',
        'stream_id',
        'channel_name',
        'stream_type',
        'viewer_count',
        'bandwidth_used_mb',
        'duration_minutes',
        'avg_bitrate_kbps',
        'resolution',
        'codec',
        'country_code',
        'region',
        'started_at',
        'ended_at',
        'end_reason',
        'metadata',
        'user_agent_hash',
    ];

    protected $casts = [
        'user_id'                => 'integer',
        'streaming_api_key_id'   => 'integer',
        'viewer_count'           => 'integer',
        'bandwidth_used_mb'      => 'decimal:2',
        'duration_minutes'       => 'integer',
        'avg_bitrate_kbps'       => 'integer',
        'started_at'             => 'datetime',
        'ended_at'               => 'datetime',
        'metadata'               => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(StreamingApiKey::class, 'streaming_api_key_id');
    }
}
