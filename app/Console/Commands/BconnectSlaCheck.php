<?php

namespace App\Console\Commands;

use App\Services\BconnectSlaTracker;
use Illuminate\Console\Command;

class BconnectSlaCheck extends Command
{
    protected $signature = 'bconnect:sla-check';
    protected $description = 'Evaluate Bmydesk SLA breaches and notify admins.';

    public function handle(): int
    {
        BconnectSlaTracker::checkBreaches();
        $this->info('Bmydesk SLA check complete.');
        return 0;
    }
}
