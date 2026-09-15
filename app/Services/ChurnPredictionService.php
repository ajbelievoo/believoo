<?php

namespace App\Services;

use App\Models\Bconnect\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChurnPredictionService
{
    public function scoreUser(User $user): array
    {
        $score = 0;
        $signals = [];

        // Overdue invoices
        $overdueInvoices = Invoice::where('user_id', $user->id)
            ->where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueInvoices > 0) {
            $points = min($overdueInvoices * 25, 50);
            $score += $points;
            $signals[] = "{$overdueInvoices} overdue invoice(s)";
        }

        // Recent failed or cancelled orders
        $failedOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['failed', 'cancelled', 'refunded'])
            ->where('created_at', '>=', now()->subDays(90))
            ->count();

        if ($failedOrders > 0) {
            $score += min($failedOrders * 15, 30);
            $signals[] = "{$failedOrders} failed/cancelled order(s) in last 90 days";
        }

        // No successful payment in 90 days
        $lastPaid = Order::where('user_id', $user->id)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->value('paid_at');

        if (!$lastPaid || $lastPaid->lt(now()->subDays(90))) {
            $score += 20;
            $signals[] = 'No successful payment in last 90 days';
        }

        // Critical/high open support tickets
        $urgentTickets = DB::table('support_tickets')
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->whereIn('priority', ['critical', 'high'])
            ->count();

        if ($urgentTickets > 0) {
            $score += min($urgentTickets * 10, 20);
            $signals[] = "{$urgentTickets} open critical/high priority ticket(s)";
        }

        // More than 2 open tickets
        $openTickets = DB::table('support_tickets')
            ->where('user_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        if ($openTickets > 2) {
            $score += 15;
            $signals[] = "{$openTickets} open support tickets";
        }

        // Negative or low wallet
        if ($user->wallet_balance !== null && $user->wallet_balance < 0) {
            $score += 15;
            $signals[] = 'Negative wallet balance';
        }

        // Billing status not active
        if ($user->billing_status && $user->billing_status !== 'active') {
            $score += 30;
            $signals[] = 'Billing status is ' . $user->billing_status;
        }

        // Next due date passed
        if ($user->next_due_date && $user->next_due_date < now()) {
            $score += 25;
            $signals[] = 'Next due date passed';
        }

        // Inactive (no record update in 60 days)
        if ($user->updated_at && $user->updated_at->lt(now()->subDays(60))) {
            $score += 10;
            $signals[] = 'No account activity in 60 days';
        }

        $score = min($score, 100);

        return [
            'score' => $score,
            'risk' => $this->riskLabel($score),
            'signals' => $signals,
        ];
    }

    public function scoreBconnectCompany(Company $company): array
    {
        $score = 0;
        $signals = [];

        if ($company->plan_expires_at && $company->plan_expires_at->lt(now())) {
            $score += 60;
            $signals[] = 'Plan expired';
        } elseif ($company->plan_expires_at && $company->plan_expires_at->diffInDays(now()) <= 7) {
            $score += 35;
            $signals[] = 'Plan expires within 7 days';
        }

        if ($company->subscription_status && $company->subscription_status !== 'active') {
            $score += 30;
            $signals[] = 'Subscription status: ' . $company->subscription_status;
        }

        if ($company->trial_ends_at && $company->trial_ends_at->lt(now())) {
            $score += 40;
            $signals[] = 'Trial ended';
        }

        if ($company->grace_period_until && $company->grace_period_until->lt(now()->addDays(3))) {
            $score += 20;
            $signals[] = 'Grace period ending';
        }

        // No recent tickets means low engagement
        $recentTickets = $company->tickets()->where('created_at', '>=', now()->subDays(30))->count();
        if ($recentTickets === 0) {
            $score += 10;
            $signals[] = 'No activity in last 30 days';
        }

        $score = min($score, 100);

        return [
            'score' => $score,
            'risk' => $this->riskLabel($score),
            'signals' => $signals,
        ];
    }

    public function topUserRisks(int $limit = 50): array
    {
        $users = User::orderByDesc('created_at')->limit(500)->get();

        return $users->map(function ($user) {
            $score = $this->scoreUser($user);
            return [
                'user' => $user,
                'score' => $score['score'],
                'risk' => $score['risk'],
                'signals' => $score['signals'],
            ];
        })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->toArray();
    }

    public function topBconnectRisks(int $limit = 50): array
    {
        $companies = Company::where('is_active', true)->limit(500)->get();

        return $companies->map(function ($company) {
            $score = $this->scoreBconnectCompany($company);
            return [
                'company' => $company,
                'score' => $score['score'],
                'risk' => $score['risk'],
                'signals' => $score['signals'],
            ];
        })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->toArray();
    }

    protected function riskLabel(int $score): string
    {
        if ($score >= 70) return 'high';
        if ($score >= 40) return 'medium';
        return 'low';
    }
}
