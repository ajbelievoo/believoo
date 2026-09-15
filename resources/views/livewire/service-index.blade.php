@php
    $whatsappNumber = \App\Models\Setting::getValue('whatsapp');
@endphp

<div class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">Our Services</h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">VPS hosting, web hosting, live streaming and software solutions for businesses in India and worldwide.</p>
            <div class="mt-6 flex justify-center gap-4">
                <a href="https://ghc.believoo.com/domain/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition">
                    <i class="fas fa-globe"></i> Register a Domain
                </a>
            </div>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($services as $service)
                <div class="group bg-white dark:bg-slate-800 rounded-2xl p-8 border border-slate-100 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-500 hover:shadow-xl hover:-translate-y-1 transition-all block overflow-hidden">
                    <a href="{{ route('services.show', $service->slug) }}" class="block focus:outline-none">
                        <div class="w-16 h-16 rounded-2xl bg-amber-100 flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                            <i class="{{ $service->icon ?? 'fas fa-layer-group' }} text-2xl text-amber-600 group-hover:text-white transition-colors"></i>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">{{ $service->category ?? 'Service' }}</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-3 group-hover:text-amber-600 transition-colors">{{ $service->title }}</h3>
                        <p class="text-slate-500 dark:text-slate-300 leading-relaxed mb-6 line-clamp-3">{!! strip_tags($service->description) !!}</p>
                    </a>
                    @php
                        $rate = \App\Models\ExchangeRate::getUsdToInrRate();
                        $displayPrice = (strtoupper($currentCurrency ?? 'USD') === 'INR') ? $service->price * $rate : $service->price;
                    @endphp
                    <div class="flex items-center justify-between pt-6 border-t border-slate-100 dark:border-slate-700">
                        <span class="text-amber-600 font-bold">{{ $currencySymbol }}{{ number_format($displayPrice, 2) }}</span>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('services.show', $service->slug) }}" class="inline-flex items-center gap-2 text-amber-600 font-semibold text-sm group-hover:gap-3 transition-all">
                                Learn more <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                            @if($whatsappNumber)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $whatsappNumber) }}?text={{ urlencode('Hi Believoo, I am interested in ' . $service->title . '. Please help: ' . route('services.show', $service->slug)) }}"
                                   target="_blank"
                                   class="w-10 h-10 rounded-full bg-green-500 hover:bg-green-600 text-white flex items-center justify-center transition-all hover:scale-110"
                                   title="Order on WhatsApp">
                                    <i class="fab fa-whatsapp"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($services->isEmpty())
            <div class="text-center py-20">
                <i class="fas fa-cube text-5xl text-slate-200 dark:text-slate-600 mb-4"></i>
                <p class="text-slate-500 dark:text-slate-300">No services found.</p>
            </div>
        @endif
    </div>
</div>
