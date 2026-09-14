<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DnsRecord;
use App\Models\UserDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DnsRecordController extends Controller
{
    /**
     * Display DNS records for a domain
     */
    public function index(int $domainId)
    {
        $domain = UserDomain::where('id', $domainId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $records = DnsRecord::byDomain($domainId)
            ->byUser(Auth::id())
            ->active()
            ->orderBy('record_type')
            ->orderBy('name')
            ->get();

        // Group records by type for display
        $groupedRecords = $records->groupBy('record_type');

        return view('client.dns-records.index', compact('domain', 'records', 'groupedRecords'));
    }

    /**
     * Get DNS records via API (for dashboard)
     */
    public function getDnsRecords(int $domainId)
    {
        try {
            $domain = UserDomain::where('id', $domainId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$domain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain not found',
                ], 404);
            }

            // Get DNS records from database
            $records = DnsRecord::byDomain($domainId)
                ->byUser(Auth::id())
                ->active()
                ->orderBy('record_type')
                ->orderBy('name')
                ->get();

            // If no records exist, fetch from provider and save
            if ($records->isEmpty()) {
                $records = $this->syncFromProvider($domain);
            }

            // Check nameserver status
            $nsStatus = $this->checkNameserverStatus($domain);

            return response()->json([
                'success' => true,
                'domain' => $domain->domain_name,
                'records' => $records,
                'nameservers' => $domain->nameservers ?? [],
                'ns_status' => $nsStatus,
                'is_using_believoo_ns' => $this->isUsingBelievooNs($domain),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get DNS records', [
                'domain_id' => $domainId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch DNS records',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new DNS record
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_domain_id' => 'required|exists:user_domains,id',
            'record_type' => 'required|in:A,AAAA,CNAME,MX,TXT,NS,SRV,CAA',
            'name' => 'required|string|max:255',
            'value' => 'required|string',
            'ttl' => 'nullable|integer|min:60|max:86400',
            'priority' => 'nullable|integer|min:0|max:65535',
        ]);

        // Verify domain ownership
        $domain = UserDomain::where('id', $validated['user_domain_id'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            $record = DnsRecord::create([
                'user_domain_id' => $validated['user_domain_id'],
                'user_id' => Auth::id(),
                'record_type' => strtoupper($validated['record_type']),
                'name' => $validated['name'],
                'value' => $validated['value'],
                'ttl' => $validated['ttl'] ?? 3600,
                'priority' => $validated['priority'] ?? null,
                'is_active' => true,
                'source' => 'manual',
            ]);

            // Sync to provider if using BelieVoo DNS
            if ($domain->use_believoo_dns) {
                $this->syncToProvider($record);
            }

            Log::info('DNS record created', [
                'record_id' => $record->id,
                'domain' => $domain->domain_name,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'DNS record created successfully',
                'record' => $record,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create DNS record', [
                'domain_id' => $validated['user_domain_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create DNS record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a DNS record
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'value' => 'sometimes|string',
            'ttl' => 'sometimes|integer|min:60|max:86400',
            'priority' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'sometimes|boolean',
        ]);

        $record = DnsRecord::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            $record->update($validated);

            // Sync to provider if using BelieVoo DNS
            $domain = $record->userDomain;
            if ($domain && $domain->use_believoo_dns) {
                $this->syncToProvider($record);
            }

            return response()->json([
                'success' => true,
                'message' => 'DNS record updated successfully',
                'record' => $record,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update DNS record', [
                'record_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update DNS record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a DNS record
     */
    public function destroy(int $id)
    {
        $record = DnsRecord::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        try {
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'DNS record deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete DNS record', [
                'record_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete DNS record',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-create A record after VPS creation
     */
    public static function autoCreateVpsARecord(int $userId, int $domainId, string $ip, ?string $subdomain = null): ?DnsRecord
    {
        try {
            $domain = UserDomain::find($domainId);
            
            if (!$domain) {
                Log::warning('Domain not found for VPS A record creation', [
                    'domain_id' => $domainId,
                    'user_id' => $userId,
                ]);
                return null;
            }

            // Default subdomain is @ (root) or 'www' if domain name matches
            $recordName = $subdomain ?? '@';

            // Delete existing provisioned A records for this subdomain
            DnsRecord::where('user_domain_id', $domainId)
                ->where('name', $recordName)
                ->where('record_type', 'A')
                ->where('is_provisioned', true)
                ->delete();

            // Create new A record
            $record = DnsRecord::createVpsARecord(
                $userId,
                $domainId,
                $recordName,
                $ip,
                'Auto-provisioned from VPS: ' . now()->format('Y-m-d H:i:s')
            );

            Log::info('VPS A record auto-created', [
                'record_id' => $record->id,
                'domain' => $domain->domain_name,
                'ip' => $ip,
                'user_id' => $userId,
            ]);

            return $record;

        } catch (\Exception $e) {
            Log::error('Failed to auto-create VPS A record', [
                'domain_id' => $domainId,
                'user_id' => $userId,
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Sync records from domain provider
     */
    private function syncFromProvider(UserDomain $domain): array
    {
        try {
            $service = $domain->domainProvider?->getService();
            
            if (!$service || !$domain->use_believoo_dns) {
                return [];
            }

            $providerRecords = $service->getDnsRecords($domain->domain_name);
            
            if (empty($providerRecords)) {
                return [];
            }

            // Create default NS records if none exist
            DnsRecord::createDefaultNsRecords($domain->user_id, $domain->id);

            // Save provider records to database
            foreach ($providerRecords as $record) {
                DnsRecord::create([
                    'user_domain_id' => $domain->id,
                    'user_id' => $domain->user_id,
                    'record_type' => $record['type'] ?? 'A',
                    'name' => $record['name'] ?? '@',
                    'value' => $record['value'] ?? '',
                    'ttl' => $record['ttl'] ?? 3600,
                    'priority' => $record['priority'] ?? null,
                    'is_active' => true,
                    'source' => 'provider',
                ]);
            }

            return DnsRecord::byDomain($domain->id)->active()->get()->toArray();

        } catch (\Exception $e) {
            Log::error('Failed to sync DNS from provider', [
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Sync record to provider
     */
    private function syncToProvider(DnsRecord $record): bool
    {
        try {
            $domain = $record->userDomain;
            $service = $domain?->domainProvider?->getService();
            
            if (!$service) {
                return false;
            }

            // This would call the provider's API to add/update the record
            // Implementation depends on the provider service
            return true;

        } catch (\Exception $e) {
            Log::error('Failed to sync DNS to provider', [
                'record_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if domain is using BelieVoo nameservers
     */
    public static function isUsingBelievooNs(UserDomain $domain): bool
    {
        $domainNs = $domain->nameservers ?? [];
        $believooNs = DnsRecord::BELIEVOO_NS;

        // Check if any BelieVoo NS is in the domain's NS list
        foreach ($believooNs as $ns) {
            foreach ($domainNs as $domainNsItem) {
                if (str_contains(strtolower($domainNsItem), strtolower($ns))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check nameserver status
     */
    private function checkNameserverStatus(UserDomain $domain): array
    {
        $domainNs = $domain->nameservers ?? [];
        $believooNs = DnsRecord::BELIEVOO_NS;

        $status = [
            'current_ns' => $domainNs,
            'recommended_ns' => $believooNs,
            'is_using_believoo' => false,
            'needs_update' => false,
        ];

        // Check if using BelieVoo NS
        $hasBelievooNs = false;
        foreach ($believooNs as $ns) {
            foreach ($domainNs as $domainNsItem) {
                if (str_contains(strtolower($domainNsItem), strtolower($ns))) {
                    $hasBelievooNs = true;
                    break 2;
                }
            }
        }

        $status['is_using_believoo'] = $hasBelievooNs;
        $status['needs_update'] = !$hasBelievooNs && $domain->use_believoo_dns;

        return $status;
    }

    /**
     * Get nameserver info for dashboard
     */
    public function getNameserverInfo(int $domainId)
    {
        try {
            $domain = UserDomain::where('id', $domainId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$domain) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain not found',
                ], 404);
            }

            $nsStatus = $this->checkNameserverStatus($domain);

            return response()->json([
                'success' => true,
                'domain' => $domain->domain_name,
                'nameservers' => $domain->nameservers ?? [],
                'ns_status' => $nsStatus,
                'alert' => $nsStatus['needs_update'] ? 'Please update your nameservers to BelieVoo DNS for full management' : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get nameserver info', [
                'domain_id' => $domainId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get nameserver information',
            ], 500);
        }
    }
}
