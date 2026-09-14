<?php

namespace App\Http\Controllers;

use App\Models\StreamingPlan;
use App\Models\StreamingSubscription;
use App\Models\Order;
use Illuminate\Http\Request;

class StreamingPlansController extends Controller
{
    public function index()
    {
        $plans = StreamingPlan::active()->standalone()->cloudHosted()->orderBy('sort_order')->get();
        return view('services.streaming', compact('plans'));
    }

    /**
     * Process standalone streaming plan checkout
     */
    public function checkout(Request $request, $planSlug)
    {
        $plan = StreamingPlan::where('slug', $planSlug)
            ->active()
            ->standalone()
            ->cloudHosted()
            ->firstOrFail();

        // Ensure streaming service exists for checkout
        $service = \App\Models\Service::firstOrCreate(
            ['slug' => 'streaming'],
            [
                'name' => 'Streaming',
                'description' => 'BelieVoo Live Streaming Service',
                'status' => 'active',
                'type' => 'streaming',
                'pricing_tiers' => [],
            ]
        );

        // Redirect to standard checkout with streaming plan preselected
        return redirect()->route('checkout', [
            'service' => $service->slug,
            'tier' => $plan->slug,
        ]);
    }

    /**
     * Process standalone streaming order completion
     */
    public function processOrder(Request $request)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:streaming_plans,id',
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $plan = StreamingPlan::findOrFail($validated['plan_id']);
        $order = Order::findOrFail($validated['order_id']);

        try {
            // Create streaming subscription for standalone plan
            $subscription = StreamingSubscription::create([
                'user_id' => $validated['user_id'],
                'streaming_plan_id' => $plan->id,
                'order_id' => $order->id,
                'delivery_method' => 'cloud_hosted',
                'status' => 'pending_setup',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'price' => $plan->price,
            ]);

            // Provision on BelieVoo master streaming cluster
            $this->provisionOnMasterCluster($subscription, $plan);
            
            // Update subscription status
            $subscription->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

            // Create streaming API keys
            $this->createStreamingApiKeys($subscription);

            return response()->json([
                'success' => true,
                'subscription_id' => $subscription->id,
                'message' => 'Standalone streaming provisioned successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Standalone streaming order processing failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to provision streaming service'
            ], 500);
        }
    }

    /**
     * Provision streaming on BelieVoo master cluster
     */
    private function provisionOnMasterCluster($subscription, $plan)
    {
        // Generate unique streaming instance ID
        $instanceId = 'stream-' . $subscription->id . '-' . uniqid();
        
        // Master cluster endpoints
        $masterCluster = [
            'rtmp_endpoint' => 'rtmp://stream.believoo.com/live',
            'webrtc_endpoint' => 'wss://stream.believoo.com/webrtc',
            'hls_endpoint' => 'https://stream.believoo.com/hls',
            'api_endpoint' => 'https://api.stream.believoo.com',
        ];

        // Store cluster configuration
        $subscription->update([
            'streaming_config' => [
                'instance_id' => $instanceId,
                'cluster_endpoints' => $masterCluster,
                'plan_limits' => [
                    'max_viewers' => $plan->max_viewers,
                    'max_bitrate' => $plan->max_bitrate,
                    'max_resolution' => $plan->max_resolution,
                    'bandwidth_gb' => $plan->bandwidth_gb,
                    'storage_gb' => $plan->storage_gb,
                ]
            ]
        ]);

        // Call master cluster API to provision instance
        $this->callMasterClusterApi([
            'action' => 'provision',
            'instance_id' => $instanceId,
            'subscription_id' => $subscription->id,
            'plan_limits' => [
                'max_viewers' => $plan->max_viewers,
                'max_bitrate' => $plan->max_bitrate,
                'max_resolution' => $plan->max_resolution,
                'bandwidth_gb' => $plan->bandwidth_gb,
                'storage_gb' => $plan->storage_gb,
            ],
            'features' => [
                'rtmp_support' => $plan->rtmp_support,
                'webrtc_support' => $plan->webrtc_support,
                'hls_support' => $plan->hls_support,
                'dash_support' => $plan->dash_support,
                'recording_enabled' => $plan->recording_enabled,
                'transcoding_enabled' => $plan->transcoding_enabled,
                'adaptive_bitrate' => $plan->adaptive_bitrate,
                'low_latency' => $plan->low_latency,
            ]
        ]);

        \Log::info("Provisioned standalone streaming instance {$instanceId} for subscription {$subscription->id}");
    }

    /**
     * Call master cluster API
     */
    private function callMasterClusterApi($data)
    {
        // This would integrate with your actual master streaming cluster API
        // Example implementation using HTTP client
        
        $client = new \GuzzleHttp\Client([
            'base_uri' => config('streaming.master_cluster_url', 'https://api.stream.believoo.com'),
            'headers' => [
                'Authorization' => 'Bearer ' . config('streaming.master_cluster_api_key'),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);

        try {
            $response = $client->post('/v1/instances', [
                'json' => $data
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            \Log::error('Master cluster API call failed: ' . $e->getMessage());
            throw new \Exception('Failed to provision on master cluster');
        }
    }

    /**
     * Create streaming API keys for the subscription
     */
    private function createStreamingApiKeys($subscription)
    {
        $config = $subscription->streaming_config ?? [];
        $endpoints = $config['cluster_endpoints'] ?? [];

        // Generate API credentials (Agora-style)
        $appId = 'app_' . bin2hex(random_bytes(16));
        $appCertificate = bin2hex(random_bytes(32));
        $restApiKey = 'rk_' . bin2hex(random_bytes(16));
        $restApiSecret = bin2hex(random_bytes(32));

        // Create streaming API key record
        \App\Models\StreamingApiKey::create([
            'user_id' => $subscription->user_id,
            'streaming_plan_id' => $subscription->streaming_plan_id,
            'streaming_subscription_id' => $subscription->id,
            'order_id' => $subscription->order_id,
            'app_id' => $appId,
            'app_certificate' => $appCertificate,
            'rest_api_key' => $restApiKey,
            'rest_api_secret' => $restApiSecret,
            'rtmp_ingest_url' => $endpoints['rtmp_endpoint'] ?? 'rtmp://stream.believoo.com/live',
            'webrtc_ingest_url' => $endpoints['webrtc_endpoint'] ?? 'wss://stream.believoo.com/webrtc',
            'hls_playback_url' => $endpoints['hls_endpoint'] ?? 'https://stream.believoo.com/hls',
            'web_rtc_url' => $endpoints['webrtc_endpoint'] ?? 'wss://stream.believoo.com/webrtc',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        \Log::info("Created streaming API keys for subscription {$subscription->id}");
    }

    /**
     * Activate streaming addon on an existing VPS with prorated pricing
     */
    public function activateVpsAddon(Request $request, $hostingId)
    {
        $hosting = \App\Models\UserHosting::where('id', $hostingId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Find the VPS plan
        $vpsPlan = \App\Models\VpsPlan::where('name', $hosting->plan_name ?? $hosting->server_type)->first();
        if (!$vpsPlan) {
            return redirect()->route('client.dashboard')
                ->with('error', 'VPS plan not found for streaming addon activation.');
        }

        $addonPrice = $vpsPlan->streaming_addon_price ?? 0;
        if ($addonPrice <= 0) {
            return redirect()->route('client.dashboard')
                ->with('error', 'Streaming addon is not available for this VPS plan.');
        }

        // Calculate prorated price for remaining days
        $expiryDate = $hosting->expiry_date ? \Carbon\Carbon::parse($hosting->expiry_date) : null;
        $today = \Carbon\Carbon::today();
        $proratedPrice = $addonPrice;
        $daysRemaining = 30;

        if ($expiryDate && $expiryDate->isFuture()) {
            $daysRemaining = max(1, $today->diffInDays($expiryDate));
            $daysInMonth = max(1, $today->daysInMonth);
            $proratedPrice = round($addonPrice * ($daysRemaining / $daysInMonth), 2);
        }

        // Ensure streaming addon service exists for checkout
        $service = \App\Models\Service::firstOrCreate(
            ['slug' => 'streaming-addon'],
            [
                'title' => 'Streaming Addon',
                'name' => 'Streaming Addon',
                'description' => 'Live Streaming Addon for VPS',
                'status' => 'active',
                'type' => 'addon',
                'price' => $proratedPrice,
                'pricing_tiers' => [],
            ]
        );

        // Update service price to prorated amount
        $service->update(['price' => $proratedPrice]);

        // Store addon context in session
        session()->put('streaming_addon_context', [
            'hosting_id' => $hosting->id,
            'vps_plan_id' => $vpsPlan->id,
            'monthly_addon_price' => $addonPrice,
            'prorated_price' => $proratedPrice,
            'days_remaining' => $daysRemaining,
            'is_prorated' => true,
        ]);

        // Store VPS plan price for checkout context (so checkout uses correct base)
        session()->put('vps_plan_price', $proratedPrice);
        session()->put('vps_plan_name', $vpsPlan->name);

        // Redirect to checkout with streaming addon service
        return redirect()->route('checkout', [
            'service' => $service->slug,
        ]);
    }
}
