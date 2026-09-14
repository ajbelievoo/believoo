<?php

namespace App\Jobs;

use App\Models\StreamingUsageLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordStreamingUsage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int   $userId,
        public readonly int   $streamingProjectId,
        public readonly float $bandwidthUsedGb,
        public readonly int   $peakConcurrentViewers,
        public readonly int   $activeStreamMinutes,
    ) {}

    public function handle(): void
    {
        StreamingUsageLog::create([
            'user_id'                 => $this->userId,
            'streaming_project_id'    => $this->streamingProjectId,
            'bandwidth_used_gb'       => $this->bandwidthUsedGb,
            'peak_concurrent_viewers' => $this->peakConcurrentViewers,
            'active_stream_minutes'   => $this->activeStreamMinutes,
            'recorded_at'             => now(),
        ]);
    }
}
