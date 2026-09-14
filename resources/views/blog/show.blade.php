<x-layouts.believoo
    title="{{ $post->meta_title ?: $post->title . ' | Believoo Blog' }}"
    description="{{ $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?? $post->content), 160) }}"
    keywords="{{ $post->meta_keywords ?: $post->title . ', Believoo, blog' }}">

<div class="pt-24 pb-20 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-900 min-h-screen">
    <div class="max-w-4xl mx-auto">
        <article class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200 dark:border-slate-700 p-8 md:p-12">
            <header class="mb-8">
                <span class="text-amber-600 text-sm font-bold uppercase tracking-wider">{{ $post->published_at?->format('M d, Y') }}</span>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mt-2 mb-4">{{ $post->title }}</h1>
                @if($post->excerpt)
                <p class="text-lg text-slate-600 dark:text-slate-300">{{ $post->excerpt }}</p>
                @endif
            </header>

            @if($post->featuredImageUrl())
            <img src="{{ $post->featuredImageUrl() }}" alt="{{ $post->title }}" class="w-full rounded-2xl mb-8" loading="lazy">
            @endif

            <div class="prose prose-slate dark:prose-invert max-w-none">
                {!! $post->content !!}
            </div>
        </article>

        @if($related->isNotEmpty())
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">Related Articles</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($related as $item)
                <a href="{{ route('blog.show', $item) }}" class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-6 hover:shadow-lg transition-all">
                    <span class="text-amber-600 text-xs font-bold uppercase tracking-wider">{{ $item->published_at?->format('M d, Y') }}</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ $item->title }}</h3>
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
</x-layouts.believoo>
