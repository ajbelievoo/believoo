<div class="py-20 min-h-screen bg-[#0a0a0a]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-white mb-4">Register a Domain</h1>
            <p class="text-lg text-gray-400 max-w-2xl mx-auto">Search available TLDs and register your domain through OVH.</p>
        </div>

        <div class="max-w-3xl mx-auto mb-12">
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search domain, e.g. .com, .in, .net..."
                       class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 pl-14 text-white placeholder-gray-500 focus:outline-none focus:border-[#00B7FF] focus:ring-1 focus:ring-[#00B7FF]">
                <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        @if($domains->isNotEmpty())
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($domains as $product)
                    @php
                        $service = $product->service;
                        if (!$service) continue;
                        $displayPrice = (strtoupper($currentCurrency ?? 'USD') === 'INR') ? $service->price * $rate : $service->price;
                    @endphp
                    <div class="group glass rounded-2xl p-6 border border-white/10 hover:border-[#00B7FF]/50 transition-all">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-white">{{ $product->display_name ?: $product->plan_code }}</h3>
                            <span class="text-xs font-semibold text-[#00B7FF] uppercase">{{ $product->plan_code }}</span>
                        </div>
                        <div class="text-3xl font-black text-[#00B7FF] mb-6">
                            {{ $currencySymbol }}{{ number_format($displayPrice, 2) }}
                            <span class="text-sm font-medium text-gray-500">/yr</span>
                        </div>
                        <a href="{{ route('checkout', ['service' => $service->slug]) }}" class="inline-flex items-center justify-center w-full px-6 py-3 rounded-xl bg-[#00B7FF] text-black font-bold hover:bg-[#00B7FF]/90 transition">
                            Buy Now
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-20">
                <i class="fas fa-globe text-5xl text-gray-600 mb-4"></i>
                <p class="text-gray-400">No domains found. Try a different search.</p>
            </div>
        @endif
    </div>
</div>
