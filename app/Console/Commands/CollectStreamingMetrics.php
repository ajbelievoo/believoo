<?php

namespace App\Console\Commands;

use App\Services\StreamingMetricsCollector;
use Illuminate\Console\Command;

/**
 * Collect streaming metrics from SRS/MediaMTX servers
 * 
 * Run via cron every minute:
 * * * * * * cd /path && php artisan streaming:collect-metrics >> /dev/null 2>&1
 */
class CollectStreamingMetrics extends Command
{
    protected $signature = 'streaming:collect-metrics 
                            {--key= : Collect metrics for specific API key ID}
                            {--details : Show detailed output per key}';

    protected $description = 'Collect streaming usage metrics from streaming servers';

    public function handle(): int
    {
        $this->info('🎥 BelieVoo Streaming Metrics Collector');
        $this->info('=========================================');

        $collector = app(StreamingMetricsCollector::class);

        if ($this->option('key')) {
            // Collect for specific key
            $apiKey = \App\Models\StreamingApiKey::find($this->option('key'));
            if (!$apiKey) {
                $this->error("API key not found: {$this->option('key')}");
                return 1;
            }

            $this->info("Collecting metrics for: {$apiKey->app_id}");
            $metrics = $collector->collectForKey($apiKey);

            if ($metrics) {
                $this->displayMetrics($metrics);
            } else {
                $this->warn('No metrics returned - stream may be offline');
            }
        } else {
            // Collect for all active keys
            $results = $collector->collectAll();

            $this->info("Processed: {$results['processed']} keys");
            $this->info("Updated: {$results['updated']} keys");

            if ($results['errors'] > 0) {
                $this->warn("Errors: {$results['errors']} keys");
            }

            // Show detailed output if requested
            if ($this->option('details')) {
                $this->newLine();
                $this->info('Details:');
                $activeKeys = \App\Models\StreamingApiKey::where('status', 'active')->get();
                foreach ($activeKeys as $key) {
                    $metrics = $collector->collectForKey($key);
                    if ($metrics) {
                        $this->info("  {$key->app_id}: " . $metrics['concurrent_viewers'] . ' viewers, ' . number_format($metrics['bandwidth_kbps'], 0) . ' kbps');
                    }
                }
            }
        }

        $this->info('Done!');
        return 0;
    }

    private function displayMetrics(array $metrics): void
    {
        $this->newLine();
        $this->info('Metrics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Server Type', $metrics['server_type']],
                ['Bandwidth', number_format($metrics['bandwidth_kbps'], 2) . ' kbps'],
                ['Bandwidth (GB/hr)', number_format($metrics['bandwidth_gb_per_hour'], 4)],
                ['Concurrent Viewers', $metrics['concurrent_viewers']],
                ['Active Streams', $metrics['active_streams']],
                ['Stream Names', implode(', ', $metrics['stream_names'])],
            ]
        );
    }
}
