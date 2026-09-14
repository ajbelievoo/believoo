<x-app-layout>
    <div class="min-h-screen bg-dark pt-32 pb-20 px-6">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="{{ route('client.dashboard') }}" class="inline-flex items-center text-electric-blue hover:text-white transition-colors mb-4">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
                <h1 class="text-4xl font-black text-white uppercase tracking-tight mb-2">
                    Upgrade Your <span class="text-electric-blue">Plan</span>
                </h1>
                <p class="text-gray-400">Choose a new plan to upgrade your hosting service.</p>
            </div>

            <!-- Current Plan Card -->
            <div class="glass rounded-3xl border border-white/10 p-8 mb-8">
                <h2 class="text-lg font-black text-electric-blue uppercase tracking-widest mb-4">Current Plan</h2>
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase">{{ $hosting->plan_name }}</h3>
                        <p class="text-gray-400">{{ ucfirst($hosting->hosting_type) }} Hosting</p>
                        <div class="flex items-center gap-4 mt-3">
                            @if($hosting->cpu_cores)
                                <span class="text-sm text-gray-300">{{ $hosting->cpu_cores }} vCores</span>
                            @endif
                            @if($hosting->ram_size)
                                <span class="text-sm text-gray-300">{{ $hosting->ram_size }} RAM</span>
                            @endif
                            @if($hosting->storage_size)
                                <span class="text-sm text-gray-300">{{ $hosting->storage_size }} Storage</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-400">Current Price</p>
                        <p class="text-2xl font-black text-white">{{ $currencySymbol }}{{ number_format($hosting->price, 2) }}<span class="text-sm text-gray-500">/{{ $hosting->billing_cycle }}</span></p>
                        <p class="text-xs text-gray-500 mt-1">Expires: {{ $hosting->expiry_date->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <!-- Available Plans for Upgrade -->
            @if($availableServices->count() > 0)
                <div class="mb-8">
                    <h2 class="text-2xl font-black text-white uppercase tracking-tight mb-6">Available Upgrades</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($availableServices as $service)
                            <div class="glass rounded-3xl border border-white/10 hover:border-electric-blue/30 transition-all p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-black text-white uppercase">{{ $service->title }}</h3>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase bg-electric-blue/10 text-electric-blue">
                                        {{ $service->category }}
                                    </span>
                                </div>
                                
                                @if(!empty($service->pricing_tiers))
                                    <div class="space-y-3 mb-6">
                                        @foreach($service->pricing_tiers as $tier)
                                            <form action="{{ route('upgrade.process', $hosting->id) }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="service_id" value="{{ $service->id }}">
                                                <input type="hidden" name="tier_name" value="{{ $tier['name'] }}">
                                                <input type="hidden" name="billing_months" value="1">
                                                
                                                <div class="p-4 bg-white/5 rounded-xl border border-white/10 hover:border-electric-blue/30 transition-all">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="font-bold text-white">{{ $tier['name'] }}</span>
                                                        <span class="text-electric-blue font-black">{{ $currencySymbol }}{{ $tier['price'] }}/{{ $tier['billing_cycle'] ?? 'mo' }}</span>
                                                    </div>
                                                    @if(isset($tier['features']))
                                                        <ul class="text-xs text-gray-400 space-y-1 mb-3">
                                                            @foreach($tier['features'] as $feature)
                                                                <li class="flex items-center">
                                                                    <i class="fas fa-check text-electric-blue mr-2"></i>{{ $feature }}
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                    <button type="submit" class="w-full py-2 rounded-lg bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                                        Upgrade Now
                                                    </button>
                                                </div>
                                            </form>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mb-6">
                                        <p class="text-2xl font-black text-electric-blue">{{ $currencySymbol }}{{ $service->price }}<span class="text-sm text-gray-500">/{{ $service->price_label ?? 'mo' }}</span></p>
                                    </div>
                                    <form action="{{ route('upgrade.process', $hosting->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="service_id" value="{{ $service->id }}">
                                        <input type="hidden" name="billing_months" value="1">
                                        <button type="submit" class="w-full py-3 rounded-xl bg-electric-blue text-dark font-black uppercase tracking-widest text-xs hover:scale-105 transition-all">
                                            Upgrade to {{ $service->title }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="glass rounded-3xl border border-white/10 p-12 text-center">
                    <i class="fas fa-check-circle text-5xl text-green-500 mb-6"></i>
                    <h3 class="text-2xl font-black text-white uppercase mb-2">No Upgrades Available</h3>
                    <p class="text-gray-400">You already have the highest plan available.</p>
                </div>
            @endif

            <!-- Help Section -->
            <div class="mt-12 glass rounded-3xl border border-white/10 p-6">
                <h3 class="text-lg font-black text-white uppercase tracking-widest mb-3">Need Help?</h3>
                <p class="text-gray-400 text-sm mb-4">Contact our support team if you need assistance with your upgrade.</p>
                <a href="{{ route('client.dashboard') }}" wire:click="$wire.set('activeTab', 'tickets')" class="inline-flex items-center px-6 py-3 rounded-xl bg-white/10 text-white font-black uppercase tracking-widest text-xs hover:bg-white/20 transition-all">
                    <i class="fas fa-headset mr-2"></i>Contact Support
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
