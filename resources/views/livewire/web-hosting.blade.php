<div class="pt-32 pb-20 min-h-screen transition-colors duration-300 bg-slate-50 text-slate-900 dark:bg-[#0a0a1a] dark:text-white">
    <!-- Hero -->
    <section class="py-20 text-center relative overflow-hidden">
        <div class="absolute inset-0 pointer-events-none bg-gradient-to-b from-sky-200/50 to-transparent dark:from-cyan-500/10 dark:to-transparent"></div>
        <div class="max-w-4xl mx-auto px-4 relative z-10"
             x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)"
             x-show="shown"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 translate-y-10"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-6 bg-sky-100 dark:bg-cyan-500/10">
                <i class="fas fa-globe text-3xl text-sky-600 dark:text-cyan-400"></i>
            </div>
            <span class="text-xs font-black uppercase tracking-widest text-sky-600 dark:text-cyan-400">Managed Hosting</span>
            <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tighter mb-6">
                Web Hosting <span class="text-sky-600 dark:text-cyan-400">Plans</span>
            </h1>
            <p class="text-xl max-w-2xl mx-auto text-slate-600 dark:text-gray-400">
                Perfect for small to medium websites with lightning-fast SSD storage and 99.9% uptime guarantee.
            </p>
        </div>
    </section>

    <!-- Pricing Cards -->
    <section class="py-16 px-4" x-data="{ selected: null }">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($services as $index => $service)
                    @php
                        $pricingTiers = $service->pricing_tiers ?? [];
                        $tier = $pricingTiers[0] ?? ['name' => 'Basic', 'price' => $service->price];
                        $isPopular = $index === 1;
                    @endphp
                    <div @click="selected = {{ $index }}"
                         class="rounded-3xl p-8 border-2 cursor-pointer transition-all duration-300 relative
                            {{ $isPopular ? 'ring-1 ring-sky-500/30 dark:ring-cyan-500/30' : '' }}
                            {{ $index === 0 ? 'border-slate-200 bg-white shadow-lg hover:border-sky-300 dark:border-white/10 dark:bg-white/5 dark:shadow-none dark:hover:border-white/20' : '' }}
                            {{ $index === 1 ? 'border-slate-200 bg-white shadow-lg hover:border-sky-300 dark:border-white/10 dark:bg-white/5 dark:shadow-none dark:hover:border-white/20' : '' }}
                            {{ $index === 2 ? 'border-slate-200 bg-white shadow-lg hover:border-sky-300 dark:border-white/10 dark:bg-white/5 dark:shadow-none dark:hover:border-white/20' : '' }}">
                        @if($isPopular)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-sky-500 text-white dark:bg-cyan-500 dark:text-black">Popular</span>
                            </div>
                        @endif
                        <div class="mb-6 {{ $isPopular ? 'mt-4' : '' }}">
                            <h3 class="text-xl font-black uppercase mb-2 text-slate-900 dark:text-white">{{ explode('-', $service->title)[1] ?? $service->title }}</h3>
                            <p class="text-sm line-clamp-2 text-slate-600 dark:text-gray-400">{{ strip_tags($service->description) }}</p>
                        </div>
                        <div class="mb-6">
                            <span class="text-5xl font-black text-sky-600 dark:text-cyan-400">${{ $tier['price'] ?? $service->price }}</span>
                            <span class="text-sm text-slate-400 dark:text-gray-400">/mo</span>
                        </div>
                        <ul class="space-y-3 mb-8">
                            @foreach($service->features ?? [] as $feature)
                                @php $featureText = is_array($feature) ? ($feature['feature'] ?? '') : $feature; @endphp
                                @if($featureText)
                                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-gray-300">
                                        <i class="fas fa-check text-xs flex-shrink-0 text-sky-500 dark:text-cyan-400"></i>
                                        {{ $featureText }}
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        @auth
                            <a href="{{ route('checkout', ['service' => $service->slug, 'tier' => $tier['name'] ?? 'default']) }}"
                               class="w-full text-center block text-sm py-3 rounded-xl font-bold uppercase transition-all
                                {{ $isPopular ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white dark:from-cyan-500 dark:to-blue-600 dark:text-black' : 'bg-slate-100 border border-slate-200 text-slate-700 hover:bg-sky-50 dark:bg-white/10 dark:border-white/20 dark:text-white dark:hover:bg-white/20' }}">
                                Buy Now
                            </a>
                        @else
                            <a href="{{ route('contact') }}"
                               class="w-full text-center block text-sm py-3 rounded-xl font-bold uppercase transition-all
                                {{ $isPopular ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white dark:from-cyan-500 dark:to-blue-600 dark:text-black' : 'bg-slate-100 border border-slate-200 text-slate-700 hover:bg-sky-50 dark:bg-white/10 dark:border-white/20 dark:text-white dark:hover:bg-white/20' }}">
                                Get Started
                            </a>
                        @endauth
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-16 px-4 bg-slate-100 dark:bg-white/5" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach([
                    ['icon' => 'fas fa-bolt', 'title' => 'NVMe SSD', 'desc' => 'Lightning-fast NVMe SSD storage for optimal performance'],
                    ['icon' => 'fas fa-shield-alt', 'title' => 'Free SSL', 'desc' => 'Free SSL certificates for all your domains'],
                    ['icon' => 'fas fa-headset', 'title' => 'Expert Support', 'desc' => 'Reliable expert technical support'],
                    ['icon' => 'fas fa-database', 'title' => 'Daily Backups', 'desc' => 'Automated daily backups with easy restoration'],
                ] as $feature)
                    <div class="text-center p-6 rounded-2xl transition-all hover:-translate-y-1 bg-white border border-slate-200 hover:border-sky-300 shadow-sm dark:bg-white/5 dark:border-white/10 dark:hover:border-cyan-500/30 dark:shadow-none">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-sky-100 dark:bg-cyan-500/10">
                            <i class="{{ $feature['icon'] }} text-2xl text-sky-600 dark:text-cyan-400"></i>
                        </div>
                        <h3 class="font-black mb-2 uppercase text-sm tracking-widest text-slate-900 dark:text-white">{{ $feature['title'] }}</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FAQ Accordion -->
    <section class="py-20 px-4" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-3xl mx-auto"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-xs font-black uppercase tracking-widest text-sky-600 dark:text-cyan-400">Common Questions</span>
                <h2 class="text-4xl font-black uppercase tracking-tighter text-slate-900 dark:text-white">Frequently Asked <span class="text-sky-600 dark:text-cyan-400">Questions</span></h2>
            </div>
            <div class="space-y-3" x-data="{ open: null }">
                @foreach([
                    ['q' => 'What is included in web hosting?', 'a' => 'All plans include SSD storage, free SSL certificate, daily backups, expert support, and a 99.9% uptime guarantee.'],
                    ['q' => 'Can I upgrade my plan later?', 'a' => 'Yes, you can upgrade your hosting plan at any time. The price difference will be prorated for the remaining billing period.'],
                    ['q' => 'Do you offer a money-back guarantee?', 'a' => 'We offer a 30-day money-back guarantee on hosting plans hosted on Believoo\'s own infrastructure. Upstream-provisioned cloud services follow provider terms — see our Refund Policy.'],
                    ['q' => 'How do I migrate my existing website?', 'a' => 'Our team provides free website migration assistance for all new customers. Contact our support team to get started.'],
                    ['q' => 'What control panel do you use?', 'a' => 'We provide cPanel access with all hosting plans, making it easy to manage your website, databases, and email accounts.'],
                ] as $index => $faq)
                    <div class="rounded-2xl overflow-hidden border bg-white border-slate-200 dark:bg-white/5 dark:border-white/10">
                        <button @click="open = open === {{ $index }} ? null : {{ $index }}"
                                class="w-full flex justify-between items-center px-8 py-6 text-left min-h-[64px]"
                                :aria-expanded="open === {{ $index }}">
                            <span class="font-black text-sm uppercase tracking-wide pr-4 text-slate-900 dark:text-white">{{ $faq['q'] }}</span>
                            <i class="fas fa-chevron-down text-sm flex-shrink-0 transition-transform duration-300 text-sky-600 dark:text-cyan-400"
                               :class="open === {{ $index }} ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open === {{ $index }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="px-8 pb-6 leading-relaxed text-sm text-slate-600 dark:text-gray-400">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</div>
