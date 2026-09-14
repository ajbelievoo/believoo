@php($title = 'Help Center')
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-dark pt-32 pb-20 px-6 relative overflow-hidden">
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
        <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(0,183,255,0.4) 0%, transparent 70%); filter: blur(80px);"></div>
        <div class="absolute bottom-20 -left-40 w-[400px] h-[400px] rounded-full opacity-10"
             style="background: radial-gradient(circle, rgba(112,0,255,0.4) 0%, transparent 70%); filter: blur(60px);"></div>
        <div class="absolute inset-0 opacity-20"
             style="background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 64px 64px;"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        <div class="text-center mb-12">
            <p class="text-[10px] font-black text-electric-blue uppercase tracking-[0.3em] mb-2">Help Center</p>
            <h1 class="text-5xl font-black text-white uppercase tracking-tighter">How can we <span class="text-electric-blue">help?</span></h1>
            <p class="text-gray-400 mt-3 text-sm max-w-xl mx-auto">Search articles, browse by category, or read our most popular guides.</p>
        </div>

        <div class="max-w-2xl mx-auto mb-12">
            <form action="{{ route('kb.index') }}" method="GET" class="relative">
                <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 text-sm"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search help articles..."
                       class="w-full bg-white/5 border border-white/10 rounded-2xl pl-12 pr-4 py-4 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-electric-blue/50 transition-all">
            </form>
        </div>

        @if($categories->count())
        <div class="flex flex-wrap justify-center gap-3 mb-12">
            <a href="{{ route('kb.index') }}" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all {{ !request('category') ? 'bg-electric-blue text-dark border-electric-blue' : 'bg-white/5 text-gray-400 border-white/10 hover:border-electric-blue/30' }}">All</a>
            @foreach($categories as $cat)
                <a href="{{ route('kb.index', ['category' => $cat]) }}" class="px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest border transition-all {{ request('category') === $cat ? 'bg-electric-blue text-dark border-electric-blue' : 'bg-white/5 text-gray-400 border-white/10 hover:border-electric-blue/30' }}">{{ $cat }}</a>
            @endforeach
        </div>
        @endif

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($articles as $article)
                <a href="{{ route('kb.show', $article) }}" class="glass rounded-3xl border border-white/5 p-6 hover:border-electric-blue/30 transition-all group">
                    <div class="w-10 h-10 rounded-2xl bg-electric-blue/10 flex items-center justify-center mb-4 group-hover:bg-electric-blue/20 transition-all">
                        <i class="fas fa-file-alt text-electric-blue text-sm"></i>
                    </div>
                    @if($article->category)
                        <div class="text-[10px] font-black text-electric-blue uppercase tracking-widest mb-2">{{ $article->category }}</div>
                    @endif
                    <h3 class="text-lg font-black text-white mb-2 group-hover:text-electric-blue transition-colors">{{ $article->title }}</h3>
                    <p class="text-sm text-gray-400 line-clamp-2">{{ Str::limit(strip_tags($article->content), 120) }}</p>
                </a>
            @endforeach
        </div>

        @if($articles->count() === 0)
            <div class="text-center py-24">
                <div class="w-20 h-20 rounded-full bg-electric-blue/10 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-search text-3xl text-electric-blue"></i>
                </div>
                <h3 class="text-2xl font-black text-white uppercase tracking-tight mb-2">No articles found</h3>
                <p class="text-gray-400 text-sm max-w-sm mx-auto">Try a different search or contact support.</p>
            </div>
        @endif

        <div class="mt-10">
            {{ $articles->links() }}
        </div>
    </div>
</div>
@endsection
