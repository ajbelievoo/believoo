<?php

namespace App\Livewire;

use App\Models\Service;
use App\Models\Setting;
use Livewire\Component;

class Checkout extends Component
{
    public Service $service;
    public ?string $tierName = null;
    public float $amount = 0;
    public float $subtotal = 0;
    public float $gstAmount = 0;
    public float $totalWithGst = 0;
    public float $subtotalInINR = 0;
    public float $gstAmountInINR = 0;
    public float $totalWithGstInINR = 0;
    public int $billingMonths = 1;
    public int $quantity = 1; // Number of VPS instances
    public string $paymentGateway = 'razorpay';
    public string $currency = 'USD'; // USD or INR
    public const GST_RATE = 0.18;

    // Streaming Properties (Hybrid Architecture)
    public bool $hasStreamingAddon = false;
    public bool $isStandaloneStreaming = false;
    public ?int $streamingPlanId = null;
    public float $streamingAddonPrice = 0;
    public ?\App\Models\StreamingPlan $streamingPlan = null;
    public string $streamingDeliveryMethod = 'cloud_hosted'; // vps_embedded or cloud_hosted
    
    protected $listeners = [
        'billingCycleChanged' => 'updateBillingCycle',
        'currencyChanged' => 'setCurrency',
    ];
    
    public function mount(Service $service, ?string $tier = null)
    {
        $this->service = $service;
        $this->tierName = $tier;
        
        // Check if this is a standalone streaming service
        if ($service->slug === 'streaming' || str_contains($service->slug, 'streaming')) {
            $this->isStandaloneStreaming = true;
            $this->streamingDeliveryMethod = 'cloud_hosted';
        }
        
        // Check if coming from VPS plan configure page (session has plan details)
        $vpsPlanPrice = session('vps_plan_price');
        $vpsPlanName  = session('vps_plan_name');
        
        if ($tier && $vpsPlanName === $tier && $vpsPlanPrice > 0) {
            // Use VPS plan price directly (in INR)
            // Convert INR to USD for internal calculation
            $rate = \App\Models\ExchangeRate::getUsdToInrRate();
            $this->amount = round($vpsPlanPrice / $rate, 2);
        } elseif ($tier && !empty($service->pricing_tiers)) {
            // Calculate amount based on tier from service pricing_tiers
            foreach ($service->pricing_tiers as $t) {
                if ($t['name'] === $tier) {
                    $this->amount = floatval($t['price']);
                    break;
                }
            }
        }
        
        if ($this->amount <= 0) {
            $this->amount = floatval($service->price ?? 0);
        }
        
        // Set currency based on user preference or location
        $this->currency = $this->detectCurrency();
        
        // Select appropriate payment gateway based on currency
        $this->setPaymentGatewayByCurrency();
        
        // Set default billing cycle (monthly for VPS plans)
        $this->billingMonths = 1;
        $billingCycles = $this->service->billing_cycles ?? Service::getDefaultBillingCycles();
        foreach ($billingCycles as $cycle) {
            if ($cycle['recommended'] ?? false) {
                $this->billingMonths = $cycle['months'];
                break;
            }
        }
        
        $this->updateAmount();
    }
    
    public function updateBillingCycle(int $months)
    {
        $this->billingMonths = $months;
        $this->updateAmount();
    }
    
    public function updateAmount()
    {
        // Check if VPS plan price is in session
        $vpsPlanPrice = session('vps_plan_price');
        $vpsPlanName  = session('vps_plan_name');

        if ($this->tierName && $vpsPlanName === $this->tierName && $vpsPlanPrice > 0) {
            // VPS plan price is in INR monthly - convert to USD and multiply by billing months
            $rate = \App\Models\ExchangeRate::getUsdToInrRate();
            $monthlyPriceUsd = round($vpsPlanPrice / $rate, 2);
            $this->amount = round($monthlyPriceUsd * $this->billingMonths, 2);
        } else {
            $this->amount = $this->service->getPriceForCycle($this->billingMonths, $this->tierName);
        }
        $this->calculateGst();
    }

    public function calculateGst()
    {
        $addonAmount = $this->hasStreamingAddon ? $this->streamingAddonPrice : 0;
        $this->subtotal   = ($this->amount * $this->quantity) + $addonAmount;
        $this->gstAmount  = round($this->subtotal * self::GST_RATE, 2);
        $this->totalWithGst = round($this->subtotal + $this->gstAmount, 2);

        // Sync INR amounts to public properties for frontend JavaScript access
        $rate = \App\Models\ExchangeRate::getUsdToInrRate();
        $this->subtotalInINR = round($this->subtotal * $rate);
        $this->gstAmountInINR = round($this->gstAmount * $rate);
        $this->totalWithGstInINR = round($this->totalWithGst * $rate);
    }

    /**
     * Toggle streaming addon on/off (for VPS checkout)
     */
    public function toggleStreamingAddon(): void
    {
        $this->hasStreamingAddon = !$this->hasStreamingAddon;
        
        if ($this->hasStreamingAddon && !$this->streamingPlan) {
            // Load default VPS-embedded streaming addon plan
            $this->streamingPlan = \App\Models\StreamingPlan::addon()
                ->vpsEmbedded()
                ->active()
                ->orderBy('addon_price')
                ->first();
            
            if ($this->streamingPlan) {
                $this->streamingPlanId = $this->streamingPlan->id;
                $this->streamingAddonPrice = $this->streamingPlan->addon_price;
                $this->streamingDeliveryMethod = 'vps_embedded';
            }
        }
        
        $this->calculateGst();
    }

    /**
     * Select standalone streaming plan
     */
    public function selectStandaloneStreamingPlan(int $planId): void
    {
        $plan = \App\Models\StreamingPlan::find($planId);
        if ($plan && !$plan->is_addon && $plan->is_active && $plan->delivery_method === 'cloud_hosted') {
            $this->streamingPlan = $plan;
            $this->streamingPlanId = $plan->id;
            $this->amount = $plan->price; // Use standalone plan price
            $this->streamingDeliveryMethod = 'cloud_hosted';
            $this->isStandaloneStreaming = true;
            $this->calculateGst();
        }
    }

    /**
     * Select a specific streaming plan
     */
    public function selectStreamingPlan(int $planId): void
    {
        $plan = \App\Models\StreamingPlan::find($planId);
        if ($plan && $plan->is_addon && $plan->is_active) {
            $this->streamingPlan = $plan;
            $this->streamingPlanId = $plan->id;
            $this->streamingAddonPrice = $plan->addon_price;
            $this->hasStreamingAddon = true;
            $this->calculateGst();
        }
    }

    /**
     * Get available streaming addon plans (for VPS checkout)
     */
    /**
     * Get all available streaming plans (addon + standalone)
     */
    public function getAvailableStreamingPlans(): array
    {
        try {
            return \App\Models\StreamingPlan::active()
                ->orderBy('price')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getAvailableStreamingAddonPlans(): array
    {
        try {
            return \App\Models\StreamingPlan::addon()
                ->vpsEmbedded()
                ->active()
                ->orderBy('addon_price')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get available standalone streaming plans
     */
    public function getAvailableStandaloneStreamingPlans(): array
    {
        try {
            return \App\Models\StreamingPlan::standalone()
                ->cloudHosted()
                ->active()
                ->orderBy('price')
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function updateQuantity(int $qty): void
    {
        $this->quantity = max(1, min(10, $qty));
        $this->calculateGst();
    }

    public function getSubtotalInINR(): float
    {
        return $this->subtotalInINR;
    }

    public function getGstAmountInINR(): float
    {
        return $this->gstAmountInINR;
    }

    public function getTotalWithGstInINR(): float
    {
        return $this->totalWithGstInINR;
    }

    public function getAmountInINR(): float
    {
        $rate = \App\Models\ExchangeRate::getUsdToInrRate();
        return round($this->amount * $rate);
    }
    
    /**
     * Detect user currency based on preference or location
     */
    private function detectCurrency(): string
    {
        // Check user preference if logged in
        if (auth()->check() && auth()->user()->preferred_currency) {
            return auth()->user()->preferred_currency;
        }
        
        // Check session
        if (session()->has('currency')) {
            return session('currency');
        }
        
        // Auto-detect based on IP/country (simplified)
        $country = request()->header('CF-IPCountry') ?? request()->server('HTTP_CF_IPCOUNTRY');
        if ($country === 'IN') {
            return 'INR';
        }
        
        return 'USD';
    }
    
    /**
     * Set currency and update payment gateway
     */
    public function setCurrency(string $currency): void
    {
        if (!in_array($currency, ['USD', 'INR'])) {
            return;
        }
        
        $this->currency = $currency;
        session(['currency' => $currency]);
        
        // Update user preference if logged in
        if (auth()->check()) {
            auth()->user()->update(['preferred_currency' => $currency]);
        }
        
        // Update payment gateway based on currency
        $this->setPaymentGatewayByCurrency();
        
        // Recalculate amounts
        $this->updateAmount();
    }
    
    /**
     * Set payment gateway based on currency
     */
    private function setPaymentGatewayByCurrency(): void
    {
        $settings = Setting::where('group', 'Payment')->pluck('value', 'key');
        
        $razorpayEnabled = ($settings['razorpay_enabled'] ?? '0') === '1';
        $cashfreeEnabled = ($settings['cashfree_enabled'] ?? '0') === '1';
        $paypalEnabled = ($settings['paypal_enabled'] ?? '0') === '1';
        $payuEnabled = ($settings['payu_enabled'] ?? '0') === '1';
        $stripeEnabled = ($settings['stripe_enabled'] ?? '0') === '1';
        
        if ($this->currency === 'INR') {
            // Indian gateways for INR
            if ($razorpayEnabled && !empty($settings['razorpay_key_id'])) {
                $this->paymentGateway = 'razorpay';
            } elseif ($cashfreeEnabled && !empty($settings['cashfree_app_id'])) {
                $this->paymentGateway = 'cashfree';
            } elseif ($payuEnabled && !empty($settings['payu_key'])) {
                $this->paymentGateway = 'payu';
            }
        } else {
            // International gateways for USD
            if ($stripeEnabled && !empty($settings['stripe_key'])) {
                $this->paymentGateway = 'stripe';
            } elseif ($paypalEnabled && !empty($settings['paypal_client_id'])) {
                $this->paymentGateway = 'paypal';
            } elseif ($payuEnabled && !empty($settings['payu_key'])) {
                $this->paymentGateway = 'payu';
            }
        }
    }
    
    /**
     * Toggle currency between USD and INR
     */
    public function toggleCurrency(): void
    {
        $newCurrency = $this->currency === 'USD' ? 'INR' : 'USD';
        $this->setCurrency($newCurrency);
    }
    
    /**
     * Get display price with currency symbol
     */
    public function getDisplayPrice(float $amount): string
    {
        if ($this->currency === 'INR') {
            return '₹' . number_format($this->convertToInr($amount), 0);
        }
        return '$' . number_format($amount, 2);
    }
    
    /**
     * Convert USD to INR using live rate
     */
    private function convertToInr(float $usdAmount): float
    {
        $rate = \App\Models\ExchangeRate::getUsdToInrRate();
        return round($usdAmount * $rate);
    }
    
    public function getBillingCycles(): array
    {
        return $this->service->billing_cycles ?? Service::getDefaultBillingCycles();
    }
    
    public function getMonthlyPrice(): float
    {
        return round($this->amount / $this->billingMonths, 2);
    }
    
    /**
     * Process order completion with streaming deployment
     */
    public function processOrderCompletion(): void
    {
        // This method should be called after successful payment
        if ($this->hasStreamingAddon && $this->streamingDeliveryMethod === 'vps_embedded') {
            $this->deployVpsStreaming();
        } elseif ($this->isStandaloneStreaming && $this->streamingDeliveryMethod === 'cloud_hosted') {
            $this->provisionStandaloneStreaming();
        }
    }

    /**
     * Deploy streaming to client's VPS (Method A)
     */
    private function deployVpsStreaming(): void
    {
        try {
            // Get the VPS hosting that will be created
            $vpsHosting = $this->getOrCreateVpsHosting();
            
            if (!$vpsHosting) {
                throw new \Exception('VPS hosting not found or could not be created');
            }

            // Create streaming subscription linked to VPS
            $streamingSubscription = \App\Models\StreamingSubscription::create([
                'user_id' => auth()->id(),
                'streaming_plan_id' => $this->streamingPlan->id,
                'vps_hosting_id' => $vpsHosting->id,
                'order_id' => $this->getLatestOrderId(),
                'delivery_method' => 'vps_embedded',
                'status' => 'pending_setup',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'price' => $this->streamingAddonPrice,
            ]);

            // Deploy streaming server to VPS IP
            $this->executeVpsDeployment($vpsHosting, $streamingSubscription);
            
            // Update subscription status
            $streamingSubscription->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('VPS Streaming deployment failed: ' . $e->getMessage());
            // Handle deployment failure
        }
    }

    /**
     * Provision standalone streaming on BelieVoo cluster (Method B)
     */
    private function provisionStandaloneStreaming(): void
    {
        try {
            // Create streaming subscription for standalone plan
            $streamingSubscription = \App\Models\StreamingSubscription::create([
                'user_id' => auth()->id(),
                'streaming_plan_id' => $this->streamingPlan->id,
                'order_id' => $this->getLatestOrderId(),
                'delivery_method' => 'cloud_hosted',
                'status' => 'pending_setup',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'price' => $this->streamingPlan->price,
            ]);

            // Provision on BelieVoo master streaming cluster
            $this->executeClusterProvisioning($streamingSubscription);
            
            // Update subscription status
            $streamingSubscription->update([
                'status' => 'active',
                'activated_at' => now(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Standalone streaming provisioning failed: ' . $e->getMessage());
            // Handle provisioning failure
        }
    }

    /**
     * Execute deployment scripts to client VPS
     */
    private function executeVpsDeployment($vpsHosting, $streamingSubscription): void
    {
        $vpsIp = $vpsHosting->ip_address;
        
        // Generate deployment script
        $deploymentScript = $this->generateVpsDeploymentScript($streamingSubscription);
        
        // Execute via SSH or API (implementation depends on your VPS provider)
        // This is a placeholder for actual deployment logic
        \Log::info("Deploying streaming to VPS {$vpsIp} for subscription {$streamingSubscription->id}");
        
        // Example: SSH execution (you would implement this based on your infrastructure)
        // $this->executeSshCommand($vpsIp, $deploymentScript);
    }

    /**
     * Execute provisioning on BelieVoo cluster
     */
    private function executeClusterProvisioning($streamingSubscription): void
    {
        // Provision on master streaming cluster
        \Log::info("Provisioning standalone streaming for subscription {$streamingSubscription->id}");
        
        // This would integrate with your streaming cluster API
        // Example: Create streaming instance, assign RTMP/WebRTC endpoints
    }

    /**
     * Generate VPS deployment script
     */
    private function generateVpsDeploymentScript($streamingSubscription): string
    {
        $plan = $streamingSubscription->plan;
        $subscriptionId = $streamingSubscription->id;
        
        return <<<BASH
#!/bin/bash
# BelieVoo Streaming Server Deployment Script
# Subscription ID: {$subscriptionId}

# Install dependencies
apt update && apt install -y docker docker-compose nginx certbot python3-certbot-nginx

# Create streaming directories
mkdir -p /opt/believoo-streaming/{config,data,logs}

# Generate streaming configuration
cat > /opt/believoo-streaming/config/streaming.conf << 'EOF'
server {
    listen 1935;
    listen [::]:1935;
    chunk_size 4096;
    
    application live {
        live on;
        record off;
        
        # WebRTC support
        push rtmp://127.0.0.1:1935/live;
        
        # HLS output
        hls on;
        hls_path /opt/believoo-streaming/data/hls;
        hls_fragment 3;
        hls_playlist_length 60;
    }
}
EOF

# Create docker-compose file
cat > /opt/believoo-streaming/docker-compose.yml << 'EOF'
version: '3.8'
services:
  streaming:
    image: nginx:alpine
    container_name: believoo-streaming-{$subscriptionId}
    ports:
      - "1935:1935"
      - "8080:80"
    volumes:
      - ./config:/etc/nginx/conf.d
      - ./data:/opt/believoo-streaming/data
      - ./logs:/var/log/nginx
    restart: unless-stopped
EOF

# Start streaming service
cd /opt/believoo-streaming
docker-compose up -d

# Setup SSL (if domain is configured)
# certbot --nginx -d your-domain.com

echo "Streaming server deployed successfully!"
BASH;
    }

    /**
     * Get or create VPS hosting record
     */
    private function getOrCreateVpsHosting()
    {
        // This should return the VPS hosting that was just created
        return \App\Models\UserHosting::where('user_id', auth()->id())
            ->where('service_id', $this->service->id)
            ->where('plan_name', $this->tierName)
            ->latest()
            ->first();
    }

    /**
     * Get latest order ID for this checkout session
     */
    private function getLatestOrderId()
    {
        // Implementation depends on your order creation flow
        return session('latest_order_id') ?? null;
    }

    public function render()
    {
        return view('livewire.checkout', [
            'settings' => Setting::where('group', 'Payment')->pluck('value', 'key'),
            'amountInINR' => $this->getAmountInINR(),
            'billingCycles' => $this->getBillingCycles(),
            'monthlyPrice' => $this->getMonthlyPrice(),
            'subtotalInINR' => $this->getSubtotalInINR(),
            'gstAmountInINR' => $this->getGstAmountInINR(),
            'totalWithGstInINR' => $this->getTotalWithGstInINR(),
            'currency' => $this->currency,
            'exchangeRate' => \App\Models\ExchangeRate::getUsdToInrRate(),
            'streamingPlans' => $this->getAvailableStreamingPlans(),
            'streamingPlan' => $this->streamingPlan,
        ])->layout('components.layouts.believoo');
    }
}
