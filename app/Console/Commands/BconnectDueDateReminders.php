<?php

namespace App\Console\Commands;

use App\Models\Bconnect\Sprint;
use App\Models\Bconnect\Ticket;
use App\Services\BconnectNotificationService;
use Illuminate\Console\Command;

class BconnectDueDateReminders extends Command
{
    protected $signature = 'bconnect:due-date-reminders';
    protected $description = 'Send B-Connect notifications for tickets and sprints nearing due dates.';

    public function handle(): int
    {
        $now = now();

        // Ticket due reminders (24h and overdue)
        $tickets = Ticket::whereNotNull('due_date')
            ->whereIn('status', ['open', 'in_progress', 'testing'])
            ->where(function ($q) use ($now) {
                $q->whereDate('due_date', $now->copy()->addDay()->toDateString())
                  ->orWhereDate('due_date', '<', $now->toDateString());
            })
            ->with(['assignee', 'project'])
            ->get();

        foreach ($tickets as $ticket) {
            $url = route('bconnect.tickets.show', $ticket->id);
            $isOverdue = $ticket->due_date->startOfDay()->lt($now->startOfDay());
            $title = $isOverdue ? 'Ticket overdue' : 'Ticket due soon';
            $message = $ticket->title . ($isOverdue ? ' was due on ' : ' is due on ') . $ticket->due_date->format('M d, Y');

            if ($ticket->assignee) {
                BconnectNotificationService::send($ticket->assignee, 'due_date', $title, $message, $url, $ticket->company_id);
            }
            BconnectNotificationService::sendToCompanyAdmins($ticket->company_id, 'due_date', $title, $message, $url);
        }

        // Sprint reminders (ending in 24h or overdue)
        $sprints = Sprint::whereNotNull('end_date')
            ->whereDate('end_date', '<=', $now->copy()->addDay()->toDateString())
            ->where('status', '!=', 'completed')
            ->with('project')
            ->get();

        foreach ($sprints as $sprint) {
            $isOverdue = $sprint->end_date->startOfDay()->lt($now->startOfDay());
            $title = $isOverdue ? 'Sprint ended' : 'Sprint ending soon';
            $message = $sprint->name . ' ends on ' . $sprint->end_date->format('M d, Y');
            BconnectNotificationService::sendToCompanyAdmins($sprint->company_id, 'sprint', $title, $message, route('bconnect.sprints.show', $sprint->id));
        }

        $this->info('B-Connect due date reminders sent.');
        return 0;
    }
}
