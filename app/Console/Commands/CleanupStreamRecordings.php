<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StreamRecording;
use Illuminate\Support\Facades\Log;

class CleanupStreamRecordings extends Command
{
    protected $signature = 'stream:cleanup-recordings';
    protected $description = 'Auto-delete stream recordings older than 30 days';

    public function handle(): int
    {
        $recordings = StreamRecording::readyToDelete()->get();
        $count = 0;

        foreach ($recordings as $recording) {
            try {
                $recording->update(['status' => 'deleted']);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to auto-delete stream recording', [
                    'recording_id' => $recording->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Auto-deleted {$count} stream recording(s).");
        Log::info("Stream recordings cleanup completed. {$count} recording(s) deleted.");

        return 0;
    }
}
