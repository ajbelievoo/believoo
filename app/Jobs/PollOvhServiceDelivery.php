<?php

namespace App\Jobs;

use App\Models\UserHosting;
use App\Services\OvhApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollOvhServiceDelivery implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 30;
    public int $backoff = 60; // seconds

    public function __construct(
        public int $hostingId,
        public string $ovhOrderId,
    ) {}

    public function handle(): void
    {
        $hosting = UserHosting::find($this->hostingId);
        if (!$hosting) {
            Log::warning('PollOvhServiceDelivery: hosting not found', ['hosting_id' => $this->hostingId]);
            return;
        }

        // Stop polling if the order was cancelled / refunded locally.
        if (in_array($hosting->status, ['cancelled', 'terminated', 'suspended'])) {
            return;
        }

        $ovh = new OvhApiService();
        if (!$ovh->isEnabled()) {
            Log::warning('PollOvhServiceDelivery: OVH API not configured', ['hosting_id' => $this->hostingId]);
            $this->release(300);
            return;
        }

        try {
            // 1. Check order status (OVH keeps this in a dedicated sub-route).
            $status = $ovh->get("/me/order/{$this->ovhOrderId}/status") ?? 'unknown';

            Log::info('OVH order status poll', [
                'hosting_id' => $this->hostingId,
                'ovh_order_id' => $this->ovhOrderId,
                'status' => $status,
            ]);

            if ($status === 'delivered') {
                // 2. Find the VPS service created by this order.
                $serviceId = $this->findVpsServiceId($ovh, $this->ovhOrderId);

                if ($serviceId) {
                    $hosting->update([
                        'provider_service_id' => $serviceId,
                        'status' => 'active',
                        'admin_notes' => ($hosting->admin_notes ?? '') . "\nDelivered. Service ID: {$serviceId}",
                    ]);

                    // 3. Fetch VPS details (IP, hostname, etc.).
                    $this->enrichVpsDetails($ovh, $hosting, $serviceId);

                    $user = $hosting->user;
                    if ($user) {
                        $user->notify(new \App\Notifications\VpsServerNotification('vm_created', [
                            'plan' => $hosting->plan_name,
                            'vmid' => $hosting->provider_service_id,
                            'ip'   => $hosting->server_ip,
                        ]));
                    }

                    return;
                }

                // Order delivered but service ID not found yet — retry.
                $this->release(120);
                return;
            }

            if (in_array($status, ['cancelled', 'notPaid', 'expired', 'documentsRequested'])) {
                $hosting->update([
                    'status' => 'failed',
                    'admin_notes' => ($hosting->admin_notes ?? '') . "\nOVH order status: {$status}",
                ]);
                return;
            }

            // Still pending/processing — keep polling with exponential backoff.
            $delay = min(3600, $this->backoff * ($this->attempts() + 1));
            $this->release($delay);
        } catch (\Exception $e) {
            Log::error('PollOvhServiceDelivery failed', [
                'hosting_id' => $this->hostingId,
                'ovh_order_id' => $this->ovhOrderId,
                'error' => $e->getMessage(),
            ]);
            $this->release(300);
        }
    }

    /**
     * Try to find the VPS service name created by an OVH order.
     */
    protected function findVpsServiceId(OvhApiService $ovh, string $ovhOrderId): ?string
    {
        try {
            $details = $ovh->get("/me/order/{$ovhOrderId}/details");

            foreach ($details as $detailId) {
                $detailId = is_array($detailId) ? ($detailId['detailId'] ?? null) : $detailId;
                if (!$detailId) {
                    continue;
                }

                $detailInfo = $ovh->get("/me/order/{$ovhOrderId}/details/{$detailId}");
                $domain = $detailInfo['domain'] ?? null;

                // VPS order lines expose the service name as a vps-xxxx.vps.ovh.<tld> domain.
                // Option/backup lines append suffixes like "-linux" or "-autobackup".
                // Other products use placeholders like "*001.001".
                if ($domain && preg_match('/^vps-[a-z0-9]+\.vps\.ovh\.[a-z]+$/', $domain)) {
                    return $domain;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not find OVH service ID from order details', [
                'ovh_order_id' => $ovhOrderId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Fetch VPS details from OVH and update the local hosting record.
     */
    protected function enrichVpsDetails(OvhApiService $ovh, UserHosting $hosting, string $serviceId): void
    {
        try {
            $vps = $ovh->get("/vps/{$serviceId}");

            $hosting->update([
                'server_ip' => $vps['ip'] ?? null,
                'server_hostname' => $vps['displayName'] ?? $serviceId,
                'datacenter_location' => $vps['location'] ?? null,
            ]);

            // Try to get the IPv4 address if not directly present.
            if (empty($hosting->server_ip)) {
                $ips = $ovh->get("/vps/{$serviceId}/ips");
                if (is_array($ips) && !empty($ips[0])) {
                    $hosting->update(['server_ip' => $ips[0]]);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not enrich VPS details', [
                'service_id' => $serviceId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
