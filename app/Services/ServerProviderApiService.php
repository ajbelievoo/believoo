<?php

namespace App\Services;

use App\Models\ProxmoxNode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Just-In-Time (JIT) On-Demand IP Allocation Service
 *
 * Triggers on payment: calls the server provider's API to purchase a fresh
 * add-on IPv4 for our dedicated server cluster, then returns it for binding
 * to the Proxmox VM. Zero upfront cost — IPs are bought only after client pays.
 */
class ServerProviderApiService
{
    /**
     * Acquire an IP address on-demand for a given Proxmox node.
     *
     * @param ProxmoxNode $node The node (must have provider_* fields set)
     * @return JitIpResult|null  Null if acquisition failed or JIT not enabled
     */
    public function acquireIp(ProxmoxNode $node): ?JitIpResult
    {
        if (!$node->supportsJitIp()) {
            Log::info('JIT IP skipped: node not configured for JIT', ['node' => $node->name]);
            return null;
        }

        $adapter = $this->resolveAdapter($node);

        if (!$adapter) {
            Log::error('JIT IP failed: no adapter for provider', [
                'node'     => $node->name,
                'provider' => $node->provider_name,
            ]);
            return null;
        }

        try {
            Log::info('JIT IP: requesting IP from provider', [
                'node'     => $node->name,
                'provider' => $node->provider_name,
                'server_id'=> $node->provider_server_id,
            ]);

            $result = $adapter->purchaseIp($node);

            if ($result) {
                Log::info('JIT IP: acquired successfully', [
                    'node'    => $node->name,
                    'ip'      => $result->ip,
                    'gateway' => $result->gateway,
                    'netmask' => $result->netmask,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('JIT IP: exception during acquisition', [
                'node'    => $node->name,
                'provider'=> $node->provider_name,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Release an IP back to the provider (e.g. on VM termination).
     *
     * @param ProxmoxNode $node
     * @param string      $ipAddress
     * @return bool
     */
    public function releaseIp(ProxmoxNode $node, string $ipAddress): bool
    {
        if (!$node->supportsJitIp()) {
            return false;
        }

        $adapter = $this->resolveAdapter($node);

        if (!$adapter) {
            return false;
        }

        try {
            return $adapter->releaseIp($node, $ipAddress);
        } catch (\Exception $e) {
            Log::error('JIT IP: exception during release', [
                'node'    => $node->name,
                'ip'      => $ipAddress,
                'error'   => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Resolve the correct provider adapter based on node configuration.
     */
    protected function resolveAdapter(ProxmoxNode $node): ?ProviderAdapterInterface
    {
        return match (strtolower($node->provider_name ?? '')) {
            'hetzner', 'hetzner_robot' => new HetznerRobotAdapter(),
            'ovh'                      => new OvhAdapter(),
            'hivelocity'               => new HivelocityAdapter(),
            default                    => null,
        };
    }
}

/**
 * Data transfer object for JIT-acquired IP details.
 */
class JitIpResult
{
    public function __construct(
        public readonly string $ip,
        public readonly ?string $gateway = null,
        public readonly ?string $netmask = null,
        public readonly ?string $rdns = null,
        public readonly ?string $providerRef = null, // Provider-specific reference ID
        public readonly ?float $cost = null,
        public readonly array $raw = [],
    ) {}

    /**
     * Build Proxmox cloud-init ipconfig0 string.
     */
    public function toProxmoxIpConfig(): string
    {
        $cidr = $this->netmask ? $this->ip . '/' . $this->netmaskToCidr($this->netmask) : $this->ip . '/24';
        $gw   = $this->gateway ?? '';
        return "ip={$cidr},gw={$gw}";
    }

    /**
     * Convert dotted-decimal netmask to CIDR prefix.
     */
    protected function netmaskToCidr(string $netmask): int
    {
        $long = ip2long($netmask);
        if ($long === false) {
            return 24;
        }
        $binary = sprintf('%032b', $long);
        return substr_count($binary, '1');
    }
}

/**
 * Contract every provider adapter must implement.
 */
interface ProviderAdapterInterface
{
    /**
     * Purchase a single IPv4 for the given node.
     *
     * @param ProxmoxNode $node
     * @return JitIpResult|null
     */
    public function purchaseIp(ProxmoxNode $node): ?JitIpResult;

    /**
     * Release / cancel an IP back to the provider.
     *
     * @param ProxmoxNode $node
     * @param string      $ipAddress
     * @return bool
     */
    public function releaseIp(ProxmoxNode $node, string $ipAddress): bool;
}

/**
 * Hetzner Robot (WebService) Adapter — Dedicated Server Add-on IPs
 *
 * Docs: https://robot.your-server.de/doc/webservice/en.html
 */
class HetznerRobotAdapter implements ProviderAdapterInterface
{
    protected string $baseUrl = 'https://robot-ws.your-server.de';

    public function purchaseIp(ProxmoxNode $node): ?JitIpResult
    {
        $apiKey    = $node->getDecryptedProviderApiKey();
        $apiSecret = $node->getDecryptedProviderApiSecret();
        $serverId  = $node->provider_server_id;

        if (empty($apiKey) || empty($serverId)) {
            Log::error('Hetzner Robot: missing credentials or server ID', ['node' => $node->name]);
            return null;
        }

        try {
            // 1. Order a single IPv4 add-on for this server
            $orderUrl = "{$this->baseUrl}/order/ip";

            $response = Http::withBasicAuth($apiKey, $apiSecret ?? '')
                ->timeout(30)
                ->withOptions(['verify' => true])
                ->post($orderUrl, [
                    'server_number' => $serverId,
                    'mask_size'     => 32, // Single IPv4
                ]);

            if (!$response->successful()) {
                Log::error('Hetzner Robot: IP order failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $ip   = $data['ip']['ip'] ?? null;

            if (!$ip) {
                Log::error('Hetzner Robot: IP missing from order response', ['response' => $data]);
                return null;
            }

            // 2. Fetch IP details to get gateway / netmask
            $detailResponse = Http::withBasicAuth($apiKey, $apiSecret ?? '')
                ->timeout(30)
                ->get("{$this->baseUrl}/ip/{$ip}");

            $gateway = $node->network_gateway ?? null;
            $netmask = '255.255.255.255';

            if ($detailResponse->successful()) {
                $detail = $detailResponse->json();
                $gateway = $detail['ip']['gateway'] ?? $gateway;
                $netmask = $detail['ip']['mask'] ?? $netmask;
            }

            return new JitIpResult(
                ip: $ip,
                gateway: $gateway,
                netmask: $netmask,
                providerRef: $serverId,
                cost: $node->jit_ip_cost,
                raw: $data,
            );

        } catch (\Exception $e) {
            Log::error('Hetzner Robot: exception purchasing IP', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function releaseIp(ProxmoxNode $node, string $ipAddress): bool
    {
        $apiKey    = $node->getDecryptedProviderApiKey();
        $apiSecret = $node->getDecryptedProviderApiSecret();

        if (empty($apiKey)) {
            return false;
        }

        try {
            // Hetzner Robot: DELETE /ip/{ip} cancels the IP
            $response = Http::withBasicAuth($apiKey, $apiSecret ?? '')
                ->timeout(30)
                ->delete("{$this->baseUrl}/ip/{$ipAddress}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Hetzner Robot: exception releasing IP', [
                'ip'    => $ipAddress,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

/**
 * OVHcloud Adapter — Dedicated Server Add-on IPs
 *
 * Stub: implement when OWH API credentials & server IDs are available.
 */
class OvhAdapter implements ProviderAdapterInterface
{
    public function purchaseIp(ProxmoxNode $node): ?JitIpResult
    {
        Log::warning('OVH JIT IP adapter is a stub — not yet implemented', ['node' => $node->name]);
        return null;
    }

    public function releaseIp(ProxmoxNode $node, string $ipAddress): bool
    {
        Log::warning('OVH JIT IP adapter is a stub — not yet implemented', ['node' => $node->name]);
        return false;
    }
}

/**
 * Hivelocity Adapter — Dedicated Server Add-on IPs
 *
 * Stub: implement when Hivelocity API credentials & server IDs are available.
 */
class HivelocityAdapter implements ProviderAdapterInterface
{
    public function purchaseIp(ProxmoxNode $node): ?JitIpResult
    {
        Log::warning('Hivelocity JIT IP adapter is a stub — not yet implemented', ['node' => $node->name]);
        return null;
    }

    public function releaseIp(ProxmoxNode $node, string $ipAddress): bool
    {
        Log::warning('Hivelocity JIT IP adapter is a stub — not yet implemented', ['node' => $node->name]);
        return false;
    }
}
