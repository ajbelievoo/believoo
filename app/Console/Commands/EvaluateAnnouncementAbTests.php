<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AnnouncementBroadcastService;
use Illuminate\Console\Command;

class EvaluateAnnouncementAbTests extends Command
{
    protected $signature = 'announcement:evaluate-ab';
    protected $description = 'Evaluate active A/B tests and send the winning variant to reserve groups';

    public function handle()
    {
        $tests = Announcement::where('ab_enabled', true)
            ->where('ab_status', 'testing')
            ->whereNotNull('ab_test_started_at')
            ->get()
            ->filter(fn ($a) => $a->abDurationIsOver());

        if ($tests->isEmpty()) {
            $this->info('No A/B tests ready for evaluation.');
            return Command::SUCCESS;
        }

        $service = new AnnouncementBroadcastService();

        foreach ($tests as $announcement) {
            $winner = $this->determineWinner($announcement);

            $this->info("Announcement #{$announcement->id}: winner is variant {$winner}");

            $service->sendWinner($announcement, $winner);
        }

        return Command::SUCCESS;
    }

    private function determineWinner(Announcement $announcement): string
    {
        $metric = $announcement->ab_metric;

        $a = $announcement->recipients()->where('variant', 'A')->where('is_test', true);
        $b = $announcement->recipients()->where('variant', 'B')->where('is_test', true);

        $aSent = (clone $a)->whereNotNull('sent_at')->count();
        $bSent = (clone $b)->whereNotNull('sent_at')->count();

        if ($aSent === 0 && $bSent === 0) {
            return 'A';
        }

        if ($aSent === 0) return 'B';
        if ($bSent === 0) return 'A';

        if ($metric === 'clicks') {
            $aEvents = (clone $a)->whereNotNull('clicked_at')->count();
            $bEvents = (clone $b)->whereNotNull('clicked_at')->count();
            $aRate = $aEvents / $aSent;
            $bRate = $bEvents / $bSent;

            if ($aRate === $bRate) {
                $aOpens = (clone $a)->whereNotNull('opened_at')->count();
                $bOpens = (clone $b)->whereNotNull('opened_at')->count();
                $aOpenRate = $aOpens / $aSent;
                $bOpenRate = $bOpens / $bSent;

                if ($aOpenRate === $bOpenRate) {
                    return $aSent >= $bSent ? 'A' : 'B';
                }

                return $aOpenRate > $bOpenRate ? 'A' : 'B';
            }

            return $aRate > $bRate ? 'A' : 'B';
        }

        $aOpens = (clone $a)->whereNotNull('opened_at')->count();
        $bOpens = (clone $b)->whereNotNull('opened_at')->count();
        $aRate = $aOpens / $aSent;
        $bRate = $bOpens / $bSent;

        if ($aRate === $bRate) {
            $aClicks = (clone $a)->whereNotNull('clicked_at')->count();
            $bClicks = (clone $b)->whereNotNull('clicked_at')->count();
            $aClickRate = $aClicks / $aSent;
            $bClickRate = $bClicks / $bSent;

            if ($aClickRate === $bClickRate) {
                return $aSent >= $bSent ? 'A' : 'B';
            }

            return $aClickRate > $bClickRate ? 'A' : 'B';
        }

        return $aRate > $bRate ? 'A' : 'B';
    }
}
