<x-layouts.believoo :settings="$settings">
    <!-- Hero Section - Enhanced Premium Design -->
    <section class="relative min-h-screen flex items-center justify-center pt-24 overflow-hidden z-[1]">
        {{-- Premium Animated Background --}}
        <div class="absolute inset-0 z-0 pointer-events-none">
            {{-- Floating gradient orbs with animation --}}
            <div class="absolute top-1/4 -left-1/4 w-[800px] h-[800px] rounded-full opacity-20"
                 style="background: radial-gradient(circle, rgba(0, 183, 255, 0.5) 0%, transparent 70%);
                        filter: blur(100px);
                        animation: floatOrb1 20s ease-in-out infinite;">
            </div>
            <div class="absolute bottom-1/4 -right-1/4 w-[700px] h-[700px] rounded-full opacity-20"
                 style="background: radial-gradient(circle, rgba(112, 0, 255, 0.5) 0%, transparent 70%);
                        filter: blur(100px);
                        animation: floatOrb2 25s ease-in-out infinite;">
            </div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full opacity-10"
                 style="background: radial-gradient(circle, rgba(255, 0, 160, 0.4) 0%, transparent 70%);
                        filter: blur(80px);
                        animation: floatOrb3 18s ease-in-out infinite;">
            </div>

            {{-- Animated mesh gradient --}}
            <div class="absolute inset-0 opacity-30"
                 style="background: radial-gradient(at 40% 20%, rgba(0, 183, 255, 0.15) 0px, transparent 40%),
                                  radial-gradient(at 80% 0%, rgba(112, 0, 255, 0.15) 0px, transparent 40%),
                                  radial-gradient(at 0% 50%, rgba(255, 0, 160, 0.1) 0px, transparent 40%),
                                  radial-gradient(at 80% 50%, rgba(0, 255, 209, 0.1) 0px, transparent 40%),
                                  radial-gradient(at 0% 100%, rgba(0, 183, 255, 0.1) 0px, transparent 40%);
                         animation: gradientShift 15s ease infinite;
                         background-size: 200% 200%;">
            </div>

            {{-- Grid overlay --}}
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:64px_64px]"></div>

            {{-- Noise texture - subtle --}}
            <div class="absolute inset-0 opacity-[0.008] mix-blend-soft-light pointer-events-none"
                 style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%270 0 512 512%27 xmlns=%27http://www.w3.org/2000/svg%27%3E%3Cfilter id=%27noise%27%3E%3CfeTurbulence type=%27fractalNoise%27 baseFrequency=%270.8%27 numOctaves=%273%27 stitchTiles=%27stitch%27/%3E%3C/filter%3E%3Crect width=%27100%25%27 height=%27100%25%27 filter=%27url%28%23noise%29%27/%3E%3C/svg%3E');">
            </div>
        </div>

        {{-- Animation Keyframes --}}
        <style>
            @keyframes floatOrb1 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                25% { transform: translate(50px, -30px) scale(1.1); }
                50% { transform: translate(-30px, 50px) scale(1); }
                75% { transform: translate(-50px, -20px) scale(0.95); }
            }
            @keyframes floatOrb2 {
                0%, 100% { transform: translate(0, 0) scale(1); }
                33% { transform: translate(-40px, 40px) scale(1.15); }
                66% { transform: translate(60px, -30px) scale(0.9); }
            }
            @keyframes floatOrb3 {
                0%, 100% { transform: translate(-50%, -50%) scale(1); }
                50% { transform: translate(-50%, -50%) scale(1.2) rotate(180deg); }
            }
            @keyframes gradientShift {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }
            @keyframes shimmer {
                0% { background-position: -200% center; }
                100% { background-position: 200% center; }
            }
        </style>

        <div class="relative z-10 max-w-7xl mx-auto px-4 text-center"
             x-data="{ shown: false }"
             x-init="setTimeout(() => shown = true, 100)"
             x-show="shown"
             x-transition:enter="transition ease-out duration-1000"
             x-transition:enter-start="opacity-0 translate-y-10"
             x-transition:enter-end="opacity-100 translate-y-0">

            {{-- Eyebrow badge with pulse --}}
            <div class="flex justify-center mb-8">
                <span class="inline-flex items-center gap-3 px-6 py-3 rounded-full glass border border-electric-blue/30 text-electric-blue text-xs font-black uppercase tracking-[0.3em] shadow-lg shadow-electric-blue/10">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-electric-blue opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-electric-blue"></span>
                    </span>
                    Next-Gen Infrastructure Agency
                </span>
            </div>

            {{-- Animated Headline with gradient text --}}
            <h1 class="font-black tracking-tighter mb-8 leading-[0.85] uppercase"
                style="font-size: clamp(3.5rem, 12vw, 10rem)">
                {!! $content['hero_title'] ?? 'Build <span class="text-electric-blue">Scale</span><br/>& <span class="text-gradient-animated">Dominate</span>' !!}
            </h1>

            {{-- Subheadline --}}
            <p class="text-gray-400 max-w-3xl mx-auto mb-12 font-medium leading-relaxed"
               style="font-size: clamp(1rem, 2vw, 1.375rem)">
                {{ $content['hero_description'] ?? "We architect elite digital ecosystems for companies that refuse to settle for second place. High-performance, unshakeable, future-proof." }}
            </p>

            {{-- Premium CTAs with shine effect --}}
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="#inquiry" class="group relative px-12 py-5 rounded-2xl bg-gradient-to-r from-electric-blue to-electric-violet text-dark font-black text-lg uppercase tracking-wider overflow-hidden btn-shine hover:shadow-xl hover:shadow-electric-blue/30 transition-all duration-300 hover:-translate-y-1">
                    <span class="relative z-10 flex items-center gap-3">
                        <span>Initiate Project</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </span>
                </a>
                <a href="{{ route('portfolio.index') }}"
                   class="group px-12 py-5 rounded-2xl glass-strong border border-white/20 text-white font-black text-lg uppercase tracking-wider hover:bg-white/10 hover:border-electric-blue/30 transition-all duration-300 hover:-translate-y-1">
                    <span class="flex items-center gap-3">
                        <span>Explore Work</span>
                        <i class="fas fa-external-link-alt text-sm opacity-70"></i>
                    </span>
                </a>
            </div>

            {{-- Stats row --}}
            <div class="mt-16 flex flex-wrap justify-center gap-12">
                <div class="text-center">
                    <div class="text-4xl font-black text-white mb-1">500+</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Projects Delivered</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-black text-white mb-1">99.9%</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Uptime SLA</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-black text-white mb-1">24/7</div>
                    <div class="text-xs font-bold uppercase tracking-widest text-gray-500">Expert Support</div>
                </div>
            </div>

            {{-- Scroll indicator --}}
            <div class="mt-20 flex justify-center">
                <div class="flex flex-col items-center gap-2 text-gray-600 animate-bounce">
                    <span class="text-[10px] font-black uppercase tracking-widest">Scroll to explore</span>
                    <div class="w-6 h-10 rounded-full border-2 border-gray-600 flex items-start justify-center p-1">
                        <div class="w-1.5 h-3 rounded-full bg-gray-600 animate-bounce"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tech Stack Ticker -->
    <section class="py-16 border-y border-white/5 overflow-hidden" aria-label="Technologies we use">
        @php
            $techs = [
                ['name' => 'Laravel', 'icon' => 'fab fa-laravel'],
                ['name' => 'React', 'icon' => 'fab fa-react'],
                ['name' => 'Node.js', 'icon' => 'fab fa-node-js'],
                ['name' => 'AWS', 'icon' => 'fab fa-aws'],
                ['name' => 'Docker', 'icon' => 'fab fa-docker'],
                ['name' => 'Python', 'icon' => 'fab fa-python'],
                ['name' => 'Kubernetes', 'icon' => 'fas fa-dharmachakra'],
                ['name' => 'PostgreSQL', 'icon' => 'fas fa-database'],
                ['name' => 'Redis', 'icon' => 'fas fa-bolt'],
                ['name' => 'Tailwind', 'icon' => 'fas fa-wind'],
                ['name' => 'Vue.js', 'icon' => 'fab fa-vuejs'],
                ['name' => 'Linux', 'icon' => 'fab fa-linux'],
            ];
        @endphp
        <div class="flex whitespace-nowrap animate-infinite-scroll">
            @foreach(array_merge($techs, $techs) as $tech)
                <div class="inline-flex items-center gap-3 mx-10">
                    <i class="{{ $tech['icon'] }} text-white/20 text-2xl"></i>
                    <span class="text-3xl font-black text-white/20 hover:text-electric-blue transition-colors cursor-default uppercase tracking-tighter">{{ $tech['name'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-32 bg-dark-100/50"
             x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="flex flex-col md:flex-row justify-between items-end mb-24 gap-8">
                <div>
                    <span class="section-label">Our Expertise</span>
                    <h2 class="text-6xl font-black tracking-tighter uppercase leading-none">Elite <br/>Services</h2>
                </div>
                <p class="text-gray-400 max-w-md text-lg">
                    Specialized infrastructure and software solutions tailored for high-growth enterprises and ambitious startups.
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($services as $service)
                    <a href="{{ route('services.show', $service->slug) }}"
                       class="group relative p-10 rounded-[2.5rem] glass hover:border-electric-blue/40 transition-all duration-500 hover:-translate-y-2 overflow-hidden block">
                        {{-- Glow on hover --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-electric-blue/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 rounded-[2.5rem]"></div>
                        {{-- Icon --}}
                        <div class="relative w-16 h-16 rounded-2xl bg-electric-blue/10 flex items-center justify-center mb-8 group-hover:bg-electric-blue transition-all duration-500">
                            <i class="{{ $service->icon ?? 'fas fa-layer-group' }} text-2xl text-electric-blue group-hover:text-dark transition-colors duration-500"></i>
                        </div>
                        <h3 class="text-2xl font-black mb-4 uppercase tracking-tight relative">{{ $service->title }}</h3>
                        <p class="text-gray-400 leading-relaxed line-clamp-3 mb-8 relative">{!! strip_tags($service->description) !!}</p>
                        <div class="relative pt-6 border-t border-white/5 flex justify-between items-center">
                            <span class="text-xs font-black uppercase tracking-widest text-gray-500">{{ $service->price_label }}</span>
                            <span class="text-2xl font-black text-electric-blue">
                                @if($service->price)
                                    ${{ number_format($service->price, 0) }}
                                @else
                                    Custom
                                @endif
                            </span>
                        </div>
                    </a>
                @empty
                @endforelse
            </div>
        </div>
    </section>

    <!-- Why Choose Us - Bento Grid -->
    <section class="py-32"
             x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4"
             :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-24">
                <span class="section-label-violet">Why Believoo</span>
                <h2 class="text-6xl font-black tracking-tighter uppercase">The Competitive <span class="text-electric-violet">Edge</span></h2>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="md:col-span-2 p-12 rounded-[3rem] glass bg-gradient-to-br from-white/5 to-transparent relative overflow-hidden group">
                    <div class="relative z-10">
                        <div class="w-16 h-16 rounded-2xl bg-electric-blue/10 flex items-center justify-center mb-8">
                            <i class="fas fa-bolt text-2xl text-electric-blue"></i>
                        </div>
                        <h3 class="text-4xl font-black mb-6 uppercase">{{ $content['bento_1_title'] ?? 'Hyper-Performance Architecture' }}</h3>
                        <p class="text-xl text-gray-400 max-w-xl">{{ $content['bento_1_text'] ?? "We don't just build; we optimize. Every line of code and every server configuration is tuned for maximum throughput and minimum latency." }}</p>
                    </div>
                </div>
                <div class="p-12 rounded-[3rem] glass border border-electric-violet/20 bg-electric-violet/5 group relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="w-16 h-16 rounded-2xl bg-electric-violet/10 flex items-center justify-center mb-8">
                            <i class="fas fa-shield-alt text-2xl text-electric-violet"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-6 uppercase">{{ $content['bento_2_title'] ?? 'Unshakeable Security' }}</h3>
                        <p class="text-lg text-gray-400">{{ $content['bento_2_text'] ?? 'Military-grade protection for your digital assets. We implement zero-trust protocols across all layers.' }}</p>
                    </div>
                </div>
                <div class="p-12 rounded-[3rem] glass group relative overflow-hidden">
                    <div class="relative z-10">
                        <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center mb-8">
                            <i class="fas fa-infinity text-2xl text-white"></i>
                        </div>
                        <h3 class="text-3xl font-black mb-6 uppercase">{{ $content['bento_3_title'] ?? '24/7 Elite Support' }}</h3>
                        <p class="text-lg text-gray-400">{{ $content['bento_3_text'] ?? 'Not just a ticket system. Direct access to engineers who actually solve problems, not just log them.' }}</p>
                    </div>
                </div>
                <div class="md:col-span-2 p-12 rounded-[3rem] glass bg-gradient-to-bl from-electric-blue/10 to-transparent relative overflow-hidden group">
                    <div class="relative z-10 flex flex-col md:flex-row items-center gap-12">
                        <div class="flex-1">
                            <div class="w-16 h-16 rounded-2xl bg-electric-blue/10 flex items-center justify-center mb-8">
                                <i class="fas fa-expand-arrows-alt text-2xl text-electric-blue"></i>
                            </div>
                            <h3 class="text-4xl font-black mb-6 uppercase">Scale Without Limits</h3>
                            <p class="text-xl text-gray-400">Our infrastructure grows with you. From 100 users to 100 million, we ensure your platform remains rock solid.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="py-32 bg-dark-100/50"
             x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4"
             :class="shown ? 'opacity-100 translate-x-0' : 'opacity-0 translate-x-8'"
             class="transition-all duration-700 ease-out">
            <div class="flex justify-between items-center mb-24">
                <h2 class="text-6xl font-black tracking-tighter uppercase">Selected Work</h2>
                <a href="{{ route('portfolio.index') }}" class="text-electric-blue font-black uppercase tracking-widest flex items-center gap-4 group">
                    View All Projects <i class="fas fa-arrow-right group-hover:translate-x-2 transition-transform"></i>
                </a>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-10">
                @forelse($portfolios as $project)
                    <a href="{{ route('portfolio.show', $project->slug) }}"
                       class="relative group overflow-hidden rounded-[3rem] aspect-[4/5] block">
                        <img src="{{ asset('storage/' . $project->image) }}"
                             class="object-cover w-full h-full transition-transform duration-700 group-hover:scale-110"
                             alt="{{ $project->title }}"
                             loading="lazy">
                             loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/20 to-transparent opacity-90 transition-all duration-500 flex flex-col justify-end p-10">
                            <span class="badge-blue mb-3">{{ $project->category ?? 'Infrastructure' }}</span>
                            <h4 class="text-3xl font-black mb-3 uppercase">{{ $project->title }}</h4>
                            <div class="text-gray-300 mb-6 line-clamp-2 text-base font-medium opacity-0 group-hover:opacity-100 transition-opacity duration-300">{!! strip_tags($project->description) !!}</div>
                            <div class="w-12 h-12 rounded-full glass flex items-center justify-center group-hover:bg-electric-blue group-hover:text-dark transition-all duration-500">
                                <i class="fas fa-arrow-right -rotate-45 group-hover:rotate-0 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                @empty
                @endforelse
            </div>
        </div>
    </section>

    <!-- Inquiry Form Section -->
    <section id="inquiry" class="py-32 relative overflow-hidden"
             x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="absolute -left-48 top-1/2 -translate-y-1/2 w-96 h-96 bg-electric-blue opacity-5 blur-[100px] rounded-full pointer-events-none"></div>
        <div class="absolute -right-48 top-1/2 -translate-y-1/2 w-96 h-96 bg-electric-violet opacity-5 blur-[100px] rounded-full pointer-events-none"></div>

        <div class="max-w-5xl mx-auto px-4 relative z-10"
             :class="shown ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
             class="transition-all duration-700 ease-out">
            <div class="glass-strong p-16 rounded-[4rem] border border-white/5 relative overflow-hidden">
                <div class="absolute -right-24 -top-24 w-96 h-96 bg-electric-violet opacity-10 blur-[100px] rounded-full"></div>
                <div class="text-center mb-20 relative z-10">
                    <span class="section-label">Ready to start?</span>
                    <h2 class="text-5xl md:text-7xl font-black tracking-tighter uppercase mb-6">
                        Initiate <span class="text-electric-blue">Deployment</span>
                    </h2>
                    <p class="text-gray-400 text-lg max-w-2xl mx-auto leading-relaxed">
                        Submit your project parameters and our engineering team will architect a custom proposal within 24 hours.
                    </p>
                </div>
                <div class="relative z-10">
                    <livewire:inquiry-form />
                </div>
            </div>
        </div>
    </section>

    <!-- Support Ecosystem -->
    <section class="py-32 bg-dark/50"
             x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-24">
                <span class="section-label">Support Protocol</span>
                <h2 class="text-6xl font-black tracking-tighter uppercase">How We <span class="text-electric-blue">Support</span> You</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-12">
                <div class="card-hover group">
                    <div class="w-16 h-16 rounded-2xl bg-electric-blue/10 flex items-center justify-center mb-8 group-hover:bg-electric-blue transition-all duration-300">
                        <i class="fas fa-headset text-2xl text-electric-blue group-hover:text-dark transition-colors duration-300"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-6 uppercase">Live Support Hub</h3>
                    <p class="text-gray-400 leading-relaxed mb-8">Click the pulse icon at the bottom right to open our Support Hub. You can send instant messages or request an immediate callback from our engineers.</p>
                    <div class="pt-8 border-t border-white/5 text-[10px] font-black uppercase tracking-widest text-electric-blue">
                        Response Time: &lt; 15 Minutes
                    </div>
                </div>

                <div class="card-hover group" style="border-color: rgba(112,0,255,0.1)">
                    <div class="w-16 h-16 rounded-2xl bg-electric-violet/10 flex items-center justify-center mb-8 group-hover:bg-electric-violet transition-all duration-300">
                        <i class="fas fa-ticket-alt text-2xl text-electric-violet group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-6 uppercase">Smart Ticketing</h3>
                    <p class="text-gray-400 leading-relaxed mb-8">Once your project is initiated, you'll get access to the Client Dashboard where you can create and track technical support tickets for all your active services.</p>
                    <div class="pt-8 border-t border-white/5 text-[10px] font-black uppercase tracking-widest text-electric-violet">
                        Priority: 24/7 SLA Available
                    </div>
                </div>

                <div class="card-hover group">
                    <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center mb-8 group-hover:bg-white/10 transition-all duration-300">
                        <i class="fas fa-user-shield text-2xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-6 uppercase">Client Command Center</h3>
                    <p class="text-gray-400 leading-relaxed mb-8">A dedicated area for you to manage project milestones, view invoices, and communicate directly with your project manager in real-time.</p>
                    <div class="pt-8 border-t border-white/5 text-[10px] font-black uppercase tracking-widest text-white">
                        Access: Secured Auth Only
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.believoo>
