<div class="pt-32 pb-20 min-h-screen transition-colors duration-500" 
     :class="$store.darkMode.on ? 'bg-[#0a0a1a] text-white' : 'bg-slate-50 text-slate-900'">
    
    <style>
        /* Theme-aware variables */
        .theme-dark {
            --bg-page: #0a0a1a;
            --bg-card: rgba(17, 17, 43, 0.8);
            --bg-card-hover: rgba(26, 26, 58, 0.9);
            --text-primary: #ffffff;
            --text-secondary: #a0a0b0;
            --text-muted: #6b7280;
            --border-color: rgba(255, 255, 255, 0.1);
            --accent-cyan: #00d4ff;
            --accent-purple: #8b5cf6;
            --accent-green: #10b981;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
        }
        .theme-light {
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f1f5f9;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border-color: rgba(0, 0, 0, 0.08);
            --accent-cyan: #0ea5e9;
            --accent-purple: #8b5cf6;
            --accent-green: #10b981;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        /* Animated Background - Dark */
        .bg-dark-animated {
            background: radial-gradient(ellipse at top, #1a1a3e 0%, #0a0a1a 50%, #000000 100%);
            position: absolute;
            inset: 0;
        }
        .bg-dark-animated::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(0, 212, 255, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 60% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 40%);
            animation: bgPulse 8s ease-in-out infinite;
        }
        
        /* Animated Background - Light */
        .bg-light-animated {
            background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 50%, #f5f3ff 100%);
            position: absolute;
            inset: 0;
        }
        .bg-light-animated::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 60% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 40%);
            animation: bgPulse 8s ease-in-out infinite;
        }
        
        @keyframes bgPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        /* Particles */
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            animation: float 15s infinite;
        }
        .particle-dark { background: rgba(0, 212, 255, 0.6); }
        .particle-light { background: rgba(14, 165, 233, 0.4); }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(50px); opacity: 0; }
        }
        
        /* Cards */
        .hosting-card-dark {
            background: rgba(17, 17, 43, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.37);
        }
        .hosting-card-light {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        /* Buttons */
        .btn-glow {
            background: linear-gradient(135deg, #00d4ff 0%, #0891b2 100%);
            color: #000;
            font-weight: 700;
            box-shadow: 0 0 20px rgba(0, 212, 255, 0.4);
            transition: all 0.3s ease;
        }
        .btn-glow:hover {
            box-shadow: 0 0 40px rgba(0, 212, 255, 0.6);
            transform: translateY(-2px);
        }
        
        .btn-outline-dark {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        .btn-outline-dark:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .btn-outline-light {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.2);
            color: #0f172a;
        }
        .btn-outline-light:hover {
            border-color: #0ea5e9;
            color: #0ea5e9;
        }
        
        /* Text Gradients */
        .gradient-text-dark {
            background: linear-gradient(135deg, #00d4ff 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gradient-text-light {
            background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Trust Badge */
        .trust-badge-dark {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        .trust-badge-light {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #059669;
        }
        
        /* Icon Box */
        .icon-box-dark {
            background: linear-gradient(135deg, rgba(0, 212, 255, 0.2) 0%, rgba(139, 92, 246, 0.2) 100%);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        .icon-box-light {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
            border: 1px solid rgba(14, 165, 233, 0.2);
        }
        
        /* Plan Cards */
        .plan-card-dark {
            background: linear-gradient(145deg, rgba(17, 17, 43, 0.9) 0%, rgba(26, 26, 58, 0.9) 100%);
            border: 1px solid rgba(0, 212, 255, 0.15);
            transition: all 0.4s ease;
        }
        .plan-card-dark:hover {
            border-color: rgba(0, 212, 255, 0.4);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 212, 255, 0.2);
        }
        .plan-card-light {
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
        }
        .plan-card-light:hover {
            border-color: rgba(14, 165, 233, 0.3);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15);
        }
        
        .plan-popular-dark {
            background: linear-gradient(145deg, rgba(0, 212, 255, 0.15) 0%, rgba(17, 17, 43, 0.95) 100%);
            border: 2px solid #00d4ff;
        }
        .plan-popular-light {
            background: linear-gradient(145deg, rgba(14, 165, 233, 0.1) 0%, rgba(255, 255, 255, 0.95) 100%);
            border: 2px solid #0ea5e9;
        }
        
        /* Customer Toast */
        .toast-dark {
            background: rgba(17, 17, 43, 0.9);
            border: 1px solid rgba(0, 212, 255, 0.3);
        }
        .toast-light {
            background: white;
            border: 1px solid rgba(14, 165, 233, 0.3);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Theme Toggle */
        .theme-toggle-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 8px 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Progress Bar */
        .progress-bar-bg-dark { background: rgba(255, 255, 255, 0.1); }
        .progress-bar-bg-light { background: rgba(0, 0, 0, 0.1); }
        .progress-fill {
            background: linear-gradient(90deg, #00d4ff, #10b981);
            height: 100%;
            border-radius: 3px;
        }
        
        /* Check Animation */
        .check-animated {
            animation: checkPulse 2s ease-in-out infinite;
        }
        @keyframes checkPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>

    <!-- Animated Background -->
    <div :class="$store.darkMode.on ? 'bg-dark-animated' : 'bg-light-animated'" class="fixed inset-0 -z-10">
        <template x-if="$store.darkMode.on">
            <div>
                <div class="particle particle-dark" style="left: 10%; animation-delay: 0s;"></div>
                <div class="particle particle-dark" style="left: 20%; animation-delay: 2s;"></div>
                <div class="particle particle-dark" style="left: 30%; animation-delay: 4s;"></div>
                <div class="particle particle-dark" style="left: 40%; animation-delay: 1s;"></div>
                <div class="particle particle-dark" style="left: 50%; animation-delay: 3s;"></div>
                <div class="particle particle-dark" style="left: 60%; animation-delay: 5s;"></div>
                <div class="particle particle-dark" style="left: 70%; animation-delay: 2.5s;"></div>
                <div class="particle particle-dark" style="left: 80%; animation-delay: 4.5s;"></div>
                <div class="particle particle-dark" style="left: 90%; animation-delay: 1.5s;"></div>
            </div>
        </template>
        <template x-if="!$store.darkMode.on">
            <div>
                <div class="particle particle-light" style="left: 10%; animation-delay: 0s;"></div>
                <div class="particle particle-light" style="left: 30%; animation-delay: 3s;"></div>
                <div class="particle particle-light" style="left: 50%; animation-delay: 1.5s;"></div>
                <div class="particle particle-light" style="left: 70%; animation-delay: 4s;"></div>
                <div class="particle particle-light" style="left: 90%; animation-delay: 2s;"></div>
            </div>
        </template>
    </div>

    <!-- Hero Section -->
    <section class="py-16 px-4 relative overflow-hidden">
        <div class="max-w-7xl mx-auto relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                
                <!-- Left Content -->
                <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)"
                     x-show="shown"
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0 translate-x-[-20px]"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    
                    <!-- Trust Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6"
                         :class="$store.darkMode.on ? 'trust-badge-dark' : 'trust-badge-light'">
                        <i class="fas fa-shield-alt" :class="$store.darkMode.on ? 'text-emerald-400' : 'text-emerald-600'"></i>
                        <span class="text-sm font-semibold">Trusted by 50,000+ websites worldwide</span>
                    </div>

                    <!-- Headline -->
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-black leading-tight mb-6"
                        :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">
                        Your Website<br/>
                        Deserves<br/>
                        <span :class="$store.darkMode.on ? 'gradient-text-dark' : 'gradient-text-light'">Lightning Speed</span> &<br/>
                        <span :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-500'">Rock-Solid</span><br/>
                        Reliability
                    </h1>

                    <!-- Description -->
                    <p class="text-lg mb-8 max-w-xl leading-relaxed"
                       :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">
                        Enterprise-grade hosting powered by OVHCloud. Free SSL, daily backups, 99.9% uptime, and 24/7 expert support included.
                    </p>

                    <!-- Feature Pills -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        @foreach(['Free SSL Certificate', 'Free Domain', 'Free CloudFlare CDN', '24/7 Expert Support', '30-Day Money Back', 'Free Migration'] as $feature)
                            <div class="flex items-center gap-2 px-4 py-2 rounded-full text-sm"
                                 :class="$store.darkMode.on ? 'bg-white/5 border border-white/10 text-gray-300' : 'bg-white border border-slate-200 text-slate-600 shadow-sm'">
                                <i class="fas fa-check-circle check-animated" :class="$store.darkMode.on ? 'text-emerald-400' : 'text-emerald-500'"></i>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- CTAs -->
                    <div class="flex flex-wrap gap-4">
                        <a href="#plans" class="btn-glow px-8 py-4 rounded-full font-bold text-lg uppercase tracking-wide inline-flex items-center gap-2">
                            <span>Explore Plans</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('contact') }}" 
                           class="px-8 py-4 rounded-full font-bold text-lg uppercase tracking-wide inline-flex items-center gap-2 transition-all"
                           :class="$store.darkMode.on ? 'btn-outline-dark' : 'btn-outline-light'">
                            <i class="fas fa-headset"></i>
                            <span>Talk to Sales</span>
                        </a>
                    </div>
                </div>

                <!-- Right Content - Stats Cards -->
                <div class="relative" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 300)"
                     x-show="shown"
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0 translate-x-[20px]"
                     x-transition:enter-end="opacity-100 translate-x-0">
                    
                    <!-- Security Score Card -->
                    <div class="rounded-2xl p-5 mb-4"
                         :class="$store.darkMode.on ? 'hosting-card-dark glow-cyan' : 'hosting-card-light border-cyan-200'">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                     :class="$store.darkMode.on ? 'bg-emerald-500/20' : 'bg-emerald-100'">
                                    <i class="fas fa-shield-alt" :class="$store.darkMode.on ? 'text-emerald-400' : 'text-emerald-600'"></i>
                                </div>
                                <div>
                                    <div class="font-semibold" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">Security Score: A+</div>
                                    <div class="text-xs" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-500'">Free SSL + Imunify360 Active</div>
                                </div>
                            </div>
                            <i class="fas fa-check-circle text-xl" :class="$store.darkMode.on ? 'text-emerald-400' : 'text-emerald-500'"></i>
                        </div>
                    </div>

                    <!-- Page Speed Card -->
                    <div class="rounded-2xl p-5 mb-4"
                         :class="$store.darkMode.on ? 'hosting-card-dark' : 'hosting-card-light'">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                 :class="$store.darkMode.on ? 'bg-cyan-500/20' : 'bg-sky-100'">
                                <i class="fas fa-bolt" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'"></i>
                            </div>
                            <div>
                                <div class="font-semibold" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">Page Speed</div>
                                <div class="text-xs" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-500'">95/100 — LiteSpeed NVMe SSD</div>
                            </div>
                        </div>
                        <div class="h-1.5 rounded-full overflow-hidden"
                             :class="$store.darkMode.on ? 'progress-bar-bg-dark' : 'progress-bar-bg-light'">
                            <div class="progress-fill" style="width: 95%;"></div>
                        </div>
                    </div>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="text-center p-4 rounded-xl"
                             :class="$store.darkMode.on ? 'hosting-card-dark' : 'hosting-card-light'">
                            <div class="text-2xl font-black" :class="$store.darkMode.on ? 'gradient-text-dark' : 'gradient-text-light'">99.9%</div>
                            <div class="text-xs mt-1" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-500'">Uptime</div>
                        </div>
                        <div class="text-center p-4 rounded-xl"
                             :class="$store.darkMode.on ? 'hosting-card-dark' : 'hosting-card-light'">
                            <div class="text-2xl font-black" :class="$store.darkMode.on ? 'gradient-text-dark' : 'gradient-text-light'">50K+</div>
                            <div class="text-xs mt-1" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-500'">Customers</div>
                        </div>
                        <div class="text-center p-4 rounded-xl"
                             :class="$store.darkMode.on ? 'hosting-card-dark' : 'hosting-card-light'">
                            <div class="text-2xl font-black" :class="$store.darkMode.on ? 'gradient-text-dark' : 'gradient-text-light'">&lt;30s</div>
                            <div class="text-xs mt-1" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-500'">Avg Support</div>
                        </div>
                    </div>

                    <!-- Live Customer Toast -->
                    <div class="flex items-center gap-3 p-4 rounded-xl"
                         :class="$store.darkMode.on ? 'toast-dark' : 'toast-light'">
                        <span class="text-2xl">🇮🇳</span>
                        <div>
                            <div class="text-sm font-medium" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">New customer from Mumbai</div>
                            <div class="text-xs" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'">Just purchased VPS Pro</div>
                        </div>
                        <div class="ml-auto">
                            <span class="w-2 h-2 rounded-full animate-pulse" :class="$store.darkMode.on ? 'bg-emerald-500' : 'bg-emerald-500'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hosting Plans Section -->
    <section id="plans" class="py-20 px-4 relative">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <span class="inline-block px-4 py-1 rounded-full text-sm font-semibold uppercase tracking-wider mb-4"
                      :class="$store.darkMode.on ? 'bg-cyan-500/10 border border-cyan-500/30 text-cyan-400' : 'bg-sky-100 border border-sky-200 text-sky-600'">
                    Choose Your Power
                </span>
                <h2 class="text-4xl md:text-5xl font-black mb-4"
                    :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">
                    Hosting <span :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-500'">Solutions</span>
                </h2>
                <p class="max-w-2xl mx-auto"
                   :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">
                    From startup websites to enterprise applications, we have the perfect infrastructure for you.
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Web Hosting -->
                <div class="p-8 rounded-3xl relative group"
                     :class="$store.darkMode.on ? 'plan-card-dark' : 'plan-card-light'">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6"
                         :class="$store.darkMode.on ? 'icon-box-dark' : 'icon-box-light'">
                        <i class="fas fa-globe text-2xl" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-2" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">Web Hosting</h3>
                    <p class="text-sm mb-6" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">Perfect for small to medium websites</p>
                    
                    <div class="mb-6">
                        <span class="text-4xl font-black" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'">$4.99</span>
                        <span :class="$store.darkMode.on ? 'text-gray-500' : 'text-slate-400'">/mo</span>
                    </div>

                    <ul class="space-y-3 mb-8">
                        @foreach(['1 Website', '10 GB NVMe SSD', 'Unmetered Bandwidth', 'Free SSL Certificate', 'cPanel Control Panel', 'Daily Backups'] as $f)
                            <li class="flex items-center gap-3 text-sm" :class="$store.darkMode.on ? 'text-gray-300' : 'text-slate-600'">
                                <i class="fas fa-check" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-500'"></i>
                                {{ $f }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('web-hosting') }}" 
                       class="block w-full py-4 rounded-xl font-bold text-center uppercase tracking-wide transition-all"
                       :class="$store.darkMode.on ? 'bg-white/5 border border-white/20 text-white hover:bg-white/10 hover:border-cyan-500/50' : 'bg-slate-100 border border-slate-200 text-slate-700 hover:bg-sky-50 hover:border-sky-300'">
                        View Plans
                    </a>
                </div>

                <!-- VPS - Most Popular -->
                <div class="p-8 rounded-3xl relative group"
                     :class="$store.darkMode.on ? 'plan-popular-dark' : 'plan-popular-light'">
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="px-6 py-2 rounded-full text-xs font-bold uppercase tracking-wider shadow-lg"
                              :class="$store.darkMode.on ? 'bg-gradient-to-r from-cyan-500 to-purple-500 text-white shadow-cyan-500/30' : 'bg-gradient-to-r from-sky-500 to-purple-500 text-white'">
                            Most Popular
                        </span>
                    </div>
                    
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6 mt-2"
                         :class="$store.darkMode.on ? 'bg-gradient-to-br from-cyan-500/30 to-purple-500/30 border border-cyan-400/50' : 'bg-gradient-to-br from-sky-100 to-purple-100 border border-sky-300'">
                        <i class="fas fa-server text-2xl" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-2" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">VPS Servers</h3>
                    <p class="text-sm mb-6" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">Dedicated resources with full control</p>
                    
                    <div class="mb-6">
                        @php
                            $lowestVps = $vpsPlans->sortBy('price_monthly')->first();
                            $lowestVpsPrice = $lowestVps ? ($lowestVps->base_price_usd ?? round($lowestVps->price_monthly / 83, 2)) : 9.99;
                        @endphp
                        <span class="text-4xl font-black" :class="$store.darkMode.on ? 'gradient-text-dark' : 'gradient-text-light'">${{ number_format($lowestVpsPrice, 2) }}</span>
                        <span :class="$store.darkMode.on ? 'text-gray-500' : 'text-slate-400'">/mo</span>
                    </div>

                    <script>
                        const plans = [
                            @foreach($vpsPlans as $plan)
                            {
                                name: '{{ $plan->name }}',
                                price: {{ $plan->base_price_usd ?? $plan->price_monthly ?? 0 }},
                                currency: 'USD',
                                period: 'mo',
                                features: [
                                    '{{ $plan->cpu_cores }} vCPU Cores',
                                    '{{ $plan->memory_gb }} GB RAM',
                                    '{{ $plan->disk_gb }} GB {{ strtoupper($plan->disk_type ?? "SSD") }}',
                                    '{{ $plan->bandwidth_tb ?? "2" }} TB Bandwidth',
                                    '{{ $plan->uplink_mbps ?? "1000" }} Mbps Uplink',
                                    '24/7 Support'
                                    @if($plan->has_ddos_protection ?? false)
                                    , 'DDoS Protection'
                                    @endif
                                ],
                                link: '{{ route("vps-plans.show", $plan->slug) }}',
                                popular: {{ ($loop->index === 1) ? 'true' : 'false' }}
                            }{{ !$loop->last ? ',' : '' }}
                            @endforeach
                        ];
                    </script>

                    <ul class="space-y-3 mb-8">
                        @foreach($vpsPlans as $plan)
                            @php $features = $plan->display_features; @endphp
                            @foreach($features as $feature)
                            <li class="flex items-center gap-3 text-sm" :class="$store.darkMode.on ? 'text-gray-300' : 'text-slate-600'">
                                <i class="fas fa-check" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-500'"></i>
                                {{ $feature }}
                            </li>
                            @endforeach
                        @endforeach
                    </ul>

                    <a href="{{ route('vps-hosting') }}" class="btn-glow block w-full py-4 rounded-xl font-bold text-center uppercase tracking-wide">
                        Get Started
                    </a>
                </div>

                <!-- Dedicated -->
                <div class="p-8 rounded-3xl relative group"
                     :class="$store.darkMode.on ? 'plan-card-dark' : 'plan-card-light'">
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center mb-6"
                         :class="$store.darkMode.on ? 'icon-box-dark' : 'icon-box-light'">
                        <i class="fas fa-database text-2xl" :class="$store.darkMode.on ? 'text-purple-400' : 'text-purple-600'"></i>
                    </div>
                    <h3 class="text-2xl font-black mb-2" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">Dedicated</h3>
                    <p class="text-sm mb-6" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">Bare-metal enterprise power</p>
                    
                    <div class="mb-6">
                        <span class="text-4xl font-black" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'">$89.99</span>
                        <span :class="$store.darkMode.on ? 'text-gray-500' : 'text-slate-400'">/mo</span>
                    </div>

                    <ul class="space-y-3 mb-8">
                        @foreach(['8 vCPU Cores', '32 GB RAM', '500 GB NVMe SSD', '10 Gbps Network', 'Hardware RAID', '99.99% SLA'] as $f)
                            <li class="flex items-center gap-3 text-sm" :class="$store.darkMode.on ? 'text-gray-300' : 'text-slate-600'">
                                <i class="fas fa-check" :class="$store.darkMode.on ? 'text-purple-400' : 'text-purple-500'"></i>
                                {{ $f }}
                            </li>
                        @endforeach
                    </ul>

                    <a href="/dedicated-servers.html" 
                       class="block w-full py-4 rounded-xl font-bold text-center uppercase tracking-wide transition-all"
                       :class="$store.darkMode.on ? 'bg-white/5 border border-white/20 text-white hover:bg-white/10 hover:border-purple-500/50' : 'bg-slate-100 border border-slate-200 text-slate-700 hover:bg-purple-50 hover:border-purple-300'">
                        View Plans
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section class="py-20 px-4 relative" :class="$store.darkMode.on ? '' : 'bg-white/50'">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-black mb-4" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">
                    Why Choose <span :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-500'">Believoo</span>
                </h2>
                <p :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">Enterprise-grade infrastructure with premium support</p>
            </div>

            <div class="grid md:grid-cols-4 gap-6">
                @foreach([
                    ['fa-bolt', 'Lightning Fast', 'NVMe SSD with LiteSpeed'],
                    ['fa-shield-alt', 'Secure by Default', 'Free SSL, DDoS protection'],
                    ['fa-globe', 'Global Network', '15+ data centers'],
                    ['fa-headset', '24/7 Support', 'Expert help anytime'],
                    ['fa-rocket', 'Instant Deploy', 'Ready in 60 seconds'],
                    ['fa-sync-alt', 'Auto Backups', 'Daily automated'],
                    ['fa-chart-line', '99.9% Uptime', 'Enterprise SLA'],
                    ['fa-code-branch', 'Dev Friendly', 'SSH, Git, WP-CLI'],
                ] as $f)
                    <div class="p-6 rounded-2xl text-center transition-all hover:-translate-y-1"
                         :class="$store.darkMode.on ? 'hosting-card-dark hover:border-cyan-500/30' : 'hosting-card-light hover:border-sky-300'">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-4"
                             :class="$store.darkMode.on ? 'icon-box-dark' : 'icon-box-light'">
                            <i class="fas {{ $f[0] }} text-xl" :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'"></i>
                        </div>
                        <h4 class="font-bold mb-1 text-sm uppercase" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">{{ $f[1] }}</h4>
                        <p class="text-sm" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-500'">{{ $f[2] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Data Centers -->
    <section class="py-20 px-4 relative">
        <div class="max-w-7xl mx-auto">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <span class="text-sm font-bold uppercase tracking-wider"
                          :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'">Global Infrastructure</span>
                    <h2 class="text-4xl font-black mt-2 mb-6" :class="$store.darkMode.on ? 'text-white' : 'text-slate-900'">
                        15+ Data Centers<br/>
                        <span :class="$store.darkMode.on ? 'gradient-text-dark' : 'gradient-text-light'">Worldwide</span>
                    </h2>
                    <p class="mb-6" :class="$store.darkMode.on ? 'text-gray-400' : 'text-slate-600'">
                        Choose from 15 premium locations powered by OVHCloud. Deploy closer to your users.
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach([['🇫🇷','Paris'],['🇩🇪','Frankfurt'],['🇬🇧','London'],['🇺🇸','New York'],['🇸🇬','Singapore'],['🇦🇺','Sydney'],['🇮🇳','Mumbai'],['🇨🇦','Toronto']] as $dc)
                            <div class="flex items-center gap-3 p-3 rounded-xl"
                                 :class="$store.darkMode.on ? 'hosting-card-dark' : 'hosting-card-light'">
                                <span class="text-2xl">{{ $dc[0] }}</span>
                                <span class="text-sm font-medium" :class="$store.darkMode.on ? 'text-white' : 'text-slate-700'">{{ $dc[1] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="rounded-3xl p-8 aspect-square flex items-center justify-center"
                     :class="$store.darkMode.on ? 'hosting-card-dark' : 'hosting-card-light'">
                    <div class="relative w-64 h-64">
                        <div class="absolute inset-0 rounded-full border-2" :class="$store.darkMode.on ? 'border-cyan-500/30' : 'border-sky-300'"></div>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <i class="fas fa-globe-americas text-6xl" :class="$store.darkMode.on ? 'text-cyan-400/30' : 'text-sky-400/40'"></i>
                        </div>
                        @foreach([['top-0','left-1/2','bg-cyan-500'], ['top-1/4','right-0','bg-purple-500'], ['bottom-1/4','right-4','bg-emerald-500'], ['bottom-0','left-1/3','bg-cyan-500'], ['top-1/3','left-0','bg-purple-500']] as $d)
                            <div class="absolute w-3 h-3 rounded-full shadow-lg animate-pulse {{ $d[0] }} {{ $d[1] }} {{ $d[2] }}"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 relative">
        <div class="max-w-4xl mx-auto">
            <div class="relative rounded-3xl p-12 text-center overflow-hidden"
                 :class="$store.darkMode.on ? '' : 'shadow-2xl'"
                 style="background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%);">
                <div class="relative z-10">
                    <h2 class="text-3xl md:text-4xl font-black text-white mb-4">Ready to Launch?</h2>
                    <p class="text-blue-100 mb-8">Get online in minutes. Choose your hosting solution today.</p>
                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ route('vps-hosting') }}" class="bg-white text-sky-600 px-8 py-4 rounded-xl font-bold uppercase inline-flex items-center justify-center gap-2 hover:shadow-xl transition-all">
                            <i class="fas fa-rocket"></i> Get Started Now
                        </a>
                        <a href="{{ route('contact') }}" class="bg-white/10 text-white border-2 border-white/30 px-8 py-4 rounded-xl font-bold uppercase hover:bg-white/20 transition-all">
                            Contact Sales
                        </a>
                    </div>
                    <div class="flex flex-wrap justify-center gap-6 mt-8 text-white/80 text-sm">
                        <span class="flex items-center gap-2"><i class="fas fa-check-circle"></i> No Credit Card Required</span>
                        <span class="flex items-center gap-2"><i class="fas fa-check-circle"></i> 30-Day Money Back</span>
                        <span class="flex items-center gap-2"><i class="fas fa-check-circle"></i> Free Migration</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Note -->
    <section class="py-8 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-sm" :class="$store.darkMode.on ? 'text-gray-500' : 'text-slate-500'">
                <i class="fas fa-info-circle mr-2"></i>
                All hosting services powered by <span :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'">OVHCloud</span> and managed by <span :class="$store.darkMode.on ? 'text-cyan-400' : 'text-sky-600'">Believoo Systems</span>
            </p>
        </div>
    </section>
</div>
