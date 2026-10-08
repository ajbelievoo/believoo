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

            $category = $this->resolveCategory($hosting);

            Log::info('OVH order status poll', [
                'hosting_id' => $this->hostingId,
                'ovh_order_id' => $this->ovhOrderId,
                'status' => $status,
                'category' => $category,
            ]);

            if ($status === 'delivered') {
                // 2. Find the service created by this order.
                $serviceId = $this->findServiceId($ovh, $this->ovhOrderId, $category, $hosting);

                if ($serviceId) {
                    $hosting->update([
                        'provider_service_id' => $serviceId,
                        'status' => 'active',
                        'admin_notes' => ($hosting->admin_notes ?? '') . "\nDelivered. Service ID: {$serviceId}",
                    ]);

                    // 3. Fetch service details (IP, hostname, etc.).
                    $this->enrichServiceDetails($ovh, $hosting, $serviceId, $category);

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
            // SLA alert: notify admins once if delivery is still pending after ~10 attempts
            if ($this->attempts() === 10) {
                try {
                    app(\App\Services\AlertService::class)->sendCriticalAlert(
                        'provisioning_sla',
                        "OVH order {$this->ovhOrderId} still pending after extended polling",
                        [
                            'Hosting ID' => $this->hostingId,
                            'OVH Order'  => $this->ovhOrderId,
                            'Status'     => $status,
                            'Attempts'   => $this->attempts(),
                        ]
                    );
                } catch (\Throwable $e) {
                    Log::warning('Provisioning SLA alert failed', ['error' => $e->getMessage()]);
                }
            }
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
     * Resolve the OVH product category from the hosting record.
     */
    protected function resolveCategory(UserHosting $hosting): string
    {
        $metadata = $hosting->provider_metadata ?? [];
        if (!empty($metadata['category'])) {
            return $metadata['category'];
        }

        return match ($hosting->hosting_type) {
            'ovh_dedicated' => 'DEDICATED',
            'ovh_web_hosting' => 'WEB_HOSTING',
            'ovh_public_cloud' => 'PUBLIC_CLOUD',
            'ovh_private_cloud' => 'PRIVATE_CLOUD',
            'ovh_domains' => 'DOMAINS',
            default => 'VPS',
        };
    }

    /**
     * Try to find the service name created by an OVH order.
     */
    protected function findServiceId(OvhApiService $ovh, string $ovhOrderId, string $category, UserHosting $hosting): ?string
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

                if (!$domain || str_contains($domain, '*')) {
                    continue;
                }

                // VPS order lines expose the service name as a vps-xxxx.vps.ovh.<tld> domain.
                // Option/backup lines append suffixes like "-linux" or "-autobackup".
                if ($category === 'VPS' && preg_match('/^vps-[a-z0-9]+\.vps\.ovh\.[a-z]+$/', $domain)) {
                    return $domain;
                }

                // Domain orders: the detail domain is the domain name itself.
                if (in_array(strtoupper($category), ['DOMAINS', 'DOMAIN'])) {
                    return $domain;
                }

                // For dedicated / web hosting, the service domain is a real hostname.
                // Ignore option/addon lines that append known suffixes.
                if (in_array(strtoupper($category), ['DEDICATED', 'WEB_HOSTING'])) {
                    $suffixes = ['-linux', '-windows', '-autobackup', '-backup', '-license', '-option', '-ftpbackup'];
                    $hasSuffix = false;
                    foreach ($suffixes as $suffix) {
                        if (str_ends_with(strtolower($domain), $suffix)) {
                            $hasSuffix = true;
                            break;
                        }
                    }
                    if (!$hasSuffix && preg_match('/^[a-z0-9][a-z0-9\-\.]*\.[a-z]{2,}$/i', $domain)) {
                        return $domain;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Could not find OVH service ID from order details', [
                'ovh_order_id' => $ovhOrderId,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Fetch service details from OVH and update the local hosting record.
     */
    protected function enrichServiceDetails(OvhApiService $ovh, UserHosting $hosting, string $serviceId, string $category): void
    {
        try {
            $category = strtoupper($category);

            if ($category === 'VPS') {
                $vps = $ovh->get("/vps/{$serviceId}");
                $hosting->update([
                    'server_ip' => $vps['ip'] ?? null,
                    'server_hostname' => $vps['displayName'] ?? $serviceId,
                    'datacenter_location' => $vps['location'] ?? null,
                ]);

                if (empty($hosting->server_ip)) {
                    $ips = $ovh->get("/vps/{$serviceId}/ips");
                    if (is_array($ips) && !empty($ips[0])) {
                        $hosting->update(['server_ip' => $ips[0]]);
                    }
                }
                return;
            }

            if ($category === 'DEDICATED') {
                $server = $ovh->get("/dedicated/server/{$serviceId}");
                $hosting->update([
                    'server_ip' => $server['ip'] ?? ($server['ips'] ?? [null])[0] ?? null,
                    'server_hostname' => $server['displayName'] ?? ($server['name'] ?? $serviceId),
                    'datacenter_location' => $server['datacenter'] ?? null,
                ]);
                return;
            }

            if ($category === 'WEB_HOSTING') {
                $web = $ovh->get("/hosting/web/{$serviceId}");
                $hosting->update([
                    'server_hostname' => $web['serviceName'] ?? $serviceId,
                    'datacenter_location' => $web['datacenter'] ?? null,
                ]);
                return;
            }

            if (in_array($category, ['DOMAINS', 'DOMAIN'])) {
                $domain = $ovh->get("/domain/{$serviceId}");
                $hosting->update([
                    'primary_domain' => $serviceId,
                    'server_hostname' => $domain['name'] ?? $serviceId,
                    'status' => 'active',
                ]);
                return;
            }

            // Public/Private Cloud do not expose a simple domain endpoint.
            $hosting->update([
                'provider_service_id' => $serviceId,
                'status' => 'active',
            ]);
        } catch (\Exception $e) {
            Log::warning('Could not enrich OVH service details', [
                'service_id' => $serviceId,
                'category' => $category,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
