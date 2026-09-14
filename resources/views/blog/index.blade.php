<x-layouts.believoo
    title="Believoo Blog - VPS, Hosting, Streaming & Tech Tips"
    description="Read the latest articles on VPS hosting, web hosting, live streaming, cloud infrastructure and software development from Believoo."
    keywords="Believoo blog, VPS hosting tips, live streaming guide, web hosting India, cloud tips">

<div class="pt-24 pb-20 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-900 min-h-screen">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 dark:text-white mb-4">Believoo Blog</h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">VPS hosting, live streaming, web hosting and cloud tips for growing businesses.</p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($posts as $post)
            <article class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-lg transition-all">
                @if($post->featuredImageUrl())
                <div class="h-48 overflow-hidden">
                    <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="w-full h-full object-cover" loading="lazy">
                </div>
                @endif
                <div class="p-6">
                    <span class="text-amber-600 text-xs font-bold uppercase tracking-wider">{{ $post->published_at?->format('M d, Y') }}</span>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-2 mb-3">
                        <a href="{{ route('blog.show', $post) }}" class="hover:text-amber-600 transition-colors">{{ $post->title }}</a>
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 line-clamp-3 mb-4">{{ $post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 150) }}</p>
                    <a href="{{ route('blog.show', $post) }}" class="text-amber-600 font-semibold hover:text-amber-700">Read more →</a>
                </div>
            </article>
            @empty
            <p class="col-span-3 text-center text-slate-500 dark:text-slate-400 py-20">No posts published yet.</p>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    </div>
</div>
</x-layouts.believoo>
