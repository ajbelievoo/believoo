<?php

namespace App\Services;

use App\Models\Bconnect\BconnectSlaPolicy;
use App\Models\Bconnect\Ticket;
use Carbon\Carbon;

class BconnectSlaTracker
{
    public static function policyForTicket(Ticket $ticket): ?BconnectSlaPolicy
    {
        if ($ticket->sla_policy_id) {
            return BconnectSlaPolicy::find($ticket->sla_policy_id);
        }

        return BconnectSlaPolicy::where('company_id', $ticket->company_id)
            ->where('active', true)
            ->where(function ($q) use ($ticket) {
                $q->where('applies_to', 'all')
                  ->orWhere(function ($q2) use ($ticket) {
                      $conditions = $q2->getQuery()->from === 'bconnect_sla_policies' ? [] : [];
                      // Simplistic matching: priority based conditions stored in JSON
                  });
            })
            ->orderByDesc('created_at')
            ->first();
    }

    public static function recordFirstResponse(Ticket $ticket): void
    {
        if (!$ticket->first_response_at && $ticket->comments()->count() > 0) {
            $ticket->update(['first_response_at' => now()]);
        }
    }

    public static function checkBreaches(): void
    {
        $tickets = Ticket::whereIn('status', ['open', 'in_progress', 'testing'])
            ->where('sla_breached', false)
            ->get();

        foreach ($tickets as $ticket) {
            $policy = self::policyForTicket($ticket);
            if (!$policy) continue;

            $now = now();
            $breached = false;

            // Resolution SLA
            if ($ticket->created_at->diffInSeconds($now) > $policy->resolution_time_seconds) {
                $breached = true;
            }

            // Response SLA
            if (!$ticket->first_response_at && $ticket->created_at->diffInSeconds($now) > $policy->response_time_seconds) {
                $breached = true;
            }

            if ($breached) {
                $ticket->update(['sla_breached' => true]);
                BconnectNotificationService::sendToCompanyAdmins($ticket->company_id, 'sla', 'SLA breach', 'Ticket #' . $ticket->id . ' ' . $ticket->title . ' has breached SLA policy ' . $policy->name, route('bconnect.tickets.show', $ticket->id));
            }
        }
    }
}
