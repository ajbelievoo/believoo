<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\UserDomain;
use App\Services\DomainRegistration\DomainAggregatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomainController extends Controller
{
    protected DomainAggregatorService $aggregator;

    public function __construct(DomainAggregatorService $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * Show domain search page
     */
    public function search()
    {
        return view('client.domains.search');
    }

    /**
     * AJAX: Search for domains across all providers
     */
    public function searchDomains(Request $request)
    {
        $request->validate([
            'domains' => 'required|string',
        ]);

        $domainInput = $request->input('domains');

        // Parse multiple domains (comma or newline separated)
        $inputs = array_map('trim', preg_split('/[\n,]+/', $domainInput));
        $inputs = array_filter($inputs);

        if (empty($inputs)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid domains provided',
            ], 422);
        }

        // Popular TLDs to check automatically
        $popularTlds = ['com', 'in', 'net', 'org', 'co', 'io', 'dev', 'app', 'xyz', 'info', 'co.in', 'site'];

        $domainsToCheck = [];

        foreach ($inputs as $input) {
            $input = strtolower(trim($input));

            if (str_contains($input, '.')) {
                // User provided full domain with TLD
                $domainsToCheck[] = $input;
            } else {
                // User provided only SLD - check multiple popular TLDs
                foreach ($popularTlds as $tld) {
                    $domainsToCheck[] = $input . '.' . $tld;
                }
            }
        }

        // Limit to 20 domains per search to avoid API overload
        $domainsToCheck = array_slice($domainsToCheck, 0, 20);
        $domainsToCheck = array_unique($domainsToCheck);

        $results = [];
        foreach ($domainsToCheck as $domain) {
            $results[$domain] = $this->aggregator->searchDomain($domain);
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    /**
     * Show domain registration form
     */
    public function showRegistrationForm(Request $request)
    {
        $domain = $request->input('domain');
        $provider = $request->input('provider');
        $price = $request->input('price');

        if (!$domain || !$provider) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Please search and select a domain first');
        }

        // If user is not authenticated, redirect to login with redirect back
        if (!auth()->check()) {
            $redirectUrl = '/domains/register?domain=' . urlencode($domain) . '&provider=' . urlencode($provider) . '&price=' . urlencode($price);
            return redirect()->route('login', ['redirect' => $redirectUrl])
                ->with('info', 'Please login to register your domain');
        }

        // Verify the domain is still available
        $searchResult = $this->aggregator->searchDomain($domain);

        if (!$searchResult['is_available']) {
            return redirect()->route('client.domains.search')
                ->with('error', 'Domain is no longer available: ' . $domain);
        }

        return view('client.domains.register', compact('domain', 'provider', 'price', 'searchResult'));
    }

    /**
     * Process domain registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'domain' => 'required|string|max:255',
            'years' => 'required|integer|min:1|max:10',
            'nameservers' => 'nullable|string',
            'whois_privacy' => 'boolean',
            'auto_renew' => 'boolean',
            
            // Contact information
            'registrant.first_name' => 'required|string|max:100',
            'registrant.last_name' => 'required|string|max:100',
            'registrant.email' => 'required|email|max:255',
            'registrant.phone' => 'required|string|max:30',
            'registrant.address1' => 'required|string|max:255',
            'registrant.city' => 'required|string|max:100',
            'registrant.state' => 'required|string|max:100',
            'registrant.zip' => 'required|string|max:20',
            'registrant.country' => 'required|string|max:2',
        ]);

        $domain = strtolower(trim($validated['domain']));

        // Prepare registration parameters
        $params = [
            'user_id' => auth()->id(),
            'years' => $validated['years'],
            'whois_privacy' => $validated['whois_privacy'] ?? false,
            'auto_renew' => $validated['auto_renew'] ?? true,
            'registrant' => $validated['registrant'],
            'admin' => $validated['registrant'], // Use same for admin
            'technical' => $validated['registrant'], // Use same for tech
            'billing' => $validated['registrant'], // Use same for billing
        ];

        // Parse nameservers - default to BelieVoo NS if not provided
        if (!empty($validated['nameservers'])) {
            $params['nameservers'] = array_map('trim', explode(',', $validated['nameservers']));
        } else {
            // Auto-set BelieVoo nameservers by default
            $params['nameservers'] = \App\Models\DnsRecord::BELIEVOO_NS;
            $params['use_believoo_dns'] = true;
        }

        // Register with failover
        $result = $this->aggregator->registerWithFailover($domain, $params);

        if ($result['success']) {
            Log::info('Domain registered successfully', [
                'domain' => $domain,
                'user_id' => auth()->id(),
                'provider' => $result['provider'],
                'price' => $result['price_paid'],
            ]);

            // Find the newly registered domain and create default NS records
            try {
                $userDomain = UserDomain::where('domain_name', $domain)
                    ->where('user_id', auth()->id())
                    ->latest()
                    ->first();

                if ($userDomain) {
                    // Create default NS records
                    DnsRecord::createDefaultNsRecords(auth()->id(), $userDomain->id);

                    // Also set BelieVoo nameservers on the domain if using BelieVoo DNS
                    if (empty($validated['nameservers'])) {
                        $userDomain->update([
                            'nameservers' => DnsRecord::BELIEVOO_NS,
                            'use_believoo_dns' => true,
                        ]);
                    }

                    Log::info('Default NS records created for domain', [
                        'domain' => $domain,
                        'domain_id' => $userDomain->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to create default NS records for domain', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                ]);
            }

            return redirect()->route('client.domains.my-domains')
                ->with('success', "Congratulations! {$domain} has been registered successfully.");
        }

        Log::error('Domain registration failed', [
            'domain' => $domain,
            'user_id' => auth()->id(),
            'error' => $result['message'],
        ]);

        return back()->with('error', 'Registration failed: ' . $result['message']);
    }

    /**
     * Show My Domains dashboard
     */
    public function myDomains()
    {
        $domains = $this->aggregator->getUserDomains(auth()->id());
        
        // Calculate stats
        $stats = [
            'total' => $domains->count(),
            'active' => $domains->where('status', 'active')->count(),
            'expiring_soon' => $domains->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>=', now())
                ->count(),
            'expired' => $domains->where('expiry_date', '<', now())->count(),
        ];

        return view('client.domains.my-domains', compact('domains', 'stats'));
    }

    /**
     * Show domain details and DNS management
     */
    public function show(UserDomain $domain)
    {
        // Ensure user owns this domain
        if ($domain->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Get DNS records
        $dnsRecords = $this->aggregator->getDomainDns($domain->domain_name, auth()->id());

        // Sync latest info from provider
        $domain->syncFromProvider();

        return view('client.domains.show', compact('domain', 'dnsRecords'));
    }

    /**
     * Update DNS records
     */
    public function updateDns(Request $request, UserDomain $domain)
    {
        if ($domain->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'records' => 'required|array',
            'records.*.name' => 'required|string|max:255',
            'records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV',
            'records.*.value' => 'required|string|max:500',
            'records.*.ttl' => 'nullable|integer|min:60|max:86400',
            'records.*.priority' => 'nullable|integer|min:0|max:65535',
        ]);

        $result = $this->aggregator->updateDomainDns(
            $domain->domain_name, 
            $validated['records'], 
            auth()->id()
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'DNS records updated successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to update DNS records',
        ], 422);
    }

    /**
     * Update nameservers
     */
    public function updateNameservers(Request $request, UserDomain $domain)
    {
        if ($domain->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'nameservers' => 'required|array|min:2|max:5',
            'nameservers.*' => 'required|string|max:255',
            'use_believoo_dns' => 'boolean',
        ]);

        $nameservers = $validated['nameservers'];

        // If using Believoo DNS
        if ($validated['use_believoo_dns'] ?? false) {
            $nameservers = UserDomain::getBelievooNameservers();
        }

        // Update via provider
        $service = $domain->domainProvider?->getService();
        
        if ($service) {
            $result = $service->updateNameservers($domain->domain_name, $nameservers);
            
            if ($result['success']) {
                $domain->update([
                    'nameservers' => $nameservers,
                    'use_believoo_dns' => $validated['use_believoo_dns'] ?? false,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Nameservers updated successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to update nameservers',
            ], 422);
        }

        return response()->json([
            'success' => false,
            'message' => 'Provider service not available',
        ], 422);
    }

    /**
     * Renew domain
     */
    public function renew(Request $request, UserDomain $domain)
    {
        if ($domain->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'years' => 'required|integer|min:1|max:10',
        ]);

        $result = $this->aggregator->renewDomain(
            $domain->domain_name, 
            $validated['years'], 
            auth()->id()
        );

        if ($result['success']) {
            return redirect()->route('client.domains.my-domains')
                ->with('success', "{$domain->domain_name} has been renewed successfully.");
        }

        return back()->with('error', 'Renewal failed: ' . $result['message']);
    }

    /**
     * Get domain auth/EPP code
     */
    public function getAuthCode(UserDomain $domain)
    {
        if ($domain->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Get from provider
        $service = $domain->domainProvider?->getService();
        
        if ($service) {
            $info = $service->getDomainInfo($domain->domain_name);
            
            if ($info && isset($info['auth_code'])) {
                $domain->update(['auth_code' => $info['auth_code']]);
                
                return response()->json([
                    'success' => true,
                    'auth_code' => $info['auth_code'],
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to retrieve auth code',
        ], 422);
    }

    /**
     * Toggle auto-renew
     */
    public function toggleAutoRenew(UserDomain $domain)
    {
        if ($domain->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $domain->update(['auto_renew' => !$domain->auto_renew]);

        return response()->json([
            'success' => true,
            'auto_renew' => $domain->auto_renew,
            'message' => $domain->auto_renew ? 'Auto-renew enabled' : 'Auto-renew disabled',
        ]);
    }

    /**
     * Connect external domain (purchased from other registrar like GoDaddy, Namecheap, etc.)
     */
    public function connectExternalDomain(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/',
            'current_nameservers' => 'nullable|array',
            'current_nameservers.*' => 'string',
            'use_believoo_dns' => 'sometimes|boolean',
            'target_ip' => 'nullable|required_if:use_believoo_dns,true,1|ip',
        ]);

        $domainName = strtolower(trim($validated['domain_name']));
        $userId = auth()->id();

        // Whether the client wants to manage DNS through BelieVoo nameservers
        $useBelievooDns = (bool) ($validated['use_believoo_dns'] ?? false);
        $targetIp = $validated['target_ip'] ?? null;

        // Check if domain already exists
        $existingDomain = UserDomain::where('domain_name', $domainName)
            ->where('user_id', $userId)
            ->first();

        if ($existingDomain) {
            return response()->json([
                'success' => false,
                'message' => 'Domain is already connected to your account',
            ], 422);
        }

        try {
            // Create external domain record
            $parts = explode('.', $domainName);
            $tld   = count($parts) >= 2 ? implode('.', array_slice($parts, -2)) : $domainName;
            $sld   = count($parts) >= 2 ? $parts[0] : $domainName;

            $domain = UserDomain::create([
                'user_id'             => $userId,
                'domain_name'         => $domainName,
                'tld'                 => $tld,
                'sld'                 => $sld,
                'status'              => 'active',
                'domain_provider_id'  => null,
                'nameservers'         => $useBelievooDns
                    ? \App\Models\DnsRecord::BELIEVOO_NS
                    : ($validated['current_nameservers'] ?? []),
                'external_registrar'  => $this->detectRegistrar($validated['current_nameservers'] ?? []),
                'use_believoo_dns'    => $useBelievooDns,
                'use_provider_dns'    => !$useBelievooDns,
            ]);

            // If using BelieVoo DNS, build a complete starter zone and push it live
            if ($useBelievooDns && $targetIp) {
                \App\Models\DnsRecord::createDefaultWebRecords($userId, $domain->id, $targetIp);
                \Illuminate\Support\Facades\Artisan::call('dns:sync-zones', [
                    'domain' => $domainName,
                ]);
            }

            Log::info('External domain connected', [
                'domain' => $domainName,
                'user_id' => $userId,
                'current_ns' => $validated['current_nameservers'] ?? [],
                'use_believoo_dns' => $useBelievooDns,
                'target_ip' => $targetIp,
            ]);

            return response()->json([
                'success' => true,
                'domain' => $domain,
                'message' => 'Domain connected successfully',
                'registrar' => $domain->external_registrar,
                'next_steps' => [
                    'Update nameservers at your registrar to:',
                    'ns1.believoo.com',
                    'ns2.believoo.com',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to connect external domain', [
                'domain' => $domainName,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect domain: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect registrar from nameservers
     */
    private function detectRegistrar(array $nameservers): ?string
    {
        $nsString = strtolower(implode(' ', $nameservers));

        $patterns = [
            'GoDaddy' => ['godaddy', 'domaincontrol'],
            'Namecheap' => ['namecheap', 'registrar-servers'],
            'Cloudflare' => ['cloudflare', 'lara'],
            'Google Domains' => ['google', 'googledomains'],
            'Hostinger' => ['hostinger', 'dns-parking'],
            'Bluehost' => ['bluehost'],
            'OVH' => ['ovh'],
            'AWS Route53' => ['awsdns', 'amazon'],
            'Name.com' => ['name.com', 'name-services'],
        ];

        foreach ($patterns as $registrar => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($nsString, $keyword)) {
                    return $registrar;
                }
            }
        }

        return null;
    }
}
