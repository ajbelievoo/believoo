<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AnnouncementBroadcastService;
use Illuminate\Console\Command;

class SendScheduledAnnouncements extends Command
{
    protected $signature = 'announcement:send-scheduled';
    protected $description = 'Send scheduled announcements that are due';

    public function handle()
    {
        $service = new AnnouncementBroadcastService();

        Announcement::whereNull('sent_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->where('is_published', true)
            ->chunk(10, function ($announcements) use ($service) {
                foreach ($announcements as $announcement) {
                    try {
                        $service->send($announcement);
                        $this->info('Sent: ' . $announcement->title);
                    } catch (\Throwable $e) {
                        $this->error('Failed: ' . $announcement->title . ' - ' . $e->getMessage());
                    }
                }
            });

        return Command::SUCCESS;
    }
}
