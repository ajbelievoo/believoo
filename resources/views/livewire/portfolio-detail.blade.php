<div class="pt-32">
    <!-- Hero Section -->
    <section class="relative py-20 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 relative z-10">
            <div x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)" x-show="shown" x-transition:enter="transition ease-out duration-1000" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0">
                <span class="section-label">{{ $portfolio->category ?? 'Case Study' }}</span>
                <h1 class="text-6xl md:text-9xl font-black tracking-tighter uppercase mb-12 leading-none">
                    {{ $portfolio->title }}
                </h1>
                
                <div class="grid md:grid-cols-4 gap-8 mb-20">
                    <div class="card">
                        <span class="text-gray-500 font-black uppercase tracking-widest text-[10px] mb-2 block">Client</span>
                        <div class="text-xl font-bold">{{ $portfolio->client_name ?? 'Confidential' }}</div>
                    </div>
                    <div class="card">
                        <span class="text-gray-500 font-black uppercase tracking-widest text-[10px] mb-2 block">Timeline</span>
                        <div class="text-xl font-bold">{{ $portfolio->timeline ?? '4 Months' }}</div>
                    </div>
                    <div class="card">
                        <span class="text-gray-500 font-black uppercase tracking-widest text-[10px] mb-2 block">Industry</span>
                        <div class="text-xl font-bold">{{ $portfolio->industry ?? 'Infrastructure' }}</div>
                    </div>
                    <div class="card">
                        <span class="text-gray-500 font-black uppercase tracking-widest text-[10px] mb-2 block">Service</span>
                        <div class="text-xl font-bold">{{ $portfolio->service_type ?? 'Full-Stack' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Image -->
    <section class="max-w-7xl mx-auto px-4 mb-32">
        <div class="aspect-video rounded-[4rem] overflow-hidden glass border-0 shadow-2xl">
            <img src="{{ asset('storage/' . $portfolio->image) }}" class="w-full h-full object-cover" loading="lazy" alt="{{ $portfolio->title }}">
        </div>
    </section>

    <!-- Case Study Content -->
    <section class="py-32 bg-dark-100/50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-20">
                <div class="md:col-span-2 space-y-24">
                    <div>
                        <h2 class="text-4xl font-black uppercase mb-10 flex items-center gap-6">
                            <span class="w-12 h-1 bg-electric-blue rounded-full"></span>
                            The Challenge
                        </h2>
                        <div class="text-xl text-gray-400 leading-loose prose prose-invert max-w-none">
                            {!! $portfolio->problem ?? 'The client required a high-availability infrastructure capable of handling millions of requests with zero latency issues while maintaining strict security compliance.' !!}
                        </div>
                    </div>
                    <div>
                        <h2 class="text-4xl font-black uppercase mb-10 flex items-center gap-6">
                            <span class="w-12 h-1 bg-electric-violet rounded-full"></span>
                            The Solution
                        </h2>
                        <div class="text-xl text-gray-400 leading-loose prose prose-invert max-w-none">
                            {!! $portfolio->solution ?? 'We architected a distributed microservices environment using Kubernetes and AWS, implementing custom load balancing and end-to-end encryption protocols.' !!}
                        </div>
                    </div>
                    <div>
                        <h2 class="text-4xl font-black uppercase mb-10 flex items-center gap-6">
                            <span class="w-12 h-1 bg-white rounded-full"></span>
                            The Result
                        </h2>
                        <div class="text-xl text-gray-400 leading-loose prose prose-invert max-w-none">
                            {!! $portfolio->result ?? 'The platform achieved 99.99% uptime during peak traffic, with a 40% reduction in server response times and enhanced security posture.' !!}
                        </div>
                    </div>
                </div>
                
                <div class="space-y-12">
                    <div class="p-12 rounded-[3rem] glass border-white/5">
                        <h4 class="text-2xl font-black uppercase mb-8">Technologies Used</h4>
                        <div class="flex flex-wrap gap-4">
                            @php
                                $techs = $portfolio->tech_stack ?? [];
                            @endphp
                            @foreach($techs as $tech)
                                <div class="badge-gray">
                                    @if(is_array($tech) && isset($tech['icon']))
                                        <i class="{{ $tech['icon'] }} text-electric-blue mr-1"></i>
                                        {{ $tech['name'] }}
                                    @else
                                        {{ $tech }}
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    @if($portfolio->url)
                        <a href="{{ $portfolio->url }}" target="_blank" class="btn-primary w-full text-center block">
                            Visit Live Project
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Navigation -->
    <section class="py-20 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 flex justify-between items-center">
            <a href="{{ route('portfolio.index') }}" class="text-gray-400 hover:text-white transition-colors font-black uppercase tracking-widest flex items-center gap-4">
                <i class="fas fa-arrow-left"></i> All Projects
            </a>
            <div class="flex gap-12">
                <!-- Next project link could go here -->
            </div>
        </div>
    </section>
</div>
