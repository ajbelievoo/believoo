<div class="min-h-screen bg-[#0a0a0a] pt-28 pb-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-10">
            <span class="text-[#00B7FF] font-black uppercase tracking-[0.3em] text-sm mb-3 block">Secure Checkout</span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-black tracking-tighter uppercase text-white">Complete Your <span class="text-[#00B7FF]">Purchase</span></h1>
            
            {{-- Currency Toggle --}}
            <div class="mt-6 inline-flex glass rounded-full p-1">
                <button 
                    type="button"
                    wire:click="setCurrency('USD')"
                    class="px-6 py-2 rounded-full text-sm font-bold uppercase transition-all {{ $currency === 'USD' ? 'bg-[#00B7FF] text-black' : 'text-gray-400 hover:text-white' }}">
                    🇺🇸 USD ($)
                </button>
                <button 
                    type="button"
                    wire:click="setCurrency('INR')"
                    class="px-6 py-2 rounded-full text-sm font-bold uppercase transition-all {{ $currency === 'INR' ? 'bg-[#00B7FF] text-black' : 'text-gray-400 hover:text-white' }}">
                    🇮🇳 INR (₹)
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-sync-alt mr-1"></i> Exchange rate: 1 USD = ₹{{ number_format($exchangeRate, 2) }}
            </p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            <!-- Order Summary -->
            <div class="xl:col-span-7 glass rounded-3xl p-6 lg:p-8 border border-white/10 h-fit">
                <h2 class="text-2xl font-black uppercase mb-6 text-white flex items-center gap-3">
                    <i class="fas fa-shopping-cart text-[#00B7FF]"></i>
                    Order Summary
                </h2>
                
                <div class="border-b border-white/10 pb-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-xl text-white">{{ $service->title }}</h3>
                            @if($tierName)
                                <p class="text-[#00B7FF] text-sm mt-1">{{ $tierName }} Plan</p>
                            @endif
                        </div>
                    </div>

                    <!-- Price Breakdown with GST -->
                    <div class="space-y-3 bg-white/5 rounded-xl p-4">

                        <!-- Quantity Selector -->
                        <div class="flex items-center justify-between pb-3 border-b border-white/10">
                            <span class="text-gray-400 text-sm">Quantity</span>
                            <div class="flex items-center gap-3">
                                <button wire:click="updateQuantity({{ max(1, $quantity - 1) }})"
                                        class="w-8 h-8 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-all flex items-center justify-center font-bold"
                                        {{ $quantity <= 1 ? 'disabled' : '' }}>−</button>
                                <span class="text-white font-black text-lg w-8 text-center">{{ $quantity }}</span>
                                <button wire:click="updateQuantity({{ min(10, $quantity + 1) }})"
                                        class="w-8 h-8 rounded-lg bg-white/10 text-white hover:bg-white/20 transition-all flex items-center justify-center font-bold"
                                        {{ $quantity >= 10 ? 'disabled' : '' }}>+</button>
                            </div>
                        </div>
                        @if($quantity > 1)
                        <div class="flex items-center justify-between text-xs text-gray-500">
                            <span>Unit price</span>
                            <span>{{ $currency === 'INR' ? '₹' . number_format($subtotalInINR / $quantity, 0) : '$' . number_format($subtotal / $quantity, 2) }} × {{ $quantity }}</span>
                        </div>
                        @endif
                        @if($currency === 'INR')
                            <!-- INR Pricing -->
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Subtotal</span>
                                <div class="text-right">
                                    <span class="text-white font-semibold text-lg">₹{{ number_format($subtotalInINR, 0) }}</span>
                                    <span class="text-xs text-gray-500 ml-1 block">(${{ number_format($subtotal, 2) }} USD)</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">GST (18%)</span>
                                <div class="text-right">
                                    <span class="text-[#00B7FF] font-semibold">₹{{ number_format($gstAmountInINR, 0) }}</span>
                                </div>
                            </div>
                            <div class="border-t border-white/10 pt-3 flex items-center justify-between">
                                <span class="text-white font-bold uppercase">Total Payable</span>
                                <div class="text-right">
                                    <span class="text-3xl font-black text-[#00B7FF]">₹{{ number_format($totalWithGstInINR, 0) }}</span>
                                    <p class="text-xs text-gray-500 mt-1">≈ ${{ number_format($totalWithGst, 2) }} USD</p>
                                </div>
                            </div>
                        @else
                            <!-- USD Pricing for International -->
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Subtotal</span>
                                <div class="text-right">
                                    <span class="text-white font-semibold text-lg">${{ number_format($subtotal, 2) }}</span>
                                    <span class="text-xs text-gray-500 ml-1 block">(₹{{ number_format($subtotalInINR, 0) }})</span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">GST (18%)</span>
                                <span class="text-[#00B7FF] font-semibold">${{ number_format($gstAmount, 2) }}</span>
                            </div>
                            <div class="border-t border-white/10 pt-3 flex items-center justify-between">
                                <span class="text-white font-bold uppercase">Total Payable</span>
                                <div class="text-right">
                                    <span class="text-3xl font-black text-[#00B7FF]">${{ number_format($totalWithGst, 2) }}</span>
                                    <p class="text-xs text-gray-500 mt-1">≈ ₹{{ number_format($totalWithGstInINR, 0) }} INR</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Billing Cycle Selection -->
                <div class="border-b border-white/10 pb-6 mb-6">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-4">Select Billing Cycle:</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($billingCycles as $cycle)
                            <button 
                                type="button"
                                wire:click="updateBillingCycle({{ $cycle['months'] }})"
                                class="relative p-5 rounded-xl border text-left transition-all {{ $billingMonths == $cycle['months'] ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 hover:border-white/30' }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="font-bold text-white text-lg">{{ $cycle['label'] }}</div>
                                    <div class="flex flex-col items-end gap-1">
                                        @if($cycle['discount_percent'] > 0)
                                            <span class="bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">SAVE {{ $cycle['discount_percent'] }}%</span>
                                        @endif
                                        @if($cycle['recommended'] ?? false)
                                            <span class="bg-[#00B7FF] text-black text-[10px] font-bold px-2 py-0.5 rounded-full">RECOMMENDED</span>
                                        @endif
                                    </div>
                                </div>
                                @php
                                    // Use VPS plan price from session if available, otherwise fall back to service price
                                    $vpsPlanPriceInr = session('vps_plan_price');
                                    $vpsPlanName = session('vps_plan_name');
                                    $cycleRate = \App\Models\ExchangeRate::getUsdToInrRate();
                                    $discountMultiplier = (1 - ($cycle['discount_percent'] / 100));

                                    if ($vpsPlanPriceInr > 0 && $vpsPlanName === $tierName) {
                                        // VPS plan: price is in INR monthly
                                        $monthlyPriceInr = round($vpsPlanPriceInr * $discountMultiplier);
                                        $cycleSubtotalInr = round($monthlyPriceInr * $cycle['months']);
                                        $cycleGstInr = round($cycleSubtotalInr * 0.18);
                                        $cycleTotalInr = $cycleSubtotalInr + $cycleGstInr;
                                        $monthlyPriceUsd = round($vpsPlanPriceInr / $cycleRate * $discountMultiplier, 2);
                                        $cycleSubtotalUsd = round($monthlyPriceUsd * $cycle['months'], 2);
                                        $cycleGstUsd = round($cycleSubtotalUsd * 0.18, 2);
                                        $cycleTotalUsd = round($cycleSubtotalUsd + $cycleGstUsd, 2);
                                    } else {
                                        // Standard service pricing
                                        $cycleSubtotalUsd = $service->getPriceForCycle($cycle['months'], $tierName);
                                        $cycleGstUsd = round($cycleSubtotalUsd * 0.18, 2);
                                        $cycleTotalUsd = round($cycleSubtotalUsd + $cycleGstUsd, 2);
                                        $cycleTotalInr = round($cycleTotalUsd * $cycleRate);
                                        $monthlyPriceInr = round($service->price * $cycleRate * $discountMultiplier);
                                        $monthlyPriceUsd = round($cycleSubtotalUsd / $cycle['months'], 2);
                                    }
                                @endphp
                                @if($currency === 'INR')
                                    <div class="text-xl font-black text-[#00B7FF]">₹{{ number_format($cycleTotalInr, 0) }}</div>
                                    <div class="text-xs text-gray-500">₹{{ number_format($monthlyPriceInr, 0) }}/mo</div>
                                @else
                                    <div class="text-xl font-black text-[#00B7FF]">${{ number_format($cycleTotalUsd, 2) }}</div>
                                    <div class="text-xs text-gray-500">${{ number_format($monthlyPriceUsd, 2) }}/mo</div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- GST Summary -->
                <div class="bg-[#00B7FF]/10 border border-[#00B7FF]/30 rounded-xl p-4 mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <i class="fas fa-receipt text-[#00B7FF]"></i>
                        <span class="text-white font-bold uppercase text-sm">Bill Summary</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        @if($currency === 'INR')
                            <div class="flex justify-between text-gray-400">
                                <span>Subtotal ({{ $billingMonths }} months)</span>
                                <span>₹{{ number_format($subtotalInINR, 0) }}</span>
                            </div>
                            <div class="flex justify-between text-[#00B7FF]">
                                <span>GST 18%</span>
                                <span>+₹{{ number_format($gstAmountInINR, 0) }}</span>
                            </div>
                            <div class="border-t border-white/10 pt-2 flex justify-between text-white font-bold text-lg">
                                <span>Total Payable</span>
                                <div class="text-right">
                                    <span>₹{{ number_format($totalWithGstInINR, 0) }}</span>
                                    <p class="text-xs text-gray-400 font-normal">(${{ number_format($totalWithGst, 2) }} USD)</p>
                                </div>
                            </div>
                        @else
                            <div class="flex justify-between text-gray-400">
                                <span>Subtotal ({{ $billingMonths }} months)</span>
                                <span>${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-[#00B7FF]">
                                <span>GST 18%</span>
                                <span>+${{ number_format($gstAmount, 2) }}</span>
                            </div>
                            <div class="border-t border-white/10 pt-2 flex justify-between text-white font-bold text-lg">
                                <span>Total Payable</span>
                                <div class="text-right">
                                    <span>${{ number_format($totalWithGst, 2) }}</span>
                                    <p class="text-xs text-gray-500 font-normal">≈ ₹{{ number_format($totalWithGstInINR, 0) }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if(!empty($service->features))
                    <div class="pt-6 border-t border-white/10">
                        <h4 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-4">What's included:</h4>
                        <ul class="space-y-3">
                            @foreach($service->features as $feature)
                                <li class="flex items-center text-gray-300">
                                    <i class="fas fa-check text-[#00B7FF] mr-3"></i>
                                    {{ $feature['feature'] ?? $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Datacenter Location Only - No Stock Info --}}
                @php
                    $defaultNode = \App\Models\ProxmoxNode::where('is_default', true)->first() 
                        ?? \App\Models\ProxmoxNode::where('status', 'active')->first();
                    $allNodes = \App\Models\ProxmoxNode::where('status', 'active')->orWhere('status', 'coming_soon')->get();
                @endphp
                
                <div class="mt-6 pt-6 border-t border-white/10">
                    <h4 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-4 flex items-center gap-2">
                        <i class="fas fa-globe text-[#00B7FF]"></i>
                        Available Datacenters
                    </h4>
                    
                    @foreach($allNodes as $node)
                        <div class="flex items-center justify-between p-4 mb-3 rounded-xl border {{ $node->status === 'active' ? 'border-green-500/30 bg-green-500/5' : 'border-gray-500/30 bg-gray-500/5' }}">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg {{ $node->status === 'active' ? 'bg-green-500/20' : 'bg-gray-500/20' }} flex items-center justify-center text-xl">
                                    {{ $node->flag_emoji }}
                                </div>
                                <div>
                                    <div class="text-white font-semibold">{{ $node->city }}, {{ $node->country_code }}</div>
                                    <div class="text-xs {{ $node->status === 'active' ? 'text-green-400' : 'text-gray-500' }}">
                                        @if($node->status === 'active')
                                            <i class="fas fa-check-circle mr-1"></i>Available Now
                                        @else
                                            <i class="fas fa-clock mr-1"></i>Coming Soon
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if($node->status === 'active')
                                <span class="text-xs bg-green-500/20 text-green-400 px-3 py-1 rounded-full">
                                    Deploy Here
                                </span>
                            @else
                                <span class="text-xs bg-gray-500/20 text-gray-400 px-3 py-1 rounded-full">
                                    Soon
                                </span>
                            @endif
                        </div>
                    @endforeach
                    
                    @if($defaultNode && $defaultNode->status === 'active')
                        <p class="text-xs text-gray-500 mt-3 text-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Your VM will be deployed in {{ $defaultNode->city }} {{ $defaultNode->flag_emoji }}
                        </p>
                    @endif
                </div>

                {{-- BelieVoo Live Streaming Engine - Hybrid Architecture --}}
                @if((str_contains(strtolower($service->category), 'vps') || str_contains(strtolower($service->slug), 'vps')) && count($this->getAvailableStreamingAddonPlans()) > 0)
                <div class="mt-6 pt-6 border-t border-purple-500/30">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-bold uppercase tracking-widest text-purple-400 flex items-center gap-2">
                            <i class="fas fa-broadcast-tower"></i>
                            BelieVoo Live Engine
                        </h4>
                        <span class="px-2 py-1 rounded-full bg-gradient-to-r from-purple-500/20 to-pink-500/20 text-purple-300 text-[10px] font-bold uppercase tracking-wider border border-purple-500/30">
                            Luxury Add-on
                        </span>
                    </div>

                    {{-- Toggle Button --}}
                    <button 
                        type="button"
                        wire:click="toggleStreamingAddon"
                        class="w-full relative p-5 rounded-xl border text-left transition-all {{ $hasStreamingAddon ? 'border-purple-500 bg-gradient-to-r from-purple-500/10 to-pink-500/10' : 'border-white/10 bg-white/5 hover:border-white/30' }}">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl {{ $hasStreamingAddon ? 'bg-gradient-to-br from-purple-500 to-pink-500' : 'bg-white/10' }} flex items-center justify-center transition-all">
                                    <i class="fas {{ $hasStreamingAddon ? 'fa-video text-white' : 'fa-video-slash text-gray-400' }} text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-white {{ $hasStreamingAddon ? 'text-purple-300' : '' }}">
                                        {{ $hasStreamingAddon ? 'Live Streaming Enabled' : 'Add Live Streaming' }}
                                    </p>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        @if($hasStreamingAddon && $streamingPlan)
                                            {{ $streamingPlan->max_viewers }} viewers • {{ $streamingPlan->getFormattedResolution() }} • RTMP/WebRTC
                                        @else
                                            Transform your VPS into a professional streaming server
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center gap-3">
                                    @if($hasStreamingAddon && $streamingPlan)
                                        <span class="text-xl font-black text-purple-400">
                                            @if($currency === 'INR')
                                                +₹{{ number_format($streamingPlan->addon_price * \App\Models\ExchangeRate::getUsdToInrRate(), 0) }}
                                            @else
                                                +${{ number_format($streamingPlan->addon_price, 2) }}
                                            @endif
                                        </span>
                                    @endif
                                    <div class="w-12 h-6 rounded-full {{ $hasStreamingAddon ? 'bg-purple-500' : 'bg-gray-600' }} relative transition-colors">
                                        <div class="absolute {{ $hasStreamingAddon ? 'right-1' : 'left-1' }} top-1 w-4 h-4 rounded-full bg-white transition-all"></div>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500">/month</span>
                            </div>
                        </div>
                    </button>

                    {{-- Streaming Plan Selection (shown when enabled) --}}
                    @if($hasStreamingAddon && count($this->getAvailableStreamingAddonPlans()) > 1)
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($this->getAvailableStreamingAddonPlans() as $plan)
                        <button 
                            type="button"
                            wire:click="selectStreamingPlan({{ $plan['id'] }})"
                            class="p-4 rounded-xl border text-left transition-all {{ $streamingPlanId === $plan['id'] ? 'border-purple-500 bg-purple-500/10' : 'border-white/10 bg-white/5 hover:border-white/30' }}">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-bold text-white text-sm">{{ $plan['name'] }}</span>
                                @if($streamingPlanId === $plan['id'])
                                    <i class="fas fa-check-circle text-purple-400"></i>
                                @endif
                            </div>
                            <p class="text-xs text-gray-400">{{ $plan['max_viewers'] }} viewers • {{ $plan['bandwidth_gb'] }}GB/mo</p>
                            <p class="text-sm font-black text-purple-400 mt-2">
                                @if($currency === 'INR')
                                    +₹{{ number_format($plan['addon_price'] * \App\Models\ExchangeRate::getUsdToInrRate(), 0) }}/mo
                                @else
                                    +${{ number_format($plan['addon_price'], 2) }}/mo
                                @endif
                            </p>
                        </button>
                        @endforeach
                    </div>
                    @endif

                    {{-- Features list when enabled --}}
                    @if($hasStreamingAddon && $streamingPlan)
                    <div class="mt-4 p-4 bg-purple-500/5 rounded-xl border border-purple-500/20">
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> {{ $streamingPlan['max_viewers'] ?? $streamingPlan->max_viewers }} Concurrent Viewers
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> {{ $streamingPlan['max_resolution'] ?? $streamingPlan->max_resolution }}p Max Resolution
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> RTMP Ingest Support
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> WebRTC Low Latency
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> {{ $streamingPlan['bandwidth_gb'] ?? $streamingPlan->bandwidth_gb }}GB Bandwidth
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> HLS Playback URL
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Standalone Streaming Plans (for dedicated streaming service) --}}
                @if($isStandaloneStreaming && count($this->getAvailableStandaloneStreamingPlans()) > 0)
                <div class="mt-6 pt-6 border-t border-blue-500/30">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-bold uppercase tracking-widest text-blue-400 flex items-center gap-2">
                            <i class="fas fa-cloud"></i>
                            Dedicated Streaming Plans
                        </h4>
                        <span class="px-2 py-1 rounded-full bg-gradient-to-r from-blue-500/20 to-cyan-500/20 text-blue-300 text-[10px] font-bold uppercase tracking-wider border border-blue-500/30">
                            Cloud-Hosted
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($this->getAvailableStandaloneStreamingPlans() as $plan)
                        <button 
                            type="button"
                            wire:click="selectStandaloneStreamingPlan({{ $plan['id'] }})"
                            class="p-5 rounded-xl border text-left transition-all {{ $streamingPlanId === $plan['id'] ? 'border-blue-500 bg-blue-500/10' : 'border-white/10 bg-white/5 hover:border-white/30' }}">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <span class="font-bold text-white">{{ $plan['name'] }}</span>
                                    @if($plan['max_viewers'] >= 999999)
                                        <span class="ml-2 px-2 py-0.5 bg-gradient-to-r from-yellow-500/20 to-orange-500/20 text-yellow-300 text-[8px] font-bold uppercase rounded-full border border-yellow-500/30">
                                            Unlimited
                                        </span>
                                    @endif
                                </div>
                                @if($streamingPlanId === $plan['id'])
                                    <i class="fas fa-check-circle text-blue-400"></i>
                                @endif
                            </div>
                            
                            <div class="space-y-2 mb-3">
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <i class="fas fa-users text-blue-400"></i>
                                    @if($plan['max_viewers'] >= 999999)
                                        Unlimited Viewers
                                    @else
                                        {{ $plan['max_viewers'] }} Concurrent Viewers
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <i class="fas fa-hdd text-blue-400"></i>
                                    @if($plan['bandwidth_gb'] >= 999999)
                                        Unlimited Bandwidth
                                    @else
                                        {{ $plan['bandwidth_gb'] }}GB Bandwidth/mo
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                    <i class="fas fa-server text-blue-400"></i>
                                    Cloud-Hosted on BelieVoo Cluster
                                </div>
                            </div>

                            <div class="flex items-center justify-between pt-3 border-t border-white/10">
                                <div class="text-left">
                                    <p class="text-2xl font-black text-blue-400">
                                        @if($currency === 'INR')
                                            ₹{{ number_format($plan['price'] * \App\Models\ExchangeRate::getUsdToInrRate(), 0) }}
                                        @else
                                            ${{ number_format($plan['price'], 2) }}
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-500">per month</p>
                                </div>
                                <div class="text-right">
                                    @if($plan['max_viewers'] >= 999999)
                                        <span class="text-xs bg-yellow-500/20 text-yellow-300 px-2 py-1 rounded-full">
                                            Unlimited Capacity
                                        </span>
                                    @else
                                        <span class="text-xs bg-blue-500/20 text-blue-300 px-2 py-1 rounded-full">
                                            {{ $plan['max_viewers'] }} Viewers
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </button>
                        @endforeach
                    </div>

                    {{-- Selected Plan Features --}}
                    @if($streamingPlan && $streamingPlanId && $isStandaloneStreaming)
                    <div class="mt-6 p-5 bg-blue-500/5 rounded-xl border border-blue-500/20">
                        <h5 class="font-bold text-white mb-4 flex items-center gap-2">
                            <i class="fas fa-check-circle text-blue-400"></i>
                            Selected: {{ $streamingPlan->name }}
                        </h5>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> 
                                @if($streamingPlan->max_viewers >= 999999)
                                    Unlimited Viewers
                                @else
                                    {{ $streamingPlan->max_viewers }} Concurrent Viewers
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> {{ $streamingPlan->getFormattedResolution() }}
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> RTMP & WebRTC
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> HLS & DASH Playback
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> 
                                @if($streamingPlan->bandwidth_gb >= 999999)
                                    Unlimited Bandwidth
                                @else
                                    {{ $streamingPlan->bandwidth_gb }}GB Bandwidth
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-check text-green-400"></i> 
                                @if($streamingPlan->transcoding_enabled)
                                    Live Transcoding
                                @else
                                    Recording Only
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            {{-- End BelieVoo Live Streaming Engine --}}
            @endif

            <!-- Payment Section -->
            <div class="xl:col-span-5 glass rounded-3xl p-6 lg:p-8 border border-white/10 h-fit">
                @if($service->ovhProduct && in_array(strtoupper($service->ovhProduct->category), ['DOMAINS', 'DOMAIN']))
                    <div class="mb-6">
                        <h2 class="text-2xl font-black uppercase mb-4 text-white flex items-center gap-3">
                            <i class="fas fa-globe text-[#00B7FF]"></i>
                            Domain Name
                        </h2>
                        <label for="ovh-domain" class="block text-sm font-medium text-gray-400 mb-2">Enter the domain you want to register</label>
                        <input type="text" id="ovh-domain" name="ovh-domain" placeholder="example.com"
                               class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-[#00B7FF] focus:ring-1 focus:ring-[#00B7FF]"
                               value="{{ old('ovh-domain', $service->ovhProduct->plan_code) }}">
                    </div>
                @endif

                <h2 class="text-2xl font-black uppercase mb-6 text-white flex items-center gap-3">
                    <i class="fas fa-credit-card text-[#00B7FF]"></i>
                    Payment Method
                </h2>

                @if($amount <= 0)
                    <div class="bg-red-500/10 border border-red-500/30 rounded-2xl p-6 mb-4">
                        <p class="text-red-400"><i class="fas fa-exclamation-circle mr-2"></i> This service is not available for purchase. Please contact support.</p>
                    </div>
                @else
                    @php
                        $razorpayEnabled = ($settings['razorpay_enabled'] ?? '0') === '1';
                        $cashfreeEnabled = ($settings['cashfree_enabled'] ?? '0') === '1';
                        $paypalEnabled = ($settings['paypal_enabled'] ?? '0') === '1';
                        $payuEnabled = ($settings['payu_enabled'] ?? '0') === '1';
                        $stripeEnabled = ($settings['stripe_enabled'] ?? '0') === '1';
                        $cashfreeLive = ($settings['cashfree_mode'] ?? 'sandbox') === 'production';
                        $walletBalance = (float) (Auth::user()->wallet_balance ?? 0);
                    @endphp

                    <!-- Payment Gateway Selection -->
                    <div class="space-y-4 mb-8">
                        <label class="flex items-center p-5 rounded-2xl cursor-pointer transition-all border-2 {{ $paymentGateway === 'wallet' ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                            <input type="radio" wire:model.live="paymentGateway" value="wallet" class="sr-only">
                            <div class="flex items-center flex-1">
                                <div class="w-12 h-12 rounded-xl bg-emerald-500/20 flex items-center justify-center mr-4">
                                    <i class="fas fa-coins text-2xl text-emerald-400"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-white text-lg">BelieVoo Wallet</p>
                                    <p class="text-sm text-gray-400">Available balance: ₹{{ number_format($walletBalance, 2) }}</p>
                                </div>
                            </div>
                            @if($paymentGateway === 'wallet')
                                <i class="fas fa-check-circle text-2xl text-[#00B7FF]"></i>
                            @endif
                        </label>

                        @if($razorpayEnabled && !empty($settings['razorpay_key_id']) && !empty($settings['razorpay_key_secret']))
                            <label class="flex items-center p-5 rounded-2xl cursor-pointer transition-all border-2 {{ $paymentGateway === 'razorpay' ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                                <input type="radio" wire:model.live="paymentGateway" value="razorpay" class="sr-only">
                                <div class="flex items-center flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center mr-4">
                                        <i class="fas fa-wallet text-2xl text-blue-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-lg">Razorpay</p>
                                        <p class="text-sm text-gray-400">Credit/Debit Card, UPI, NetBanking</p>
                                    </div>
                                </div>
                                @if($paymentGateway === 'razorpay')
                                    <i class="fas fa-check-circle text-2xl text-[#00B7FF]"></i>
                                @endif
                            </label>
                        @endif

                        @if($cashfreeEnabled && $cashfreeLive && !empty($settings['cashfree_app_id']) && !empty($settings['cashfree_secret_key']))
                            <label class="flex items-center p-5 rounded-2xl cursor-pointer transition-all border-2 {{ $paymentGateway === 'cashfree' ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                                <input type="radio" wire:model.live="paymentGateway" value="cashfree" class="sr-only">
                                <div class="flex items-center flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center mr-4">
                                        <i class="fas fa-money-bill-wave text-2xl text-purple-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-lg">Cashfree</p>
                                        <p class="text-sm text-gray-400">Card, UPI, Wallet, EMI</p>
                                    </div>
                                </div>
                                @if($paymentGateway === 'cashfree')
                                    <i class="fas fa-check-circle text-2xl text-[#00B7FF]"></i>
                                @endif
                            </label>
                        @endif

                        @if($paypalEnabled && !empty($settings['paypal_client_id']) && !empty($settings['paypal_client_secret']))
                            <label class="flex items-center p-5 rounded-2xl cursor-pointer transition-all border-2 {{ $paymentGateway === 'paypal' ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                                <input type="radio" wire:model.live="paymentGateway" value="paypal" class="sr-only">
                                <div class="flex items-center flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-blue-600/20 flex items-center justify-center mr-4">
                                        <i class="fab fa-paypal text-2xl text-blue-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-lg">PayPal</p>
                                        <p class="text-sm text-gray-400">PayPal, Credit/Debit Card (International)</p>
                                    </div>
                                </div>
                                @if($paymentGateway === 'paypal')
                                    <i class="fas fa-check-circle text-2xl text-[#00B7FF]"></i>
                                @endif
                            </label>
                        @endif

                        @if($payuEnabled && !empty($settings['payu_key']) && !empty($settings['payu_salt']))
                            <label class="flex items-center p-5 rounded-2xl cursor-pointer transition-all border-2 {{ $paymentGateway === 'payu' ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                                <input type="radio" wire:model.live="paymentGateway" value="payu" class="sr-only">
                                <div class="flex items-center flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center mr-4">
                                        <i class="fas fa-credit-card text-2xl text-orange-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-lg">PayU</p>
                                        <p class="text-sm text-gray-400">Card, UPI, NetBanking, Wallets</p>
                                    </div>
                                </div>
                                @if($paymentGateway === 'payu')
                                    <i class="fas fa-check-circle text-2xl text-[#00B7FF]"></i>
                                @endif
                            </label>
                        @endif

                        {{-- Stripe Gateway --}}
                        @if($stripeEnabled && !empty($settings['stripe_key']) && !empty($settings['stripe_secret']))
                            <label class="flex items-center p-5 rounded-2xl cursor-pointer transition-all border-2 {{ $paymentGateway === 'stripe' ? 'border-[#00B7FF] bg-[#00B7FF]/10' : 'border-white/10 bg-white/5 hover:bg-white/10' }}">
                                <input type="radio" wire:model.live="paymentGateway" value="stripe" class="sr-only">
                                <div class="flex items-center flex-1">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-500/20 flex items-center justify-center mr-4">
                                        <i class="fab fa-stripe text-2xl text-indigo-400"></i>
                                    </div>
                                    <div>
                                        <p class="font-bold text-white text-lg">Stripe</p>
                                        <p class="text-sm text-gray-400">Credit/Debit Card (International)</p>
                                    </div>
                                </div>
                                @if($paymentGateway === 'stripe')
                                    <i class="fas fa-check-circle text-2xl text-[#00B7FF]"></i>
                                @endif
                            </label>
                        @endif

                        @php
                            $anyGatewayUsable =
                                ($razorpayEnabled && !empty($settings['razorpay_key_id']) && !empty($settings['razorpay_key_secret'])) ||
                                ($cashfreeEnabled && $cashfreeLive && !empty($settings['cashfree_app_id']) && !empty($settings['cashfree_secret_key'])) ||
                                ($paypalEnabled && !empty($settings['paypal_client_id']) && !empty($settings['paypal_client_secret'])) ||
                                ($payuEnabled && !empty($settings['payu_key']) && !empty($settings['payu_salt'])) ||
                                ($stripeEnabled && !empty($settings['stripe_key']) && !empty($settings['stripe_secret']));
                        @endphp
                        @if(!$anyGatewayUsable)
                            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-6">
                                <p class="text-yellow-400">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Online card/UPI payment is temporarily limited. Please pay with your wallet balance or contact support at {{ $settings['support_email'] ?? 'support@believoo.com' }} to complete your purchase.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Pay Button -->
                    <button
                        id="payBtn"
                        onclick="initiatePayment()"
                        class="w-full bg-[#00B7FF] text-black font-black py-5 px-8 rounded-2xl hover:scale-105 transition-all uppercase tracking-widest text-lg shadow-[0_0_30px_rgba(0,183,255,0.3)] flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span id="btnText">
                            @if($currency === 'INR')
                                Pay ₹{{ number_format($totalWithGstInINR, 0) }}
                            @else
                                Pay ${{ number_format($totalWithGst, 2) }}
                            @endif
                        </span>
                        <svg id="btnSpinner" class="animate-spin ml-3 h-6 w-6 hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>

                    <p class="mt-6 text-center text-sm text-gray-500">
                        <i class="fas fa-lock mr-2"></i>
                        Secure payment powered by <span class="text-[#00B7FF]">{{ ucfirst($paymentGateway) }}</span>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Payment Scripts — load all enabled SDKs so users can switch gateways without reload -->
    @if(!empty($settings['razorpay_key_id']))
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    @endif
    @if(!empty($settings['cashfree_app_id']))
        <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    @endif
    @if(!empty($settings['paypal_client_id']))
        <script src="https://www.paypal.com/sdk/js?client-id={{ $settings['paypal_client_id'] }}&currency=USD"></script>
    @endif
    @if(!empty($settings['stripe_key']))
        <script src="https://js.stripe.com/v3/"></script>
    @endif

    <script>
        const serviceId = {{ $service->id }};
        const tierName = '{{ $tierName }}';
        let amount = {{ $totalWithGst }};
        let billingMonths = {{ $billingMonths }};
        let quantity = {{ $quantity }};
        let paymentGateway = '{{ $paymentGateway }}';
        const csrfToken = '{{ csrf_token() }}';
        const ovhDomain = document.getElementById('ovh-domain')?.value || '';

        // Sync with Livewire updates
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('element.updated', (el, component) => {
                if (component.component.name === 'checkout') {
                    billingMonths = component.$wire.billingMonths;
                    amount = component.$wire.totalWithGst;
                    quantity = component.$wire.quantity;
                }
            });
        });

        function setLoading(loading) {
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            const payBtn = document.getElementById('payBtn');

            if (loading) {
                btnText.textContent = 'Processing...';
                btnSpinner.classList.remove('hidden');
                payBtn.disabled = true;
            } else {
                // Get fresh values
                const livewireComponent = @this;
                const currentAmount = livewireComponent.totalWithGst;
                const currentGateway = livewireComponent.paymentGateway;
                const amountInINR = livewireComponent.totalWithGstInINR;

                if (currentGateway === 'cashfree' || currentGateway === 'razorpay' || currentGateway === 'payu' || currentGateway === 'wallet') {
                    btnText.textContent = 'Pay ₹' + Math.round(amountInINR).toLocaleString('en-IN');
                } else {
                    btnText.textContent = 'Pay $' + currentAmount.toFixed(2);
                }
                btnSpinner.classList.add('hidden');
                payBtn.disabled = false;
            }
        }

        function initiatePayment() {
            setLoading(true);

            // Get fresh values from Livewire
            const livewireComponent = @this;
            billingMonths = livewireComponent.billingMonths;
            amount = livewireComponent.totalWithGst;
            paymentGateway = livewireComponent.paymentGateway;

            if (paymentGateway === 'razorpay') {
                createRazorpayOrder();
            } else if (paymentGateway === 'cashfree') {
                createCashfreeOrder();
            } else if (paymentGateway === 'paypal') {
                createPaypalOrder();
            } else if (paymentGateway === 'payu') {
                createPayuOrder();
            } else if (paymentGateway === 'wallet') {
                payWithWallet();
            }
        }

        function payWithWallet() {
            const livewireComponent = @this;
            const amountInINR = livewireComponent.totalWithGstInINR;

            fetch('{{ route('payment.wallet.pay') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    domain: ovhDomain,
                    tier_name: tierName,
                    amount: amountInINR,
                    billing_months: billingMonths,
                    quantity: quantity,
                }),
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);

                if (data.error) {
                    alert(data.error);
                    return;
                }

                window.location.href = data.redirect_url;
            })
            .catch(error => {
                setLoading(false);
                console.error('Error:', error);
                alert('Failed to pay with wallet. Please try again.');
            });
        }

        function createRazorpayOrder() {
            fetch('{{ route('payment.razorpay.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    domain: ovhDomain,
                    tier_name: tierName,
                    amount: amount,
                    billing_months: billingMonths,
                }),
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);
                
                if (data.error) {
                    alert(data.error);
                    return;
                }

                const options = {
                    key: data.key_id,
                    amount: data.amount,
                    currency: data.currency,
                    order_id: data.order_id,
                    name: data.name,
                    description: data.description,
                    prefill: data.prefill,
                    notes: data.notes,
                    handler: function(response) {
                        // Submit form with payment details
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route('payment.razorpay.callback') }}';
                        
                        const csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = '_token';
                        csrfInput.value = csrfToken;
                        form.appendChild(csrfInput);

                        const razorpayOrderId = document.createElement('input');
                        razorpayOrderId.type = 'hidden';
                        razorpayOrderId.name = 'razorpay_order_id';
                        razorpayOrderId.value = response.razorpay_order_id;
                        form.appendChild(razorpayOrderId);

                        const razorpayPaymentId = document.createElement('input');
                        razorpayPaymentId.type = 'hidden';
                        razorpayPaymentId.name = 'razorpay_payment_id';
                        razorpayPaymentId.value = response.razorpay_payment_id;
                        form.appendChild(razorpayPaymentId);

                        const razorpaySignature = document.createElement('input');
                        razorpaySignature.type = 'hidden';
                        razorpaySignature.name = 'razorpay_signature';
                        razorpaySignature.value = response.razorpay_signature;
                        form.appendChild(razorpaySignature);

                        document.body.appendChild(form);
                        form.submit();
                    },
                    theme: {
                        color: '#4F46E5',
                    },
                };

                const rzp = new Razorpay(options);
                rzp.open();
                
                rzp.on('payment.failed', function(response) {
                    alert('Payment failed: ' + response.error.description);
                });
            })
            .catch(error => {
                setLoading(false);
                console.error('Error:', error);
                alert('Failed to initiate payment. Please try again.');
            });
        }

        function createCashfreeOrder() {
            // Get fresh Livewire values
            const livewireComponent = @this;
            // Use exact INR amount from backend public property
            const amountInINR = livewireComponent.totalWithGstInINR;

            fetch('{{ route('payment.cashfree.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    domain: ovhDomain,
                    tier_name: tierName,
                    amount: amountInINR,
                    billing_months: billingMonths,
                }),
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);

                console.log('Cashfree response:', data);

                if (data.error) {
                    alert(data.error);
                    return;
                }

                if (!data.payment_session_id) {
                    console.error('No payment_session_id in response:', data);
                    alert('Payment session ID not received. Please try again.');
                    return;
                }

                // Initialize Cashfree (v3 SDK: no 'new' keyword)
                if (typeof Cashfree === 'undefined') {
                    console.error('Cashfree SDK not loaded');
                    alert('Cashfree SDK failed to load. Please refresh the page.');
                    return;
                }

                try {
                    const cashfree = Cashfree({
                        mode: '{{ ($settings['cashfree_mode'] ?? 'sandbox') === 'production' ? 'production' : 'sandbox' }}'
                    });

                    const checkoutOptions = {
                        paymentSessionId: data.payment_session_id,
                        redirectTarget: '_self'
                    };

                    console.log('Cashfree checkout options:', checkoutOptions);

                    cashfree.checkout(checkoutOptions);
                } catch (sdkError) {
                    setLoading(false);
                    console.error('Cashfree SDK error:', sdkError);
                    alert('Cashfree error: ' + (sdkError.message || 'Unknown error'));
                }
            })
            .catch(error => {
                setLoading(false);
                console.error('Fetch/Parse error:', error);
                alert('Failed to initiate payment: ' + (error.message || 'Network error'));
            });
        }

        function createPaypalOrder() {
            fetch('{{ route('payment.paypal.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    domain: ovhDomain,
                    tier_name: tierName,
                    amount: amount,
                    billing_months: billingMonths,
                }),
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);

                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Redirect to PayPal approval URL
                if (data.approval_url) {
                    window.location.href = data.approval_url;
                } else {
                    alert('Failed to get PayPal approval URL. Please try again.');
                }
            })
            .catch(error => {
                setLoading(false);
                console.error('Error:', error);
                alert('Failed to initiate PayPal payment. Please try again.');
            });
        }

        function createPayuOrder() {
            // Get fresh Livewire values
            const livewireComponent = @this;
            const amountInINR = livewireComponent.totalWithGstInINR;

            fetch('{{ route('payment.payu.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    domain: ovhDomain,
                    tier_name: tierName,
                    amount: amount,
                    billing_months: billingMonths,
                }),
            })
            .then(response => response.json())
            .then(data => {
                setLoading(false);

                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Create and submit PayU form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = data.base_url + '/_payment';

                const fields = {
                    key: data.key,
                    txnid: data.txnid,
                    amount: data.amount,
                    productinfo: data.productinfo,
                    firstname: data.firstname,
                    email: data.email,
                    phone: data.phone,
                    surl: data.surl,
                    furl: data.furl,
                    hash: data.hash,
                };

                for (const [key, value] of Object.entries(fields)) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                }

                document.body.appendChild(form);
                form.submit();
            })
            .catch(error => {
                setLoading(false);
                console.error('Error:', error);
                alert('Failed to initiate PayU payment. Please try again.');
            });
        }
    </script>
</div>
