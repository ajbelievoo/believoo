@php
    $whatsappNumber = \App\Models\Setting::getValue('whatsapp');
@endphp

<div class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-4">Our Services</h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">VPS hosting, web hosting and software solutions for businesses in India and worldwide.</p>
            <div class="mt-6 flex justify-center gap-4">
                <a href="https://ghc.believoo.com/domain/" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition">
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

        <!-- Why Believoo -->
        <div class="mt-20 rounded-3xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 p-8 md:p-12">
            <h2 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white text-center mb-3">Why Choose Believoo?</h2>
            <p class="text-slate-500 dark:text-slate-400 text-center mb-10 max-w-xl mx-auto">What you actually get compared to typical budget hosting providers.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700">
                            <th class="text-left py-3 pr-4 text-slate-500 dark:text-slate-400 font-semibold">Feature</th>
                            <th class="text-center py-3 px-4 text-amber-600 font-black">Believoo</th>
                            <th class="text-center py-3 px-4 text-slate-400 font-semibold">Typical Budget Hosts</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/50">
                        @foreach([
                            ['MCA-registered Indian company (CIN verifiable)', true, false],
                            ['Real human support — tickets, chat, WhatsApp', true, false],
                            ['Transparent pricing — no surprise renewal hikes', true, false],
                            ['99.9% uptime SLA with published status page', true, false],
                            ['NVMe SSD storage on all plans', true, false],
                            ['DDoS protection included free', true, false],
                            ['Instant provisioning after payment', true, false],
                            ['GST invoice for Indian businesses', true, false],
                            ['30-day money-back guarantee on self-hosted plans', true, false],
                        ] as [$feature, $us, $them])
                        <tr>
                            <td class="py-3 pr-4 text-slate-700 dark:text-slate-300">{{ $feature }}</td>
                            <td class="py-3 px-4 text-center">
                                @if($us)<i class="fas fa-check-circle text-emerald-500"></i>@else<i class="fas fa-times-circle text-slate-300"></i>@endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($them)<i class="fas fa-check-circle text-emerald-500"></i>@else<i class="fas fa-minus-circle text-slate-300 dark:text-slate-600"></i>@endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
