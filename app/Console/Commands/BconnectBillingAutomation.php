<?php

namespace App\Console\Commands;

use App\Mail\BconnectInvoiceMail;
use App\Models\Bconnect\Invoice;
use App\Models\Bconnect\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BconnectBillingAutomation extends Command
{
    protected $signature = 'bconnect:billing-automation';
    protected $description = 'Generate recurring invoices, send payment reminders, roll up remote minutes.';

    public function handle(): int
    {
        $this->recurring();
        $this->reminders();
        return 0;
    }

    private function recurring(): void
    {
        $due = DB::table('bconnect_recurring_invoices')
            ->where('active', true)->where('next_run', '<=', now()->toDateString())->get();

        foreach ($due as $rec) {
            try {
                $inv = Invoice::create([
                    'company_id' => $rec->company_id,
                    'client_id' => $rec->client_id,
                    'invoice_number' => 'BCI-' . strtoupper(uniqid()),
                    'amount' => $rec->amount,
                    'currency' => $rec->currency,
                    'status' => 'pending',
                    'due_at' => now()->addDays(7),
                    'description' => $rec->description ?: 'Recurring charge',
                    'is_subscription' => true,
                    'tax_rate' => $rec->tax_rate,
                ]);
                $next = match ($rec->interval) {
                    'quarterly' => now()->addMonths(3),
                    'yearly' => now()->addYear(),
                    default => now()->addMonth(),
                };
                DB::table('bconnect_recurring_invoices')->where('id', $rec->id)
                    ->update(['next_run' => $next->toDateString(), 'last_invoice_id' => $inv->id, 'updated_at' => now()]);

                $client = Member::with('user')->find($rec->client_id);
                if ($client?->user?->email) {
                    Mail::to($client->user->email)->send(new BconnectInvoiceMail($inv, 'created'));
                }
                $this->info("recurring → invoice {$inv->invoice_number}");
            } catch (\Throwable $e) {
                Log::warning('bconnect recurring invoice failed: ' . $e->getMessage());
            }
        }
    }

    private function reminders(): void
    {
        // Remind at 3 days before due, on due day, and 2 days overdue — once each.
        $invoices = Invoice::where('status', 'pending')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now()->subDays(7), now()->addDays(4)])
            ->with('client.user')->get();

        foreach ($invoices as $inv) {
            $meta = $inv->metadata ?? [];
            $days = now()->diffInDays($inv->due_at, false); // negative = overdue
            $mark = $days < 0 ? 'overdue' : ($days <= 3 ? 'soon' : null);
            if (!$mark || !empty($meta["reminded_$mark"])) continue;

            $client = $inv->client;
            if ($client?->user?->email) {
                try {
                    Mail::to($client->user->email)->send(new BconnectInvoiceMail($inv, $mark === 'overdue' ? 'overdue' : 'reminder'));
                    $meta["reminded_$mark"] = true;
                    $inv->update(['metadata' => $meta]);
                    $this->info("reminder sent for {$inv->invoice_number}");
                } catch (\Throwable $e) {
                    Log::warning('invoice reminder failed: ' . $e->getMessage());
                }
            }
        }
    }
}
