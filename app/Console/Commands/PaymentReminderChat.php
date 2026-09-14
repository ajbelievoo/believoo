<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\Message;
use App\Models\User;

class PaymentReminderChat extends Command
{
    protected $signature = 'payment:chat-reminder';
    protected $description = 'Send payment due reminders via chat widget to logged-in clients';

    public function handle()
    {
        // Find invoices due within 3 days or overdue
        $due = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<=', now()->addDays(3))
            ->where('due_date', '>=', now()->subDays(7))
            ->with('user')
            ->get();

        $sent = 0;
        foreach ($due as $inv) {
            if (!$inv->user) continue;

            // Find the user's chat session (last message from their email)
            $session = Message::where('sender_email', $inv->user->email)
                ->where('type', 'user')
                ->latest()
                ->first();

            if (!$session) continue;

            // Don't spam — only once per day per session
            $already = Message::where('session_id', $session->session_id)
                ->where('type', 'admin')
                ->where('message', 'like', '%invoice%')
                ->whereDate('created_at', today())
                ->exists();
            if ($already) continue;

            $amount = '₹' . number_format($inv->total ?? $inv->amount ?? 0, 0);
            $dueDate = $inv->due_date->format('d M Y');
            $isOverdue = $inv->due_date->isPast();

            $msg = $isOverdue
                ? "⏰ Reminder: Your invoice #{$inv->invoice_number} of {$amount} was due on {$dueDate}. Please pay soon to avoid service interruption. Pay: " . url('/client/invoices')
                : "💳 Upcoming payment: Invoice #{$inv->invoice_number} of {$amount} is due on {$dueDate}. Pay: " . url('/client/invoices');

            Message::create([
                'session_id' => $session->session_id,
                'sender_name' => 'Believoo Billing',
                'sender_email' => 'billing@believoo.com',
                'message' => $msg,
                'type' => 'admin',
                'is_read' => false,
            ]);

            $sent++;
        }

        $this->info("Payment reminders sent: {$sent}");
    }
}
