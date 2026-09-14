<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainProvider;
use App\Models\DomainPricing;
use App\Services\DomainRegistration\DomainAggregatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomainProviderController extends Controller
{
    protected DomainAggregatorService $aggregator;

    public function __construct(DomainAggregatorService $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * List all domain providers
     */
    public function index()
    {
        $providers = DomainProvider::orderBy('priority')
            ->withCount('userDomains')
            ->get();

        $connectionTests = [];

        return view('admin.domain-providers.index', compact('providers', 'connectionTests'));
    }

    /**
     * Show provider details and pricing
     */
    public function show(DomainProvider $provider)
    {
        $pricing = DomainPricing::where('domain_provider_id', $provider->id)
            ->orderBy('tld')
            ->get();

        $stats = [
            'total_domains' => $provider->userDomains()->count(),
            'active_domains' => $provider->userDomains()->active()->count(),
            'expired_domains' => $provider->userDomains()->expired()->count(),
            'total_tlds' => count($provider->supported_tlds ?? []),
        ];

        return view('admin.domain-providers.show', compact('provider', 'pricing', 'stats'));
    }

    /**
     * Edit provider configuration
     */
    public function edit(DomainProvider $provider)
    {
        return view('admin.domain-providers.edit', compact('provider'));
    }

    /**
     * Update provider configuration
     */
    public function update(Request $request, DomainProvider $provider)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:1|max:999',
            'api_url' => 'nullable|url',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'test_mode' => 'boolean',
            'supported_tlds' => 'nullable|string',
            'default_nameservers' => 'nullable|string',
        ]);

        try {
            // Parse TLDs
            $tlds = [];
            if (!empty($validated['supported_tlds'])) {
                $tlds = array_map('trim', explode(',', $validated['supported_tlds']));
                $tlds = array_map(fn($tld) => ltrim($tld, '.'), $tlds);
            }

            // Parse nameservers
            $nameservers = [];
            if (!empty($validated['default_nameservers'])) {
                $nameservers = array_map('trim', explode(',', $validated['default_nameservers']));
            }

            // Prepare metadata
            $metadata = $provider->metadata ?? [];
            if (!empty($validated['api_key'])) {
                $metadata['api_key'] = $validated['api_key'];
            }
            if (!empty($validated['api_secret'])) {
                $metadata['api_secret'] = $validated['api_secret'];
            }
            if (!empty($validated['username'])) {
                $metadata['username'] = $validated['username'];
            }
            if (!empty($validated['password'])) {
                $metadata['password'] = $validated['password'];
            }

            // Provider-specific metadata keys
            switch ($provider->code) {
                case 'resellerclub':
                    if (!empty($validated['api_key'])) {
                        $metadata['api_key'] = $validated['api_key']; // ResellerClub uses API Key
                    }
                    if (!empty($validated['username'])) {
                        $metadata['auth_userid'] = $validated['username']; // ResellerClub uses Auth UserID
                    }
                    break;
                case 'cloudflare':
                    if (!empty($validated['api_key'])) {
                        $metadata['api_token'] = $validated['api_key'];
                    }
                    break;
            }

            $provider->update([
                'name' => $validated['name'],
                'is_active' => $validated['is_active'] ?? false,
                'priority' => $validated['priority'],
                'api_url' => $validated['api_url'],
                'test_mode' => $validated['test_mode'] ? '1' : '0',
                'supported_tlds' => $tlds,
                'default_nameservers' => $nameservers,
                'metadata' => $metadata,
            ]);

            // Test connection if activating
            if ($provider->is_active) {
                $testResult = $provider->testConnection();
                
                if (!$testResult['success']) {
                    return back()->with('warning', 
                        'Provider updated but connection test failed: ' . $testResult['message']);
                }
            }

            Log::info('Domain provider updated', [
                'provider_id' => $provider->id,
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.domain-providers.index')
                ->with('success', 'Provider updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update domain provider', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update provider: ' . $e->getMessage());
        }
    }

    /**
     * Activate provider
     */
    public function activate(DomainProvider $provider)
    {
        try {
            // Test connection first
            $testResult = $provider->testConnection();

            if (!$testResult['success']) {
                return back()->with('error', 
                    'Cannot activate: Connection test failed. ' . $testResult['message']);
            }

            $provider->update([
                'is_active' => true,
                'status' => 'active',
            ]);

            Log::info('Domain provider activated', [
                'provider_id' => $provider->id,
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', "{$provider->name} has been activated and is ready to use.");

        } catch (\Exception $e) {
            Log::error('Failed to activate domain provider', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to activate provider: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate provider
     */
    public function deactivate(DomainProvider $provider)
    {
        try {
            $activeDomains = $provider->userDomains()->active()->count();

            $provider->update([
                'is_active' => false,
                'status' => 'inactive',
            ]);

            Log::info('Domain provider deactivated', [
                'provider_id' => $provider->id,
                'admin_id' => auth()->id(),
                'active_domains' => $activeDomains,
            ]);

            $message = "{$provider->name} has been deactivated.";
            if ($activeDomains > 0) {
                $message .= " Note: {$activeDomains} active domains are still using this provider.";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to deactivate domain provider', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to deactivate provider: ' . $e->getMessage());
        }
    }

    /**
     * Test provider connection
     */
    public function testConnection(DomainProvider $provider)
    {
        try {
            $result = $provider->testConnection();

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test all providers
     */
    public function testAll()
    {
        $results = $this->aggregator->testAllConnections();

        return view('admin.domain-providers.test-all', compact('results'));
    }

    /**
     * Manage pricing for a provider
     */
    public function pricing(DomainProvider $provider)
    {
        $pricing = DomainPricing::where('domain_provider_id', $provider->id)
            ->orderBy('tld')
            ->paginate(50);

        return view('admin.domain-providers.pricing', compact('provider', 'pricing'));
    }

    /**
     * Update pricing
     */
    public function updatePricing(Request $request, DomainProvider $provider)
    {
        $validated = $request->validate([
            'tld' => 'required|string|max:50',
            'years' => 'required|integer|min:1|max:10',
            'provider_register_price' => 'required|numeric|min:0',
            'provider_renew_price' => 'required|numeric|min:0',
            'provider_transfer_price' => 'nullable|numeric|min:0',
            'selling_register_price' => 'required|numeric|min:0',
            'selling_renew_price' => 'required|numeric|min:0',
            'selling_transfer_price' => 'nullable|numeric|min:0',
            'priority' => 'integer|min:1|max:999',
            'is_active' => 'boolean',
        ]);

        try {
            $pricing = DomainPricing::updateOrCreate(
                [
                    'domain_provider_id' => $provider->id,
                    'tld' => $validated['tld'],
                    'years' => $validated['years'],
                ],
                [
                    'provider_register_price' => $validated['provider_register_price'],
                    'provider_renew_price' => $validated['provider_renew_price'],
                    'provider_transfer_price' => $validated['provider_transfer_price'] ?? null,
                    'selling_register_price' => $validated['selling_register_price'],
                    'selling_renew_price' => $validated['selling_renew_price'],
                    'selling_transfer_price' => $validated['selling_transfer_price'] ?? null,
                    'priority' => $validated['priority'] ?? 100,
                    'is_active' => $validated['is_active'] ?? true,
                ]
            );

            Log::info('Domain pricing updated', [
                'provider_id' => $provider->id,
                'tld' => $validated['tld'],
                'admin_id' => auth()->id(),
            ]);

            return back()->with('success', 'Pricing updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update domain pricing', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update pricing: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update pricing from API
     */
    public function syncPricing(DomainProvider $provider)
    {
        try {
            $service = $provider->getService();
            
            if (!$service) {
                return back()->with('error', 'Service not available for this provider');
            }

            $updated = 0;
            $tlds = $provider->supported_tlds ?? [];

            foreach ($tlds as $tld) {
                try {
                    $priceInfo = $service->getDomainPrice($tld);
                    
                    if ($priceInfo['available']) {
                        DomainPricing::updateOrCreate(
                            [
                                'domain_provider_id' => $provider->id,
                                'tld' => $tld,
                                'years' => 1,
                            ],
                            [
                                'provider_register_price' => $priceInfo['price'],
                                'provider_renew_price' => $priceInfo['price'], // Assume same for now
                                'selling_register_price' => $priceInfo['price'] + 10, // Add margin
                                'selling_renew_price' => $priceInfo['price'] + 10,
                                'currency' => $priceInfo['currency'],
                                'is_active' => true,
                            ]
                        );
                        $updated++;
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to sync pricing for .{$tld}", [
                        'provider' => $provider->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return back()->with('success', "Pricing synced for {$updated} TLDs");

        } catch (\Exception $e) {
            Log::error('Failed to sync pricing', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to sync pricing: ' . $e->getMessage());
        }
    }
}
