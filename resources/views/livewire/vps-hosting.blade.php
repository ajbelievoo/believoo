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
                <i class="fas fa-server text-3xl text-sky-600 dark:text-cyan-400"></i>
            </div>
            <span class="text-xs font-black uppercase tracking-widest text-sky-600 dark:text-cyan-400">Cloud Infrastructure</span>
            <h1 class="text-5xl md:text-7xl font-black uppercase tracking-tighter mb-6">
                VPS <span class="text-sky-600 dark:text-cyan-400">Hosting</span>
            </h1>
            <p class="text-xl max-w-2xl mx-auto mb-6 text-slate-600 dark:text-gray-400">
                High-performance virtual private servers with NVMe storage, unlimited traffic, and enterprise-grade infrastructure powered by GHC Cloud.
            </p>
            
            {{-- Currency Toggle --}}
            @php
                $userCurrency = session('currency', 'USD');
                $rate = \App\Models\ExchangeRate::getUsdToInrRate();
            @endphp
            <div class="inline-flex rounded-full p-1 mt-4 bg-slate-100 border border-slate-200 dark:bg-white/5 dark:border-white/10">
                <button 
                    onclick="window.location.href='?currency=USD'"
                    class="px-6 py-2 rounded-full text-sm font-bold uppercase transition-all {{ $userCurrency === 'USD' ? 'bg-sky-500 text-white dark:bg-cyan-500 dark:text-black' : 'text-slate-500 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white' }}">
                    🇺🇸 USD ($)
                </button>
                <button 
                    onclick="window.location.href='?currency=INR'"
                    class="px-6 py-2 rounded-full text-sm font-bold uppercase transition-all {{ $userCurrency === 'INR' ? 'bg-sky-500 text-white dark:bg-cyan-500 dark:text-black' : 'text-slate-500 hover:text-slate-900 dark:text-gray-400 dark:hover:text-white' }}">
                    🇮🇳 INR (₹)
                </button>
            </div>
            <p class="text-xs mt-2 text-slate-500 dark:text-gray-500">
                <i class="fas fa-sync-alt mr-1"></i> 1 USD = ₹{{ number_format($rate, 2) }} • Updated hourly
            </p>
        </div>
    </section>

    <!-- VPS Plan Cards -->
    <section class="py-16 px-4" x-data="{ selected: null }">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($vpsPlans as $index => $plan)
                    @php
                        $isPopular = $plan->slug === 'vps-2' || $plan->slug === 'vps-vps-2';
                        $price = $plan->price ?? 0;
                        // $userCurrency already defined at top using session
                        $exchangeRate = $rate;
                        if ($userCurrency === 'INR') {
                            $displayPrice = round($price * $exchangeRate);
                            $priceSymbol = '₹';
                            $priceFormatted = number_format($displayPrice, 0);
                        } else {
                            $displayPrice = $price;
                            $priceSymbol = '$';
                            $priceFormatted = number_format($price, 2);
                        }
                    @endphp
                    <div @click="selected = {{ $index }}"
                         class="rounded-3xl p-8 border-2 cursor-pointer transition-all duration-300 relative border-slate-200 bg-white shadow-lg hover:border-sky-300 dark:border-white/10 dark:bg-white/5 dark:shadow-none dark:hover:border-white/20 {{ $isPopular ? 'mt-0' : '' }}">
                        @if($isPopular)
                            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                                <span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider bg-sky-500 text-white dark:bg-cyan-500 dark:text-black">Best Value</span>
                            </div>
                        @endif
                        <div class="mb-6 {{ $isPopular ? 'mt-4' : '' }}">
                            <h3 class="text-xl font-black uppercase mb-2 leading-tight text-slate-900 dark:text-white">{{ $plan->title }}</h3>
                            <p class="text-sm line-clamp-2 text-slate-600 dark:text-gray-400">{{ $plan->description }}</p>
                        </div>
                        <div class="text-4xl font-black mb-6 text-sky-600 dark:text-cyan-400">
                            {{ $priceSymbol }}{{ $priceFormatted }}<span class="text-base font-medium text-slate-400 dark:text-gray-500">/mo</span>
                            @if($userCurrency === 'INR')
                                <span class="text-xs block font-normal mt-1 text-slate-400 dark:text-gray-500">(${{ number_format($price, 2) }} USD)</span>
                            @else
                                <span class="text-xs block font-normal mt-1 text-slate-400 dark:text-gray-500">(₹{{ number_format($price * $exchangeRate, 0) }} INR)</span>
                            @endif
                        </div>
                        {{-- Spec Grid --}}
                        @php
                            $specs = [];
                            foreach ($plan->features ?? [] as $feature) {
                                $f = is_array($feature) ? ($feature['feature'] ?? '') : $feature;
                                if (preg_match('/(\d+)\s*(vCPU|CPU)/i', $f, $m)) $specs['cpu'] = $m[1] . ' vCPU';
                                elseif (preg_match('/(\d+\s*GB)\s*RAM/i', $f, $m)) $specs['ram'] = $m[1] . ' RAM';
                                elseif (preg_match('/(\d+\s*GB)\s*(NVMe|SSD|Storage)/i', $f, $m)) $specs['storage'] = $m[1] . ' SSD';
                                elseif (preg_match('/(\d+\s*GB|Unlimited)\s*Bandwidth/i', $f, $m)) $specs['bandwidth'] = $m[1] . ' BW';
                            }
                        @endphp
                        @if(count($specs) >= 2)
                            <div class="grid grid-cols-2 gap-3 mb-6">
                                @foreach([
                                    ['key' => 'cpu', 'label' => 'vCPU'],
                                    ['key' => 'ram', 'label' => 'RAM'],
                                    ['key' => 'storage', 'label' => 'Storage'],
                                    ['key' => 'bandwidth', 'label' => 'Bandwidth'],
                                ] as $spec)
                                    @if(isset($specs[$spec['key']]))
                                        <div class="rounded-xl p-3 text-center bg-slate-50 border border-slate-200 dark:bg-white/5 dark:border-white/10">
                                            <div class="text-sm font-black text-slate-900 dark:text-white">{{ $specs[$spec['key']] }}</div>
                                            <div class="text-[10px] font-black uppercase tracking-widest mt-1 text-slate-400 dark:text-gray-500">{{ $spec['label'] }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <ul class="space-y-3 mb-8">
                            @foreach($plan->features ?? [] as $feature)
                                @php $f = is_array($feature) ? ($feature['feature'] ?? '') : $feature; @endphp
                                @if($f)
                                    <li class="flex items-center gap-3 text-sm text-slate-600 dark:text-gray-300">
                                        <i class="fas fa-check text-xs flex-shrink-0 text-sky-500 dark:text-cyan-400"></i>
                                        {{ $f }}
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                        @auth
                            <a href="{{ route('checkout', $plan->slug) }}"
                               class="w-full text-center block text-sm py-3 rounded-xl font-bold uppercase transition-all
                                {{ $isPopular ? 'bg-gradient-to-r from-sky-500 to-blue-600 text-white dark:from-cyan-500 dark:to-blue-600 dark:text-black' : 'bg-slate-100 border border-slate-200 text-slate-700 hover:bg-sky-50 dark:bg-white/10 dark:border-white/20 dark:text-white dark:hover:bg-white/20' }}">
                                Deploy Now
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

    <!-- Features -->
    <section class="py-16 px-4 bg-slate-100 dark:bg-white/5" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-12">
                <h2 class="text-4xl font-black uppercase tracking-tighter mb-4 text-slate-900 dark:text-white">Why Choose Our <span class="text-sky-600 dark:text-cyan-400">VPS</span></h2>
                <p class="text-slate-600 dark:text-gray-400">Enterprise-grade infrastructure with premium features</p>
            </div>
            <div class="grid md:grid-cols-4 gap-6">
                @foreach([
                    ['icon' => 'fas fa-bolt', 'title' => '99.99% Uptime', 'desc' => 'Guaranteed availability with SLA'],
                    ['icon' => 'fas fa-shield-alt', 'title' => 'DDoS Protection', 'desc' => 'Advanced security included'],
                    ['icon' => 'fas fa-hdd', 'title' => 'NVMe SSD', 'desc' => 'Ultra-fast storage'],
                    ['icon' => 'fas fa-headset', 'title' => 'Expert Support', 'desc' => 'Assistance when you need it'],
                ] as $feature)
                    <div class="text-center p-6 rounded-2xl transition-all hover:-translate-y-1 bg-white border border-slate-200 hover:border-sky-300 shadow-sm dark:bg-white/5 dark:border-white/10 dark:hover:border-cyan-500/30 dark:shadow-none">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-sky-100 dark:bg-cyan-500/10">
                            <i class="{{ $feature['icon'] }} text-2xl text-sky-600 dark:text-cyan-400"></i>
                        </div>
                        <h4 class="font-black mb-2 uppercase text-sm tracking-widest text-slate-900 dark:text-white">{{ $feature['title'] }}</h4>
                        <p class="text-sm text-slate-600 dark:text-gray-400">{{ $feature['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-20 px-4" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-3xl mx-auto"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="text-center mb-16">
                <span class="text-xs font-black uppercase tracking-widest text-sky-600 dark:text-cyan-400">Common Questions</span>
                <h2 class="text-4xl font-black uppercase tracking-tighter text-slate-900 dark:text-white">VPS <span class="text-sky-600 dark:text-cyan-400">FAQ</span></h2>
            </div>
            <div class="space-y-3" x-data="{ open: null }">
                @foreach([
                    ['q' => 'What is a VPS?', 'a' => 'A Virtual Private Server (VPS) is a virtualized server that mimics a dedicated server within a shared hosting environment. You get dedicated resources and full root access.'],
                    ['q' => 'Can I install custom software?', 'a' => 'Yes, you have full root access to your VPS and can install any software compatible with your chosen operating system.'],
                    ['q' => 'What operating systems are available?', 'a' => 'We support Ubuntu, Debian, CentOS, and Windows Server. You can reinstall your OS at any time from the control panel.'],
                    ['q' => 'Is managed support available?', 'a' => 'Yes, we offer managed VPS plans where our team handles server updates, security patches, and monitoring for you.'],
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

    <section class="py-8 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <p class="text-sm text-slate-500 dark:text-gray-500">
                <i class="fas fa-info-circle mr-2"></i>
                All prices are in USD ($). Infrastructure powered by <span class="text-sky-600 dark:text-cyan-400">GHC Cloud</span>. Managed by <span class="text-sky-600 dark:text-cyan-400">Believoo</span>.
            </p>
        </div>
    </section>
</div>
