<div>
    <!-- Hero -->
    <section class="relative py-28 bg-slate-900 text-white overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/10 to-slate-900"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="inline-block text-amber-400 font-semibold tracking-wider uppercase text-sm mb-4">Our Story</span>
            <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">Architecting <span class="text-amber-400">The Future</span></h1>
            <p class="text-lg md:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
                {{ $content['about_hero_text'] ?? 'Believoo is a technology company specializing in cloud infrastructure, software solutions, and digital growth for businesses in India and around the world.' }}
            </p>
        </div>
    </section>

    <!-- Story -->
    <section class="py-24 bg-white dark:bg-slate-900 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div class="rounded-3xl overflow-hidden shadow-2xl border border-slate-100 dark:border-slate-700">
                    <img src="https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80&w=1000" loading="lazy" class="w-full h-[500px] object-cover" alt="Believoo technology infrastructure">
                </div>
                <div>
                    <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm mb-2 block">Who We Are</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-8">{{ $content['about_section_title'] ?? 'The Believoo Philosophy' }}</h2>
                    <div class="prose prose-slate dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 leading-relaxed">
                        {!! $content['about_section_content'] ?? '<p>We believe technology should be fast, reliable, and beautiful. Our team of engineers, designers, and cloud architects builds solutions that help businesses grow with confidence.</p><p>From enterprise-grade VPS clusters to mobile apps and AI-powered tools, we deliver infrastructure and software that does not just support your business, but moves it forward.</p>' !!}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="py-20 bg-slate-50 dark:bg-slate-800 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach([
                    ['stat' => '99.9%', 'label' => 'Uptime SLA'],
                    ['stat' => '500+', 'label' => 'Projects Delivered'],
                    ['stat' => '15min', 'label' => 'Response Time'],
                    ['stat' => '24/7', 'label' => 'Elite Support']
                ] as $item)
                <div class="bg-white dark:bg-slate-900 rounded-2xl p-8 text-center border border-slate-100 dark:border-slate-700 shadow-sm">
                    <div class="text-4xl font-bold text-amber-600 mb-2">{{ $item['stat'] }}</div>
                    <div class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $item['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Team -->
    <section class="py-24 bg-white dark:bg-slate-900 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-amber-600 font-semibold tracking-wider uppercase text-sm mb-2 block">Meet The Team</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white">The People Behind Believoo</h2>
                <p class="text-slate-500 dark:text-slate-400 mt-4 max-w-2xl mx-auto">A passionate team of engineers, creators, and strategists building technology for the next generation.</p>
            </div>

            @if($team->isNotEmpty())
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($team as $member)
                    <div class="group bg-slate-50 dark:bg-slate-800 rounded-3xl p-8 border border-slate-100 dark:border-slate-700 hover:shadow-xl hover:-translate-y-1 transition-all">
                        <div class="flex items-start gap-6 mb-6">
                            <img src="{{ $member->photo_url }}" loading="lazy" class="w-24 h-24 rounded-2xl object-cover border-2 border-amber-100 dark:border-slate-600" alt="{{ $member->name }}">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white truncate">{{ $member->name }}</h3>
                                <div class="text-amber-600 font-semibold text-sm">{{ $member->role }}</div>
                            </div>
                        </div>
                        <p class="text-slate-500 dark:text-slate-300 leading-relaxed mb-6 line-clamp-4">{{ $member->bio }}</p>

                        <div class="flex flex-wrap gap-3">
                            @if($member->facebook)
                                <a href="{{ $member->facebook }}" target="_blank" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 text-slate-400 dark:text-slate-300 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                            @endif
                            @if($member->twitter)
                                <a href="{{ $member->twitter }}" target="_blank" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 text-slate-400 dark:text-slate-300 flex items-center justify-center hover:bg-sky-500 hover:text-white transition-all">
                                    <i class="fab fa-twitter"></i>
                                </a>
                            @endif
                            @if($member->linkedin)
                                <a href="{{ $member->linkedin }}" target="_blank" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 text-slate-400 dark:text-slate-300 flex items-center justify-center hover:bg-blue-700 hover:text-white transition-all">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            @endif
                            @if($member->instagram)
                                <a href="{{ $member->instagram }}" target="_blank" class="w-10 h-10 rounded-xl bg-white dark:bg-slate-700 text-slate-400 dark:text-slate-300 flex items-center justify-center hover:bg-pink-600 hover:text-white transition-all">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-20 bg-slate-50 dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700">
                    <i class="fas fa-users text-5xl text-slate-300 dark:text-slate-600 mb-4"></i>
                    <p class="text-slate-500 dark:text-slate-400">Team information is being updated. Check back soon.</p>
                </div>
            @endif
        </div>
    </section>
</div>
