<x-layouts.believoo :settings="$settings">
    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center py-16 overflow-hidden bg-slate-50">
        <div class="absolute inset-0 z-0 pointer-events-none opacity-40" style="background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 32px 32px;"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-6" x-transition:enter-end="opacity-100 translate-y-0">
                    <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-100 text-amber-700 text-sm font-semibold mb-6">
                        <i class="fas fa-crown text-xs"></i>
                        Growth Scale Partner
                    </span>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-slate-900 leading-tight mb-6">
                        Cloud, VPS, B-CONNECT SaaS & Software Solutions for Growing Businesses
                    </h1>
                    <a href="https://bc.believoo.com/" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-cyan-100 text-cyan-700 text-sm font-semibold mb-6 hover:bg-cyan-200 transition">
                        <i class="fas fa-rocket text-xs"></i> New: B-CONNECT Workspace — Video, Remote, Billing & AI
                    </a>
                    <p class="text-lg md:text-xl font-semibold text-amber-600 mb-5" style="min-height:1.75rem;">
                        Expertise in <span data-bel-typing data-bel-words="VPS Hosting|Web Hosting|B-CONNECT SaaS|Live Streaming|Domain Services|App Development|Custom Software"></span><span class="bel-typed-caret"></span>
                    </p>
                    <p class="text-lg text-slate-600 mb-8 max-w-xl leading-relaxed">
                        Believoo delivers VPS hosting, web hosting, live streaming infrastructure, domain services and custom software engineering — built to scale your business in India and worldwide.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#services" class="bel-magnetic inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                            Explore Services
                            <i class="fas fa-arrow-right text-sm"></i>
                        </a>
                        <a href="https://bc.believoo.com/register" class="bel-magnetic inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-cyan-500 text-white font-semibold hover:bg-cyan-600 transition shadow-lg shadow-cyan-500/25">
                            Try B-CONNECT Free
                            <i class="fas fa-rocket text-sm"></i>
                        </a>
                        <a href="{{ route('contact') }}" class="bel-magnetic inline-flex items-center justify-center px-8 py-4 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-white hover:border-slate-400 transition">
                            Contact Us
                        </a>
                    </div>
                    <div class="mt-12 flex items-center gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900" data-bel-count="500" data-bel-suffix="+">500+</div>
                            <div class="text-sm text-slate-500">Projects</div>
                        </div>
                        <div class="w-px h-10 bg-slate-300"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900" data-bel-count="99.9" data-bel-decimals="1" data-bel-suffix="%">99.9%</div>
                            <div class="text-sm text-slate-500">Uptime</div>
                        </div>
                        <div class="w-px h-10 bg-slate-300"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-slate-900" data-bel-count="24" data-bel-suffix="/7">24/7</div>
                            <div class="text-sm text-slate-500">Support</div>
                        </div>
                    </div>
                </div>
                <div class="relative" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="relative aspect-square max-w-lg mx-auto">
                        <div class="absolute inset-0 rounded-[3rem] bg-gradient-to-br from-amber-100 to-slate-100"></div>
                        <div class="absolute inset-4 rounded-[2.5rem] bg-white shadow-2xl p-8 flex flex-col justify-center gap-4">
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-code text-xl"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">App Development</div>
                                    <div class="text-sm text-slate-500">Building modern solutions</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-globe text-xl"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">Domain Services</div>
                                    <div class="text-sm text-slate-500">Search & register domains</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-chart-line text-xl"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">Digital Growth</div>
                                    <div class="text-sm text-slate-500">SEO & AdSense growth</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust badges -->
    @php
        $gwMap = ['razorpay_enabled' => 'Razorpay', 'cashfree_enabled' => 'Cashfree', 'payu_enabled' => 'PayU', 'paypal_enabled' => 'PayPal'];
        $activeGateways = collect($gwMap)->filter(fn($name, $key) => ($settings[$key] ?? '0') === '1');
    @endphp
    <section class="py-5 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-center gap-x-8 gap-y-3 text-sm font-medium text-slate-500">
                <span class="inline-flex items-center gap-2"><i class="fas fa-lock text-emerald-500"></i> SSL Secure</span>
                @foreach($activeGateways as $gwName)
                    <span class="inline-flex items-center gap-2"><i class="fas fa-credit-card text-amber-500"></i> {{ $gwName }}</span>
                @endforeach
                <span class="inline-flex items-center gap-2"><i class="fas fa-shield-halved text-cyan-500"></i> 99.9% Uptime SLA</span>
                <span class="inline-flex items-center gap-2"><i class="fas fa-headset text-violet-500"></i> 24/7 Support</span>
                <span class="inline-flex items-center gap-2"><i class="fas fa-bolt text-amber-500"></i> Instant Setup</span>
            </div>
        </div>
    </section>

    <!-- Marquee -->
    <section class="py-8 bg-slate-900 overflow-hidden" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="marquee-track flex whitespace-nowrap" :class="shown ? 'opacity-100' : 'opacity-0'" class="transition-opacity duration-700">
            <div class="marquee-content flex items-center gap-12 pr-12 text-white/80 text-lg font-semibold uppercase tracking-wider">
                <span>App Development</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Domain Services</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Cloud Infrastructure</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Web Hosting</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>VPS Servers</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>SEO & Growth</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Digital Marketing</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>AdSense Monetization</span>
            </div>
            <div class="marquee-content flex items-center gap-12 pr-12 text-white/80 text-lg font-semibold uppercase tracking-wider" aria-hidden="true">
                <span>App Development</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Domain Services</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Cloud Infrastructure</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Web Hosting</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>VPS Servers</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>SEO & Growth</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Digital Marketing</span>
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>AdSense Monetization</span>
            </div>
        </div>
        <style>
            .marquee-track { animation: marquee 35s linear infinite; }
            .marquee-track:hover { animation-play-state: paused; }
            @keyframes marquee {
                0% { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
        </style>
    </section>

    <!-- Powered by technologies -->
    <section class="pb-8 bg-slate-900 border-t border-white/5 overflow-hidden">
        <div class="text-center pt-6 pb-2">
            <span class="text-[11px] font-bold tracking-[0.3em] uppercase text-white/40">Powered by industry-leading technology</span>
        </div>
        <div class="marquee-track flex whitespace-nowrap" style="animation-duration: 30s;">
            <div class="marquee-content flex items-center gap-16 pr-16 text-white/40 text-3xl">
                <i class="fab fa-aws" title="AWS"></i>
                <i class="fab fa-docker" title="Docker"></i>
                <i class="fab fa-linux" title="Linux"></i>
                <i class="fab fa-cloudflare" title="Cloudflare"></i>
                <i class="fab fa-wordpress" title="WordPress"></i>
                <i class="fab fa-php" title="PHP"></i>
                <i class="fab fa-laravel" title="Laravel"></i>
                <i class="fab fa-node-js" title="Node.js"></i>
                <i class="fab fa-react" title="React"></i>
                <i class="fab fa-python" title="Python"></i>
                <i class="fab fa-git-alt" title="Git"></i>
                <i class="fab fa-ubuntu" title="Ubuntu"></i>
            </div>
            <div class="marquee-content flex items-center gap-16 pr-16 text-white/40 text-3xl" aria-hidden="true">
                <i class="fab fa-aws"></i>
                <i class="fab fa-docker"></i>
                <i class="fab fa-linux"></i>
                <i class="fab fa-cloudflare"></i>
                <i class="fab fa-wordpress"></i>
                <i class="fab fa-php"></i>
                <i class="fab fa-laravel"></i>
                <i class="fab fa-node-js"></i>
                <i class="fab fa-react"></i>
                <i class="fab fa-python"></i>
                <i class="fab fa-git-alt"></i>
                <i class="fab fa-ubuntu"></i>
            </div>
        </div>
    </section>

    <!-- Our Businesses -->
    <section id="businesses" class="py-24 bg-white" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Our Businesses</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">One Group. Many Capabilities.</h2>
                <p class="text-slate-500 mt-4 max-w-2xl mx-auto">Believoo is a full-scale technology partner, bringing together everything your business needs to grow.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="{{ route('services.show', 'play-storeapp-store-publishing') }}" class="bel-tilt group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-100 flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                        <i class="fab fa-google-play text-2xl text-amber-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">App Publishing</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Play Store, App Store submission and management for your apps.</p>
                </a>
                <a href="{{ route('services.show', 'app-development') }}" class="bel-tilt group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-100 flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                        <i class="fas fa-code text-2xl text-amber-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Software Engineering</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Web, mobile, and custom applications built with modern technology.</p>
                </a>
                <a href="{{ route('client.domains.search') }}" class="bel-tilt group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-100 flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                        <i class="fas fa-globe text-2xl text-amber-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Domain Services</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">Search, register, and manage domains with full control.</p>
                </a>
                <a href="{{ route('services.show', 'seo-digital-growth') }}" class="bel-tilt group p-8 rounded-2xl bg-slate-50 border border-slate-100 hover:border-amber-300 hover:shadow-lg hover:-translate-y-1 transition-all text-center">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-100 flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                        <i class="fas fa-chart-line text-2xl text-amber-600 group-hover:text-white transition-colors"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mb-2">Digital Growth</h3>
                    <p class="text-sm text-slate-500 leading-relaxed">SEO, AdSense, and app publishing to scale your digital reach.</p>
                </a>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-24 bg-slate-50" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Our Expertise</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">Elite Services</h2>
                <p class="text-slate-500 mt-4 max-w-2xl mx-auto">Specialized infrastructure and software solutions tailored for high-growth enterprises and ambitious startups.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($services as $service)
                    <a href="{{ route('services.show', $service->slug) }}" class="bel-tilt group bg-white rounded-2xl p-8 border border-slate-100 hover:border-amber-300 hover:shadow-xl hover:-translate-y-1 transition-all block">
                        <div class="w-14 h-14 rounded-xl bg-amber-100 flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                            <i class="{{ $service->icon ?? 'fas fa-layer-group' }} text-2xl text-amber-600 group-hover:text-white transition-colors"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3">{{ $service->title }}</h3>
                        <p class="text-slate-500 leading-relaxed mb-6 line-clamp-3">{!! strip_tags($service->description) !!}</p>
                        <span class="inline-flex items-center gap-2 text-amber-600 font-semibold text-sm group-hover:gap-3 transition-all">
                            Learn more <i class="fas fa-arrow-right text-xs"></i>
                        </span>
                    </a>
                @empty
                @endforelse
            </div>
            <div class="text-center mt-12">
                <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-white hover:border-slate-400 transition">
                    View All Services <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Why Believoo -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 -translate-x-6" x-transition:enter-end="opacity-100 translate-x-0">
                    <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Why Believoo</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2 mb-6">Meet the All-in-One Technology Partner</h2>
                    <p class="text-slate-500 text-lg leading-relaxed mb-8">
                        Believoo Group brings together everything your business needs to build, launch, and grow in the digital world — without the complexity of managing multiple vendors.
                    </p>
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                                <i class="fas fa-bolt text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Hyper-Performance</h4>
                                <p class="text-slate-500 text-sm">Every project is optimized for speed, reliability, and scale.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                                <i class="fas fa-shield-alt text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">Unshakeable Security</h4>
                                <p class="text-slate-500 text-sm">Security-first protocols across all layers of your stack.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                                <i class="fas fa-headset text-lg"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-900 mb-1">24/7 Elite Support</h4>
                                <p class="text-slate-500 text-sm">Direct access to engineers who solve problems, not just log them.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)" x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-x-6" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="relative rounded-3xl overflow-hidden bg-slate-100 aspect-[4/3] flex items-center justify-center">
                        <div class="absolute inset-0 grid grid-cols-6 gap-1 p-4 opacity-20">
                            @for($i=0;$i<24;$i++)
                                <div class="bg-amber-500 rounded"></div>
                            @endfor
                        </div>
                        <div class="relative bg-white p-8 rounded-2xl shadow-xl max-w-sm mx-4">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="font-bold text-slate-900">Project Delivered</div>
                            </div>
                            <div class="text-sm text-slate-500 mb-4">Website + App + SEO package completed on time.</div>
                            <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 w-full rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="py-24 bg-slate-50" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-4">
                <div>
                    <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Selected Work</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">Projects We’re Proud Of</h2>
                </div>
                <a href="{{ route('portfolio.index') }}" class="inline-flex items-center gap-2 text-amber-600 font-semibold hover:gap-3 transition-all">
                    View All Projects <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($portfolios as $project)
                    <a href="{{ route('portfolio.show', $project->slug) }}" class="bel-tilt group block bg-white rounded-2xl overflow-hidden border border-slate-100 hover:border-amber-300 hover:shadow-xl transition-all">
                        <div class="aspect-[16/10] overflow-hidden">
                            <img src="{{ asset('storage/' . $project->image) }}" alt="{{ $project->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                        </div>
                        <div class="p-6">
                            <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold mb-3">{{ $project->category ?? 'Project' }}</span>
                            <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-amber-600 transition-colors">{{ $project->title }}</h3>
                            <p class="text-slate-500 text-sm line-clamp-2">{!! strip_tags($project->description) !!}</p>
                        </div>
                    </a>
                @empty
                @endforelse
            </div>
        </div>
    </section>

    <!-- Our Brands -->
    <section id="brands" class="py-24 bg-white" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Our Brands</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">The Believoo Family</h2>
                <p class="text-slate-500 mt-4 max-w-2xl mx-auto">Believoo Pvt Ltd ke teen brands — har ek apne domain me expert.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">

                <!-- B-CONNECT -->
                <a href="https://bc.believoo.com/" target="_blank" rel="noopener" class="bel-tilt group relative bg-slate-900 rounded-3xl p-8 overflow-hidden border border-slate-800 hover:border-cyan-400/50 transition-all block">
                    <div class="absolute -top-16 -right-16 w-48 h-48 bg-cyan-400/10 rounded-full blur-3xl group-hover:bg-cyan-400/20 transition-colors"></div>
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-cyan-400/10 border border-cyan-400/30 flex items-center justify-center mb-6">
                            <i class="fas fa-video text-2xl text-cyan-400"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-1">B-CONNECT</h3>
                        <p class="text-cyan-400 text-xs font-bold uppercase tracking-widest mb-4">Unified IT Workspace</p>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">Video calls, remote desktop, bug tracking, AI summaries, billing aur team collaboration — ek hi platform pe.</p>
                        <span class="inline-flex items-center gap-2 text-cyan-400 font-semibold text-sm group-hover:gap-3 transition-all">
                            Visit B-CONNECT <i class="fas fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                </a>

                <!-- GHC -->
                <a href="https://ghc.believoo.com/" target="_blank" rel="noopener" class="bel-tilt group relative bg-slate-900 rounded-3xl p-8 overflow-hidden border border-slate-800 hover:border-cyan-400/50 transition-all block">
                    <div class="absolute -top-16 -right-16 w-48 h-48 bg-cyan-400/10 rounded-full blur-3xl group-hover:bg-cyan-400/20 transition-colors"></div>
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-cyan-400/10 border border-cyan-400/30 flex items-center justify-center mb-6">
                            <i class="fas fa-server text-2xl text-cyan-400"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-1">GHC</h3>
                        <p class="text-cyan-400 text-xs font-bold uppercase tracking-widest mb-4">Go Host Cloud</p>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">Enterprise cloud hosting platform — VPS, dedicated servers, web hosting, domains aur cloud infrastructure A to Z.</p>
                        <span class="inline-flex items-center gap-2 text-cyan-400 font-semibold text-sm group-hover:gap-3 transition-all">
                            Visit GHC <i class="fas fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                </a>

                <!-- Iyolme -->
                <div class="bel-tilt group relative bg-slate-900 rounded-3xl p-8 overflow-hidden border border-slate-800 hover:border-violet-400/50 transition-all">
                    <div class="absolute -top-16 -right-16 w-48 h-48 bg-violet-400/10 rounded-full blur-3xl group-hover:bg-violet-400/20 transition-colors"></div>
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-violet-400/10 border border-violet-400/30 flex items-center justify-center mb-6">
                            <i class="fas fa-layer-group text-2xl text-violet-400"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-1">Iyolme</h3>
                        <p class="text-violet-400 text-xs font-bold uppercase tracking-widest mb-4">Digital Experiences</p>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">Modern digital products aur experiences — web, apps aur brand-driven platforms jo users ko engage karte hain.</p>
                        <span class="inline-flex items-center gap-2 text-violet-400/70 font-semibold text-sm">
                            <i class="fas fa-clock text-xs"></i> Coming Soon
                        </span>
                    </div>
                </div>

                <!-- Hitune Music -->
                <a href="https://hitune.in/" target="_blank" rel="noopener" class="bel-tilt group relative bg-slate-900 rounded-3xl p-8 overflow-hidden border border-slate-800 hover:border-pink-400/50 transition-all block">
                    <div class="absolute -top-16 -right-16 w-48 h-48 bg-pink-400/10 rounded-full blur-3xl group-hover:bg-pink-400/20 transition-colors"></div>
                    <div class="relative">
                        <div class="w-14 h-14 rounded-2xl bg-pink-400/10 border border-pink-400/30 flex items-center justify-center mb-6">
                            <i class="fas fa-music text-2xl text-pink-400"></i>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-1">Hitune</h3>
                        <p class="text-pink-400 text-xs font-bold uppercase tracking-widest mb-4">Music Platform</p>
                        <p class="text-slate-400 text-sm leading-relaxed mb-6">Music streaming aur entertainment platform — artists aur listeners ko connect karta hai.</p>
                        <span class="inline-flex items-center gap-2 text-pink-400 font-semibold text-sm group-hover:gap-3 transition-all">
                            Visit Hitune <i class="fas fa-arrow-right text-xs"></i>
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- B-CONNECT SaaS Section -->
    <section id="bconnect" class="py-24 bg-slate-900" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-cyan-400 font-semibold tracking-wider uppercase text-sm">SaaS Platform</span>
                <h2 class="text-3xl md:text-4xl font-bold text-white mt-2">B-CONNECT — One Workspace for IT Teams</h2>
                <p class="text-slate-400 mt-4 max-w-2xl mx-auto">IT companies, developers aur clients ke liye global SaaS platform. Sab kuch ek hi jagah — meetings, remote support, projects, billing aur AI summaries.</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-400/10 flex items-center justify-center mb-4">
                        <i class="fas fa-video text-2xl text-cyan-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">HD Video Meetings</h3>
                    <p class="text-slate-400 text-sm">Agora-powered video calls aur screen sharing.</p>
                </div>
                <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-400/10 flex items-center justify-center mb-4">
                        <i class="fas fa-desktop text-2xl text-cyan-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Remote Desktop</h3>
                    <p class="text-slate-400 text-sm">Client support ke liye browser-based remote access.</p>
                </div>
                <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-400/10 flex items-center justify-center mb-4">
                        <i class="fas fa-bug text-2xl text-cyan-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Tickets & Projects</h3>
                    <p class="text-slate-400 text-sm">Bug tracking, task management aur team collaboration.</p>
                </div>
                <div class="bg-slate-800/50 rounded-2xl p-6 border border-slate-700 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl bg-cyan-400/10 flex items-center justify-center mb-4">
                        <i class="fas fa-file-invoice-dollar text-2xl text-cyan-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Billing & Invoices</h3>
                    <p class="text-slate-400 text-sm">Razorpay/Cashfree integrated subscription management.</p>
                </div>
            </div>
            <div class="text-center">
                <a href="https://bc.believoo.com/register" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-cyan-500 text-white font-semibold hover:bg-cyan-600 transition shadow-lg shadow-cyan-500/25">
                    Start Free on B-CONNECT <i class="fas fa-arrow-right text-sm"></i>
                </a>
                <a href="https://bc.believoo.com/login" class="ml-4 inline-flex items-center gap-2 px-8 py-4 rounded-xl border border-slate-600 text-white font-semibold hover:bg-slate-800 transition">
                    Client Login
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    @php
        $testimonials = \App\Models\Testimonial::where('is_visible', true)->orderBy('sort_order')->latest()->get();
    @endphp
    @if($testimonials->count())
    <section class="py-24 bg-white overflow-hidden" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-12">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Testimonials</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">What Our Clients Say</h2>
            </div>
            <div x-data="{
                    i: 0,
                    total: {{ $testimonials->count() }},
                    timer: null,
                    start() { if (this.total > 1 && !this.timer) this.timer = setInterval(() => this.next(), 5000); },
                    stop() { clearInterval(this.timer); this.timer = null; },
                    next() { this.i = (this.i + 1) % this.total; },
                    prev() { this.i = (this.i - 1 + this.total) % this.total; }
                 }" x-init="start()" @mouseenter="stop()" @mouseleave="start()">
                <div class="relative">
                    @foreach($testimonials as $ti => $t)
                    <div x-show="i === {{ $ti }}" x-cloak
                         x-transition:enter="transition ease-out duration-500"
                         x-transition:enter-start="opacity-0 translate-y-4"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="bg-slate-50 rounded-3xl p-8 md:p-12 border border-slate-100 text-center">
                        <i class="fas fa-quote-left text-3xl text-amber-400 mb-6"></i>
                        <p class="text-lg md:text-xl text-slate-700 leading-relaxed mb-8">&ldquo;{{ $t->content }}&rdquo;</p>
                        <div class="flex items-center justify-center gap-4">
                            @if($t->image)
                                <img src="{{ asset('storage/' . $t->image) }}" alt="{{ $t->name }}" class="w-14 h-14 rounded-full object-cover border-2 border-amber-200">
                            @else
                                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold text-xl">{{ strtoupper(substr($t->name, 0, 1)) }}</div>
                            @endif
                            <div class="text-left">
                                <div class="font-bold text-slate-900">{{ $t->name }}</div>
                                @if($t->title || $t->company)
                                    <div class="text-sm text-slate-500">{{ collect([$t->title, $t->company])->filter()->implode(' · ') }}</div>
                                @endif
                                <div class="text-amber-500 text-xs mt-1">
                                    @for($s = 0; $s < 5; $s++)<i class="fas fa-star{{ $s < ($t->rating ?? 5) ? '' : ' opacity-25' }}"></i>@endfor
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @if($testimonials->count() > 1)
                <div class="flex items-center justify-center gap-4 mt-8">
                    <button @click="prev()" aria-label="Previous testimonial" class="w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:border-amber-400 hover:text-amber-600 transition"><i class="fas fa-chevron-left text-xs"></i></button>
                    <div class="flex items-center gap-2">
                        @foreach($testimonials as $ti => $t)
                        <button @click="i = {{ $ti }}" aria-label="Go to testimonial {{ $ti + 1 }}" :class="i === {{ $ti }} ? 'bg-amber-500 w-6' : 'bg-slate-300 w-2'" class="h-2 rounded-full transition-all"></button>
                        @endforeach
                    </div>
                    <button @click="next()" aria-label="Next testimonial" class="w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:border-amber-400 hover:text-amber-600 transition"><i class="fas fa-chevron-right text-xs"></i></button>
                </div>
                @endif
            </div>

            <!-- Feedback button -->
            <div class="text-center mt-10">
                <button @click="$dispatch('open-feedback')" class="bel-magnetic inline-flex items-center gap-2 px-8 py-3.5 rounded-xl border-2 border-amber-500 text-amber-600 font-semibold hover:bg-amber-500 hover:text-white transition">
                    <i class="fas fa-pen-to-square"></i> Share Your Feedback
                </button>
            </div>
        </div>
    </section>
    @endif

    <!-- FAQ -->
    @php
        $belFaqs = \App\Models\Service::where('is_active', true)->whereNotNull('faqs')->pluck('faqs')
            ->flatMap(fn($f) => is_array($f) ? $f : (json_decode($f, true) ?: []))
            ->filter(fn($f) => !empty($f['question']) && !empty($f['answer']))
            ->unique('question')->take(8)->values();
    @endphp
    @if($belFaqs->count())
    <section id="faq" class="py-24 bg-slate-50" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-12">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">FAQ</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">Frequently Asked Questions</h2>
                <p class="text-slate-500 mt-4">Everything you need to know about our services.</p>
            </div>
            <div class="space-y-4" x-data="{ open: 0 }">
                @foreach($belFaqs as $fi => $faq)
                <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden transition-all"
                     :class="open === {{ $fi }} ? 'border-amber-300 shadow-md' : ''">
                    <button @click="open = open === {{ $fi }} ? null : {{ $fi }}" class="w-full flex items-center justify-between gap-4 p-5 text-left">
                        <span class="font-semibold text-slate-900">{{ $faq['question'] }}</span>
                        <i class="fas fa-chevron-down text-amber-500 text-sm flex-shrink-0 transition-transform duration-300" :class="open === {{ $fi }} ? 'rotate-180' : ''"></i>
                    </button>
                    <div x-show="open === {{ $fi }}" x-cloak
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0">
                        <p class="px-5 pb-5 text-slate-500 leading-relaxed">{{ $faq['answer'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Latest Blog Posts -->
    @php $latestPosts = \App\Models\Post::published()->latest('published_at')->take(3)->get(); @endphp
    @if($latestPosts->count())
    <section class="py-24 bg-white" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-4">
                <div>
                    <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">From Our Blog</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mt-2">Latest Insights & Updates</h2>
                </div>
                <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 text-amber-600 font-semibold hover:gap-3 transition-all">
                    View All Posts <i class="fas fa-arrow-right text-sm"></i>
                </a>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                @foreach($latestPosts as $post)
                <a href="{{ route('blog.show', $post) }}" class="bel-tilt group block bg-slate-50 rounded-2xl overflow-hidden border border-slate-100 hover:border-amber-300 hover:shadow-xl transition-all">
                    @if($post->featuredImageUrl())
                    <div class="aspect-[16/9] overflow-hidden">
                        <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    </div>
                    @endif
                    <div class="p-6">
                        <div class="text-xs font-semibold text-amber-600 mb-2">{{ $post->published_at?->format('M d, Y') }}</div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2 group-hover:text-amber-600 transition-colors line-clamp-2">{{ $post->title }}</h3>
                        @if($post->excerpt)
                            <p class="text-sm text-slate-500 line-clamp-2">{{ $post->excerpt }}</p>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- SEO: FAQ schema --}}
    @if($belFaqs->count())
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => $belFaqs->map(fn($f) => [
            '@type' => 'Question',
            'name' => $f['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['answer']],
        ])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endif

    {{-- SEO: Reviews/ratings schema --}}
    @if($testimonials->count())
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        '@id' => url('/') . '#organization',
        'name' => $settings['site_name'] ?? 'Believoo',
        'url' => url('/'),
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => round((float) $testimonials->avg('rating'), 1),
            'reviewCount' => $testimonials->count(),
        ],
        'review' => $testimonials->map(fn($t) => [
            '@type' => 'Review',
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $t->rating, 'bestRating' => 5],
            'author' => ['@type' => 'Person', 'name' => $t->name],
            'reviewBody' => $t->content,
        ])->values()->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endif

    <!-- CTA Section -->
    <section class="py-24 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-3xl bg-slate-900 text-white p-12 md:p-16 overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-amber-500 rounded-full opacity-20 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-64 h-64 bg-amber-500 rounded-full opacity-10 blur-3xl"></div>
                <div class="relative z-10 text-center">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">Ready to Build Something Great?</h2>
                    <p class="text-slate-300 text-lg max-w-2xl mx-auto mb-8">Let’s discuss your project and see how Believoo can help you grow faster.</p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ route('contact') }}" class="bel-magnetic inline-flex items-center justify-center px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                            Start a Project
                        </a>
                        <a href="{{ route('services.index') }}" class="bel-magnetic inline-flex items-center justify-center px-8 py-4 rounded-xl border border-slate-600 text-white font-semibold hover:bg-slate-800 transition">
                            Browse Services
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Feedback modal (outside transformed sections so position:fixed works) -->
    <div x-data="{ fb: false }" x-init="$watch('fb', v => document.body.style.overflow = v ? 'hidden' : '')"
         @open-feedback.window="fb = true" @keydown.escape.window="fb = false">
        <div x-show="fb" x-cloak class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 2147483640;">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="fb = false"></div>
            <div class="relative bg-white dark:bg-slate-900 rounded-3xl shadow-2xl w-full max-w-lg p-8 max-h-[90vh] overflow-y-auto"
                 x-show="fb"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <button @click="fb = false" aria-label="Close" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white flex items-center justify-center transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
                <div class="text-center mb-6">
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">Share Your Feedback</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Worked with us? Tell others about your experience.</p>
                </div>
                <livewire:feedback-form />
            </div>
        </div>
    </div>
</x-layouts.believoo>
