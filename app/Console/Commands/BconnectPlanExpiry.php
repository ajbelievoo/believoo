<?php

namespace App\Console\Commands;

use App\Models\Bconnect\Company;
use App\Models\Bconnect\Notification;
use Illuminate\Console\Command;

class BconnectPlanExpiry extends Command
{
    protected $signature = 'bconnect:plan-expiry';
    protected $description = 'Downgrade Bmydesk companies whose paid plan has expired';

    public function handle()
    {
        $expired = Company::whereNot('plan', 'free')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->get();

        foreach ($expired as $company) {
            $oldPlan = $company->plan;

            $company->update([
                'plan' => 'free',
                'subscription_status' => 'free',
                'plan_expires_at' => null,
                'trial_ends_at' => null,
                'grace_period_until' => null,
                'next_invoice_at' => null,
            ]);

            // Notify the company admin(s)
            $admins = \App\Models\Bconnect\Member::where('company_id', $company->id)
                ->whereIn('role', ['company_admin', 'super_admin'])
                ->where('is_active', true)
                ->get();

            foreach ($admins as $admin) {
                Notification::create([
                    'company_id' => $company->id,
                    'member_id' => $admin->id,
                    'type' => 'billing',
                    'title' => 'Plan expired',
                    'message' => "Your {$oldPlan} plan has expired. Workspace has been downgraded to the Free plan.",
                    'url' => route('bconnect.billing'),
                ]);
            }

            $this->info("Downgraded company {$company->id} ({$company->name}) from {$oldPlan} to free.");
        }

        if ($expired->isEmpty()) {
            $this->info('No expired plans found.');
        }

        return 0;
    }
}
