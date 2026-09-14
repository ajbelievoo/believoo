<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\UserHosting;
use App\Models\User;
use App\Models\VpsPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OvhProvisioningService
{
    /**
     * Check if an order should be provisioned via OVH instead of local Proxmox.
     */
    public function isOvhOrder(Order $order): bool
    {
        $metadata = $order->metadata ?? [];
        $vpsPlanId = $metadata['vps_plan_id'] ?? null;

        if ($vpsPlanId) {
            $plan = VpsPlan::find($vpsPlanId);
            if ($plan && !empty($plan->ovh_plan_code)) {
                return true;
            }
        }

        // Also check if the tier name maps to an OVH-linked plan.
        $tierName = trim($order->tier_name ?? '');
        if ($tierName) {
            $plan = VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$tierName])
                ->orWhereRaw('LOWER(slug) = LOWER(?)', [$tierName])
                ->first();
            if ($plan && !empty($plan->ovh_plan_code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Provision an OVH-based order after user payment is confirmed.
     */
    public function provisionFromOrder(Order $order): ?UserHosting
    {
        $metadata = $order->metadata ?? [];
        $vpsPlanId = $metadata['vps_plan_id'] ?? null;
        $plan = $vpsPlanId ? VpsPlan::find($vpsPlanId) : null;

        if (!$plan) {
            $plan = $this->resolvePlanFromTier($order->tier_name);
        }

        if (!$plan || empty($plan->ovh_plan_code)) {
            Log::error('OVH provisioning failed: plan not found or missing ovh_plan_code', [
                'order_id' => $order->id,
                'tier_name' => $order->tier_name,
            ]);
            return null;
        }

        $ovhService = new OvhApiService();
        if (!$ovhService->isEnabled()) {
            Log::error('OVH provisioning failed: OVH API not configured', ['order_id' => $order->id]);
            return null;
        }

        // Check OVH wallet balance before placing order.
        $balance = $ovhService->getAccountBalance();
        if (($balance['balance'] ?? 0) <= 0) {
            Log::error('OVH provisioning failed: insufficient OVH account balance', [
                'order_id' => $order->id,
                'balance'  => $balance,
            ]);
            return null;
        }

        try {
            return DB::transaction(function () use ($order, $plan, $ovhService, $metadata) {
                // Idempotency check.
                $existing = UserHosting::where('order_id', $order->id)->first();
                if ($existing) {
                    Log::info('OVH provisioning skipped: hosting already exists', [
                        'order_id' => $order->id,
                        'hosting_id' => $existing->id,
                    ]);
                    return $existing;
                }

                $billingMonths = (int) ($order->billing_months ?? 1);
                $duration = $this->monthsToIsoDuration($billingMonths);
                $quantity = (int) ($metadata['quantity'] ?? 1);

                // Place order with OVH.
                $ovhOrder = $ovhService->orderVps($plan->ovh_plan_code, $quantity, $duration);

                $ovhOrderId = $ovhOrder->orderId;
                $ovhCost = $ovhOrder->getTotalWithTax();

                Log::info('OVH order placed', [
                    'order_id' => $order->id,
                    'ovh_order_id' => $ovhOrderId,
                    'ovh_cost' => $ovhCost,
                    'plan_code' => $plan->ovh_plan_code,
                ]);

                // Create local hosting record in provisioning state.
                $hosting = UserHosting::create([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'service_id' => $order->service_id,
                    'hosting_type' => 'ovh_vps',
                    'plan_name' => $plan->display_name ?? $plan->name,
                    'status' => 'provisioning',
                    'price' => $order->amount,
                    'billing_cycle' => $this->billingCycleFromMonths($billingMonths),
                    'start_date' => now(),
                    'expiry_date' => now()->addMonths($billingMonths),
                    'cpu_cores' => $plan->cpu_cores,
                    'ram_size' => $plan->memory_gb,
                    'storage_size' => $plan->disk_gb,
                    'os_name' => $metadata['os'] ?? 'Ubuntu 22.04',
                    'root_password' => $this->generateSecurePassword(),
                    'admin_notes' => "OVH order placed: {$ovhOrderId}. Plan code: {$plan->ovh_plan_code}. Awaiting service delivery.",
                    'provider_name' => 'ovh',
                    'provider_order_id' => $ovhOrderId,
                    'provider_metadata' => array_merge($plan->ovh_config ?? [], [
                        'ovh_order_id' => $ovhOrderId,
                        'ovh_order_url' => $ovhOrder->url,
                        'ovh_cost' => $ovhCost,
                    ]),
                ]);

                $this->createInvoiceFromOrder($order, $hosting);

                $user = User::find($order->user_id);
                if ($user) {
                    $user->notify(new \App\Notifications\VpsServerNotification('payment_success', [
                        'amount' => number_format($order->amount, 2),
                        'plan' => $hosting->plan_name,
                    ]));
                    $user->notify(new \App\Notifications\VpsServerNotification('vm_provisioning', [
                        'plan' => $hosting->plan_name,
                    ]));
                }

                // Dispatch a job to poll OVH until the service is delivered and fetch its IP.
                \App\Jobs\PollOvhServiceDelivery::dispatch($hosting->id, $ovhOrderId)
                    ->delay(now()->addSeconds(30));

                return $hosting;
            });
        } catch (\Exception $e) {
            Log::error('OVH provisioning failed for order ' . $order->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // Record a failed hosting entry so the admin can see and fix it manually.
            $hosting = UserHosting::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'service_id' => $order->service_id,
                'hosting_type' => 'ovh_vps',
                'plan_name' => $order->tier_name ?: 'OVH VPS',
                'status' => 'failed',
                'price' => $order->amount,
                'billing_cycle' => 'monthly',
                'start_date' => now(),
                'expiry_date' => now(),
                'admin_notes' => 'OVH order failed: ' . $e->getMessage(),
                'provider_name' => 'ovh',
            ]);

            // Notify admins.
            $admins = User::where('is_admin', true)->get();
            if ($admins->isEmpty()) {
                $admins = User::take(3)->get();
            }
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\AdminAlertNotification(
                title: 'OVH Order Failed - Manual Action Required',
                message: "Order #{$order->id} ({$order->tier_name}) was paid by the customer but the OVH order failed: {$e->getMessage()}. Check the hosting record #{$hosting->id}.",
                actionUrl: url('/admin/user-hostings/' . $hosting->id),
                actionLabel: 'View Hosting'
            ));

            return null;
        }
    }

    /**
     * Try to find a VpsPlan from the order tier name.
     */
    protected function resolvePlanFromTier(?string $tierName): ?VpsPlan
    {
        if (empty($tierName)) {
            return null;
        }
        return VpsPlan::whereRaw('LOWER(name) = LOWER(?)', [$tierName])
            ->orWhereRaw('LOWER(slug) = LOWER(?)', [$tierName])
            ->first();
    }

    /**
     * Convert number of months to an OVH ISO 8601 duration.
     */
    protected function monthsToIsoDuration(int $months): string
    {
        return match ($months) {
            1 => 'P1M',
            3 => 'P3M',
            6 => 'P6M',
            12 => 'P1Y',
            24 => 'P2Y',
            default => 'P1M',
        };
    }

    /**
     * Get billing cycle label from months.
     */
    protected function billingCycleFromMonths(int $months): string
    {
        return match ($months) {
            1 => 'monthly',
            3 => 'quarterly',
            6 => 'half_yearly',
            12 => 'yearly',
            default => 'monthly',
        };
    }

    /**
     * Generate a secure random root password.
     */
    protected function generateSecurePassword(int $length = 16): string
    {
        return substr(str_shuffle(
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=[]{}|;:,.<>?'
        ), 0, $length);
    }

    /**
     * Create a local invoice for the order.
     */
    protected function createInvoiceFromOrder(Order $order, UserHosting $hosting): ?Invoice
    {
        try {
            return Invoice::create([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'invoice_type' => 'hosting',
                'description' => $hosting->plan_name . ' - OVH VPS',
                'amount' => $order->amount,
                'tax_amount' => $order->gst_amount ?? 0,
                'total_amount' => $order->amount,
                'currency' => $order->currency,
                'status' => 'paid',
                'payment_gateway' => $order->payment_gateway,
                'payment_id' => $order->payment_id,
                'paid_at' => $order->paid_at,
                'due_date' => $order->paid_at?->addDays(7),
                'line_items' => [
                    [
                        'item' => $hosting->plan_name,
                        'description' => 'OVH VPS - ' . $hosting->billing_cycle,
                        'quantity' => 1,
                        'price' => $order->amount,
                        'total' => $order->amount,
                    ]
                ],
                'notes' => 'Auto-generated from OVH order #' . ($hosting->provider_order_id ?? 'unknown'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create OVH invoice from order: ' . $e->getMessage());
            return null;
        }
    }
}
