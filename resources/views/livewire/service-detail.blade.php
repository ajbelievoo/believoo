@php
    $whatsappNumber = \App\Models\Setting::getValue('whatsapp');
    $whatsappUrl = $whatsappNumber
        ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsappNumber) . '?text=' . urlencode('Hi Believoo, I am interested in ' . $service->title . '. Please assist me: ' . request()->url())
        : '';
@endphp

<div>
    <!-- Overview + Banner -->
    <section class="py-20 bg-white dark:bg-slate-900 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 -translate-x-6" x-transition:enter-end="opacity-100 translate-x-0">
                    <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm mb-2 block">{{ $service->category ?? 'Service' }}</span>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-6">{{ $service->title }} — Premium {{ $service->category ?? 'Service' }} by Believoo</h1>
                    <div class="prose prose-slate dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 leading-relaxed">
                        {!! $service->content ?? nl2br(e($service->description)) !!}
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row gap-4">
                        @if($service->ovhProduct && $service->ovhProduct->is_active)
                            <a href="{{ route('checkout', ['service' => $service->slug]) }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                                Buy Now
                                <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                        @else
                            <a href="#pricing" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                                View Pricing
                                <i class="fas fa-arrow-down text-sm"></i>
                            </a>
                        @endif
                        <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-semibold hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                            Contact Us
                        </a>
                        @if($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-green-500 text-white font-semibold hover:bg-green-600 transition shadow-lg shadow-green-500/25">
                            <i class="fab fa-whatsapp"></i>
                            WhatsApp Order
                        </a>
                        @endif
                    </div>
                </div>
                <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="relative rounded-3xl overflow-hidden bg-slate-100 dark:bg-slate-800 aspect-[4/3] flex items-center justify-center border border-slate-200 dark:border-slate-700">
                        @if($service->image)
                            <img src="{{ asset('storage/' . $service->image) }}" alt="{{ $service->title }}" class="w-full h-full object-cover">
                        @else
                            <div class="absolute inset-0 bg-gradient-to-br from-amber-100 to-slate-100 dark:from-slate-800 dark:to-slate-900"></div>
                            <i class="{{ $service->icon ?? 'fas fa-rocket' }} text-[10rem] text-amber-500/20 relative z-10"></i>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- What's Included -->
    <section class="py-20 bg-slate-50 dark:bg-slate-800/50 transition-colors" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Deliverables</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mt-2">What's Included</h2>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $features = is_array($service->features) && count($service->features) > 0
                        ? array_map(fn($f) => is_array($f) ? ($f['name'] ?? '') : $f, $service->features)
                        : ['Discovery & Planning', 'Custom Implementation', 'Quality Assurance', 'Deployment & Handover', '30 Days Support', 'Documentation'];
                    $featureIcons = ['fas fa-search', 'fas fa-cogs', 'fas fa-check-double', 'fas fa-rocket', 'fas fa-headset', 'fas fa-book'];
                @endphp
                @foreach(array_filter($features) as $i => $feature)
                    <div class="p-6 rounded-2xl bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 hover:border-amber-300 dark:hover:border-amber-500 hover:shadow-lg transition-all group">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 mb-4 group-hover:bg-amber-500 group-hover:text-white transition-colors">
                            <i class="{{ $featureIcons[$i % count($featureIcons)] }} text-lg"></i>
                        </div>
                        <h3 class="font-bold text-slate-900 dark:text-white mb-1">{{ $feature }}</h3>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-white dark:bg-slate-900 transition-colors" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-12">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Pricing</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mt-2">{{ $service->price_label ?? 'Starting From' }}</h2>
            </div>

            <div class="rounded-3xl bg-slate-900 text-white p-10 md:p-16 text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-amber-500 rounded-full opacity-20 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-64 h-64 bg-amber-500 rounded-full opacity-10 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="text-5xl md:text-6xl font-bold text-white mb-4">
                        <span class="text-amber-500">{{ $currencySymbol }}</span>{{ number_format($service->price, 2) }}
                    </div>
                    @if($service->ovhProduct && $service->ovhProduct->is_active)
                        <p class="text-slate-300 mb-8 max-w-xl mx-auto">Instant provisioning through {{ $service->ovhProduct->category }} after payment.</p>
                        <div class="flex flex-col sm:flex-row justify-center gap-4">
                            <a href="{{ route('checkout', ['service' => $service->slug]) }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                                Buy Now
                                <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl border border-slate-600 text-white font-semibold hover:bg-slate-800 transition">
                                Talk to Sales
                            </a>
                        </div>
                    @else
                        <p class="text-slate-300 mb-8 max-w-xl mx-auto">Final cost depends on scope and complexity. Contact us for a detailed quote.</p>
                        <div class="flex flex-col sm:flex-row justify-center gap-4">
                            <a href="#inquiry" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                                Start Project
                                <i class="fas fa-arrow-right text-sm"></i>
                            </a>
                            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl border border-slate-600 text-white font-semibold hover:bg-slate-800 transition">
                                Talk to Sales
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            @if(is_array($service->pricing_tiers) && count($service->pricing_tiers) > 0)
                <div class="grid md:grid-cols-3 gap-6 mt-12">
                    @foreach($service->pricing_tiers as $tier)
                        <div class="p-8 rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-center hover:border-amber-300 dark:hover:border-amber-500 hover:shadow-lg transition-all">
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $tier['name'] ?? 'Tier' }}</h3>
                            <p class="text-slate-500 dark:text-slate-400 text-sm mb-4">{{ $tier['description'] ?? '' }}</p>
                            <div class="text-3xl font-bold text-amber-600 mb-6">{{ $currencySymbol }}{{ number_format($tier['price'] ?? $service->price, 2) }}</div>
                            <a href="{{ route('checkout', ['service' => $service->slug, 'tier' => $tier['name'] ?? null]) }}" class="inline-flex items-center justify-center w-full px-6 py-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 font-semibold hover:bg-amber-500 hover:text-white hover:border-amber-500 dark:hover:text-white transition">
                                Choose Plan
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <!-- Process -->
    <section class="py-20 bg-slate-50 dark:bg-slate-800/50 transition-colors" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">How We Work</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mt-2">Our Process</h2>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
                @php
                    $steps = [
                        ['title' => 'Discovery', 'desc' => 'We understand your goals, audience and technical requirements.'],
                        ['title' => 'Planning', 'desc' => 'We design the architecture, timeline and deliverables.'],
                        ['title' => 'Execution', 'desc' => 'Our engineers build, test and iterate with your feedback.'],
                        ['title' => 'Delivery', 'desc' => 'We deploy, train and hand over with documentation and support.']
                    ];
                @endphp
                @foreach($steps as $i => $step)
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-xl font-bold mb-6">
                            0{{ $i + 1 }}
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">{{ $step['title'] }}</h3>
                        <p class="text-slate-500 dark:text-slate-300 text-sm leading-relaxed">{{ $step['desc'] }}</p>
                        @if(!$loop->last)
                            <div class="hidden lg:block absolute top-7 left-16 w-full h-0.5 bg-amber-200 dark:bg-amber-900/30 -z-10"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if(!empty($service->faqs) && is_array($service->faqs))
    <!-- FAQ Section -->
    <section class="py-20 bg-slate-50 dark:bg-slate-800/50 transition-colors" itemscope itemtype="https://schema.org/FAQPage">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">FAQ</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mt-2">Frequently Asked Questions</h2>
            </div>
            <div class="space-y-4">
                @foreach($service->faqs as $faq)
                    @if(!empty($faq['question']) && !empty($faq['answer']))
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2" itemprop="name">{{ $faq['question'] }}</h3>
                        <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                            <p class="text-slate-600 dark:text-slate-300 leading-relaxed" itemprop="text">{{ $faq['answer'] }}</p>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- CTA / Inquiry -->
    <section id="inquiry" class="py-20 bg-white dark:bg-slate-900 transition-colors">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">Ready to start {{ $service->title }}?</h2>
                <p class="text-slate-500 dark:text-slate-300 max-w-2xl mx-auto">Fill the form below and our team will get back to you with a custom proposal.</p>
            </div>
            <div class="bg-slate-50 dark:bg-slate-800 rounded-3xl p-8 md:p-12 border border-slate-100 dark:border-slate-700">
                <livewire:inquiry-form :service_id="$service->id" />
            </div>
        </div>
    </section>
</div>
