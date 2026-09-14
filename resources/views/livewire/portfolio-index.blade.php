<div class="py-20 bg-slate-50">
    <!-- Hero Content (under layout page hero) -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 100)"
                 x-show="shown"
                 x-transition:enter="transition ease-out duration-700"
                 x-transition:enter-start="opacity-0 translate-y-6"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm">Our Legacy</span>
                <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-6">
                    Proven <span class="text-amber-600">Impact</span>
                </h1>
                <p class="text-lg text-slate-500 max-w-3xl mx-auto leading-relaxed">
                    A selection of high-performance infrastructures and digital products we've architected for industry leaders.
                </p>
            </div>
        </div>
    </section>

    <!-- Projects Grid -->
    <section class="py-12" x-data="{ shown: false }" x-intersect.once="shown = true">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
             :class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8'"
             class="transition-all duration-700 ease-out">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($portfolios as $project)
                    <a href="{{ route('portfolio.show', $project->slug) }}"
                       class="group block bg-white rounded-2xl overflow-hidden border border-slate-100 hover:border-amber-300 hover:shadow-xl transition-all">
                        <div class="aspect-[4/3] overflow-hidden bg-slate-100 relative">
                            <img src="{{ asset('storage/' . $project->image) }}"
                                 alt="{{ $project->title }}"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='{{ asset('images/placeholder.svg') }}'; this.classList.add('object-contain', 'p-8');"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                        </div>
                        <div class="p-6">
                            <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold mb-3">{{ $project->category ?? 'Project' }}</span>
                            <h4 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-amber-600 transition-colors">{{ $project->title }}</h4>
                            <p class="text-slate-500 text-sm line-clamp-2">
                                {!! strip_tags($project->description) !!}
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative rounded-3xl bg-slate-900 text-white p-12 md:p-16 overflow-hidden text-center">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-64 h-64 bg-amber-500 rounded-full opacity-20 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-64 h-64 bg-amber-500 rounded-full opacity-10 blur-3xl"></div>
                <div class="relative z-10">
                    <span class="text-amber-400 font-semibold tracking-wider uppercase text-sm">Ready to build?</span>
                    <h2 class="text-3xl md:text-4xl font-bold mb-4 mt-2">
                        Start Your <span class="text-amber-500">Project</span>
                    </h2>
                    <p class="text-slate-300 text-lg max-w-2xl mx-auto mb-8">
                        Join the companies that trust Believoo to architect their digital future.
                    </p>
                    <a href="{{ route('home') }}#inquiry" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition shadow-lg shadow-amber-500/25">
                        Initiate Project
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
