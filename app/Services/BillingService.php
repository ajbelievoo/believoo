<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BillingService
{
    protected VirtualizorApiService $virtualizor;

    public function __construct(VirtualizorApiService $virtualizor)
    {
        $this->virtualizor = $virtualizor;
    }

    /**
     * Suspend all VPS for a user
     */
    public function suspendUserServers(User $user): array
    {
        $vpsIds = $user->getVpsIdsArray();

        if (empty($vpsIds)) {
            return ['success' => false, 'message' => 'No VPS IDs found for user'];
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($vpsIds as $vpsId) {
            try {
                Log::info("Billing: Suspending VPS {$vpsId} for user {$user->id}");

                // Call Virtualizor API to suspend
                $result = $this->virtualizor->suspendVps($vpsId);

                if ($result) {
                    $successCount++;
                    Log::info("Billing: VPS {$vpsId} suspended successfully");
                } else {
                    $failCount++;
                    $errors[] = "VPS {$vpsId}: API returned false";
                    Log::warning("Billing: VPS {$vpsId} suspension returned false");
                }
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = "VPS {$vpsId}: {$e->getMessage()}";
                Log::error("Billing: Exception suspending VPS {$vpsId}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Update user billing status
        if ($successCount > 0) {
            $user->update(['billing_status' => 'suspended']);

            // Create invoice if not exists for this period
            $this->createOverdueInvoice($user);
        }

        return [
            'success' => $successCount > 0,
            'suspended_count' => $successCount,
            'failed_count' => $failCount,
            'message' => $failCount > 0 ? implode(', ', $errors) : 'All servers suspended successfully',
        ];
    }

    /**
     * Unsuspend all VPS for a user (called after payment)
     */
    public function unsuspendUserServers(User $user): array
    {
        $vpsIds = $user->getVpsIdsArray();

        if (empty($vpsIds)) {
            return ['success' => false, 'message' => 'No VPS IDs found for user'];
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($vpsIds as $vpsId) {
            try {
                Log::info("Billing: Unsuspending VPS {$vpsId} for user {$user->id}");

                // Call Virtualizor API to unsuspend
                $result = $this->virtualizor->unsuspendVps($vpsId);

                if ($result) {
                    $successCount++;
                    Log::info("Billing: VPS {$vpsId} unsuspended successfully");
                } else {
                    $failCount++;
                    $errors[] = "VPS {$vpsId}: API returned false";
                    Log::warning("Billing: VPS {$vpsId} unsuspension returned false");
                }
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = "VPS {$vpsId}: {$e->getMessage()}";
                Log::error("Billing: Exception unsuspending VPS {$vpsId}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        // Update user billing status
        if ($successCount > 0) {
            $user->update(['billing_status' => 'active']);
        }

        return [
            'success' => $successCount > 0,
            'unsuspended_count' => $successCount,
            'failed_count' => $failCount,
            'message' => $failCount > 0 ? implode(', ', $errors) : 'All servers unsuspended successfully',
        ];
    }

    /**
     * Process payment and unsuspend servers
     */
    public function processPayment(User $user, float $amount, string $paymentMethod, ?string $transactionId = null): array
    {
        try {
            DB::beginTransaction();

            // Update user payment info
            $newDueDate = Carbon::now()->addMonth();
            $user->update([
                'next_due_date' => $newDueDate,
                'last_payment_date' => Carbon::now(),
                'billing_status' => 'active',
            ]);

            // Create paid invoice
            $invoice = Invoice::create([
                'user_id' => $user->id,
                'invoice_number' => $this->generateInvoiceNumber(),
                'order_id' => 'SUB-' . strtoupper(uniqid()),
                'service_description' => 'VPS Hosting - ' . ($user->current_plan_name ?? 'Standard Plan'),
                'plan_name' => $user->current_plan_name ?? 'Standard Plan',
                'amount' => $amount,
                'invoice_date' => Carbon::now(),
                'due_date' => $newDueDate,
                'paid_date' => Carbon::now(),
                'status' => 'paid',
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
                'notes' => 'Auto-generated subscription payment',
            ]);

            // Unsuspend servers
            $unsuspendResult = $this->unsuspendUserServers($user);

            DB::commit();

            Log::info("Billing: Payment processed successfully for user {$user->id}", [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'invoice' => $invoice,
                'unsuspend_result' => $unsuspendResult,
                'next_due_date' => $newDueDate,
                'message' => 'Payment processed successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Billing: Payment processing failed for user {$user->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create overdue invoice for suspended user
     */
    public function createOverdueInvoice(User $user): ?Invoice
    {
        // Check if invoice already exists for current period
        $existingInvoice = Invoice::where('user_id', $user->id)
            ->where('service_description', 'like', '%VPS Hosting%')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->first();

        if ($existingInvoice) {
            return $existingInvoice;
        }

        return Invoice::create([
            'user_id' => $user->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'order_id' => 'SUSP-' . strtoupper(uniqid()),
            'service_description' => 'VPS Hosting - ' . ($user->current_plan_name ?? 'Standard Plan') . ' (OVERDUE)',
            'plan_name' => $user->current_plan_name ?? 'Standard Plan',
            'amount' => $user->plan_price ?? 0,
            'invoice_date' => Carbon::now(),
            'due_date' => $user->next_due_date,
            'status' => 'overdue',
            'notes' => 'Auto-generated due to payment overdue',
        ]);
    }

    /**
     * Get days remaining until next payment
     */
    public function getDaysRemaining(User $user): ?int
    {
        if (!$user->next_due_date) {
            return null;
        }

        return Carbon::now()->diffInDays($user->next_due_date, false);
    }

    /**
     * Check if user is overdue
     */
    public function isOverdue(User $user, int $gracePeriodDays = 2): bool
    {
        if (!$user->next_due_date) {
            return false;
        }

        $threshold = Carbon::now()->subDays($gracePeriodDays);
        return $user->next_due_date < $threshold->toDateString();
    }

    /**
     * Generate upcoming invoices for users with due dates approaching
     */
    public static function generateUpcomingInvoices(): void
    {
        $billingService = app(self::class);
        $threeDaysFromNow = Carbon::now()->addDays(3);

        $users = User::whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', $threeDaysFromNow)
            ->whereDate('next_due_date', '>=', Carbon::now())
            ->where('billing_status', 'active')
            ->get();

        Log::info("Billing: Generating upcoming invoices for {$users->count()} users");

        foreach ($users as $user) {
            try {
                // Check if invoice already exists for this period
                $existingInvoice = Invoice::where('user_id', $user->id)
                    ->whereDate('due_date', $user->next_due_date)
                    ->first();

                if (!$existingInvoice) {
                    Invoice::create([
                        'user_id' => $user->id,
                        'invoice_number' => $billingService->generateInvoiceNumber(),
                        'order_id' => 'REN-' . strtoupper(uniqid()),
                        'service_description' => 'VPS Hosting - ' . ($user->current_plan_name ?? 'Standard Plan') . ' (Renewal)',
                        'plan_name' => $user->current_plan_name ?? 'Standard Plan',
                        'amount' => $user->plan_price ?? 0,
                        'invoice_date' => Carbon::now(),
                        'due_date' => $user->next_due_date,
                        'status' => 'pending',
                        'notes' => 'Auto-generated renewal invoice',
                    ]);

                    Log::info("Billing: Created upcoming invoice for user {$user->id}");
                }
            } catch (\Exception $e) {
                Log::error("Billing: Failed to generate upcoming invoice for user {$user->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Generate unique invoice number
     */
    protected function generateInvoiceNumber(): string
    {
        return 'BEL-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
}
