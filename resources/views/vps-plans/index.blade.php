<x-layouts.believoo
    title="VPS Cloud Hosting in India - Believoo"
    description="High-performance VPS hosting in India with NVMe SSD storage, DDoS protection, 99.9% uptime and expert support. Buy cheap cloud VPS plans at Believoo."
    keywords="VPS hosting India, cloud VPS, cheap VPS, NVMe VPS, DDoS protection, Believoo">

    
    {{-- Hero Section --}}
    <section class="pt-32 pb-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-4xl sm:text-5xl font-bold text-white mb-6">
                Virtual Private Servers
            </h1>
            <p class="text-lg text-gray-400 max-w-2xl mx-auto">
                High-performance VPS hosting with NVMe SSD storage, DDoS protection, and 99.9% uptime guarantee.
            </p>
            
            {{-- Currency Toggle --}}
            <div class="inline-flex bg-dark-2 rounded-full p-1 mt-6 border border-white/10">
                @php
                    $userCurrency = session('currency', $currentCurrency ?? 'INR');
                    $rate = \App\Models\ExchangeRate::getUsdToInrRate();
                @endphp
                <button 
                    onclick="window.location.href='{{ request()->fullUrlWithQuery(['currency' => 'USD']) }}'"
                    class="px-6 py-2 rounded-full text-sm font-bold uppercase transition-all {{ $userCurrency === 'USD' ? 'bg-electric-blue text-white' : 'text-gray-400 hover:text-white' }}">
                    🇺🇸 USD ($)
                </button>
                <button 
                    onclick="window.location.href='{{ request()->fullUrlWithQuery(['currency' => 'INR']) }}'"
                    class="px-6 py-2 rounded-full text-sm font-bold uppercase transition-all {{ $userCurrency === 'INR' ? 'bg-electric-blue text-white' : 'text-gray-400 hover:text-white' }}">
                    🇮🇳 INR (₹)
                </button>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-sync-alt mr-1"></i> 1 USD = ₹{{ number_format($rate, 2) }} • Updated hourly
            </p>
        </div>
    </section>

    {{-- Categories --}}
    <section class="py-8 border-y border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap justify-center gap-4">
                @foreach($categories as $catKey => $catInfo)
                    @if($catInfo['count'] > 0)
                    <a href="{{ route('vps-plans.category', array_merge(['category' => $catKey], request('currency') ? ['currency' => request('currency')] : [])) }}" 
                       class="px-6 py-3 rounded-xl bg-dark-2 border border-white/10 hover:border-electric-blue/50 hover:bg-dark-3 transition-all text-gray-300 hover:text-white flex items-center gap-2">
                        <i class="fas {{ $catInfo['icon'] }} text-electric-blue"></i>
                        <span>{{ $catInfo['name'] }}</span>
                        <span class="text-sm text-gray-500">({{ $catInfo['count'] }})</span>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    {{-- Plans Section --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            
            @foreach($plansByCategory as $categoryKey => $categoryData)
                @if($categoryData['plans']->count() > 0)
                <div class="mb-16">
                    {{-- Category Header --}}
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-electric-blue/10 flex items-center justify-center">
                                <i class="fas {{ $categoryData['info']['icon'] }} text-electric-blue text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-white">{{ $categoryData['info']['name'] }}</h2>
                                <p class="text-sm text-gray-400">{{ $categoryData['info']['description'] }}</p>
                            </div>
                        </div>
                        <a href="{{ route('vps-plans.category', array_merge(['category' => $categoryKey], request('currency') ? ['currency' => request('currency')] : [])) }}" 
                           class="text-electric-blue hover:text-white transition-colors flex items-center gap-1 text-sm">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>

                    {{-- Plans Grid --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($categoryData['plans']->take(3) as $plan)
                        <div class="bg-dark-2 border border-white/10 rounded-2xl overflow-hidden hover:border-electric-blue/30 transition-all group relative">
                            
                            @if($plan->is_recommended)
                            <div class="bg-gradient-to-r from-electric-blue to-blue-600 text-white text-center py-2 text-xs font-semibold">
                                <i class="fas fa-star mr-1"></i> RECOMMENDED
                            </div>
                            @endif

                            @if($plan->is_sold_out)
                            <div class="absolute inset-0 bg-dark/80 flex items-center justify-center z-10">
                                <span class="bg-red-500 text-white px-6 py-2 rounded-full font-bold transform -rotate-12 border-2 border-white">
                                    SOLD OUT
                                </span>
                            </div>
                            @endif

                            <div class="p-6 {{ $plan->is_recommended ? '' : 'pt-8' }}">
                                <h3 class="text-xl font-bold text-white mb-1">{{ $plan->name }}</h3>
                                <p class="text-gray-400 text-sm mb-4">{{ $plan->display_name }}</p>
                                
                                {{-- Price --}}
                                <div class="mb-6 pb-6 border-b border-white/10">
                                    <div class="text-gray-400 text-sm">Starting from</div>
                                    <div class="flex items-baseline gap-1">
                                        @php
                                            // price_monthly is in INR (as shown in admin panel)
                                            $priceInr = $plan->price_monthly ?? 0;
                                            // Convert to USD using exchange rate
                                            $priceUsd = $priceInr / $rate;
                                            
                                            if ($userCurrency === 'INR') {
                                                $displayPrice = round($priceInr);
                                                $priceSymbol = '₹';
                                                $priceFormatted = number_format($displayPrice, 0);
                                            } else {
                                                $displayPrice = $priceUsd;
                                                $priceSymbol = '$';
                                                $priceFormatted = number_format($displayPrice, 2);
                                            }
                                        @endphp
                                        <span class="text-3xl font-bold text-white">{{ $priceSymbol }}{{ $priceFormatted }}</span>
                                        <span class="text-gray-400 text-sm">/mo</span>
                                    </div>
                                    {{-- PAYU-REVIEW: secondary USD equivalent hidden during review
                                    @if($userCurrency === 'INR')
                                        <span class="text-xs text-gray-500">(${{ number_format($priceUsd, 2) }} USD)</span>
                                    @else
                                        <span class="text-xs text-gray-500">(₹{{ number_format($priceInr, 0) }} INR)</span>
                                    @endif
                                    --}}
                                    @if($userCurrency !== 'INR')
                                        <span class="text-xs text-gray-500">(₹{{ number_format($priceInr, 0) }} INR)</span>
                                    @endif
                                    @if($plan->installation_free)
                                    <div class="text-green-400 text-xs mt-1"><i class="fas fa-check mr-1"></i>Free setup</div>
                                    @endif
                                </div>

                                {{-- Specs --}}
                                <ul class="space-y-3 mb-6">
                                    <li class="flex items-center gap-3 text-gray-300 text-sm">
                                        <i class="fas fa-microchip text-electric-blue w-5"></i>
                                        {{ $plan->cpu_cores }} vCores
                                    </li>
                                    <li class="flex items-center gap-3 text-gray-300 text-sm">
                                        <i class="fas fa-memory text-electric-blue w-5"></i>
                                        {{ $plan->memory_gb }} GB RAM
                                    </li>
                                    <li class="flex items-center gap-3 text-gray-300 text-sm">
                                        <i class="fas fa-hdd text-electric-blue w-5"></i>
                                        {{ $plan->disk_gb }} GB {{ $plan->disk_type }}
                                    </li>
                                    <li class="flex items-center gap-3 text-gray-300 text-sm">
                                        <i class="fas fa-shield-alt text-electric-blue w-5"></i>
                                        Daily Backup
                                    </li>
                                    <li class="flex items-center gap-3 text-gray-300 text-sm">
                                        <i class="fas fa-wifi text-electric-blue w-5"></i>
                                        {{ $plan->bandwidth }}
                                    </li>
                                </ul>

                                {{-- Button --}}
                                @if($plan->is_sold_out)
                                    <button disabled class="w-full py-3 bg-gray-600 text-gray-400 rounded-xl font-medium cursor-not-allowed">
                                        Unavailable
                                    </button>
                                @else
                                    <a href="{{ route('vps-plans.configure', $plan->slug) }}" 
                                       class="block w-full py-3 bg-electric-blue hover:bg-blue-600 text-white text-center rounded-xl font-medium transition-colors">
                                        Configure
                                    </a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </section>

    {{-- Features --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8 border-t border-white/5">
        <div class="max-w-7xl mx-auto">
            <h2 class="text-2xl font-bold text-white text-center mb-12">Why Choose Our VPS?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-electric-blue/10 flex items-center justify-center">
                        <i class="fas fa-bolt text-electric-blue text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">NVMe SSD</h3>
                    <p class="text-gray-400 text-sm">10x faster than regular SSD storage</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-electric-blue/10 flex items-center justify-center">
                        <i class="fas fa-shield-alt text-electric-blue text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">DDoS Protection</h3>
                    <p class="text-gray-400 text-sm">Free protection against attacks</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-electric-blue/10 flex items-center justify-center">
                        <i class="fas fa-undo text-electric-blue text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Daily Backups</h3>
                    <p class="text-gray-400 text-sm">Automatic daily snapshots included</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-electric-blue/20 to-blue-600/20">
        <div class="max-w-3xl mx-auto text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to Get Started?</h2>
            <p class="text-gray-400 mb-8">Deploy your VPS in minutes with our instant provisioning</p>
            <a href="{{ route('vps-plans.category', 'vps_2026') }}" 
               class="inline-flex items-center gap-2 px-8 py-4 bg-electric-blue hover:bg-blue-600 text-white rounded-xl font-semibold transition-colors">
                <i class="fas fa-rocket"></i> Browse All Plans
            </a>
        </div>
    </section>

</x-layouts.believoo>
