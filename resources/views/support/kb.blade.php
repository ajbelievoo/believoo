@php($title = 'Help Center')
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-white text-slate-800 pt-32 pb-20 px-4">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-black text-slate-900 tracking-tight mb-2">Help Center</h1>
            <p class="text-slate-500 text-sm">Search articles or browse by category.</p>
        </div>

        <form action="{{ route('support.kb.index') }}" method="GET" class="max-w-2xl mx-auto mb-10 relative">
            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search articles..."
                   class="w-full pl-12 pr-4 py-4 rounded-full border border-slate-200 shadow-lg shadow-slate-100 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#00b7ff] focus:ring-2 focus:ring-[#00b7ff]/20 transition-all">
        </form>

        @if($categories->count())
            <div class="flex flex-wrap justify-center gap-2 mb-10">
                <a href="{{ route('support.kb.index') }}" class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest border {{ !request('category') ? 'bg-[#00b7ff] text-white border-[#00b7ff]' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-[#00b7ff]' }}">All</a>
                @foreach($categories as $cat)
                    <a href="{{ route('support.kb.index', ['category' => $cat]) }}" class="px-4 py-2 rounded-full text-xs font-black uppercase tracking-widest border {{ request('category') === $cat ? 'bg-[#00b7ff] text-white border-[#00b7ff]' : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-[#00b7ff]' }}">{{ $cat }}</a>
                @endforeach
            </div>
        @endif

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($articles as $article)
                <a href="{{ route('support.kb.show', $article) }}" class="p-6 rounded-2xl border border-slate-100 hover:border-[#00b7ff]/30 hover:shadow-lg hover:shadow-blue-50 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 group-hover:bg-[#00b7ff]/10 flex items-center justify-center mb-4">
                        <i class="fas fa-file-alt text-[#00b7ff] text-sm"></i>
                    </div>
                    @if($article->category)
                        <div class="text-[10px] font-black text-[#00b7ff] uppercase tracking-widest mb-2">{{ $article->category }}</div>
                    @endif
                    <h3 class="font-bold text-slate-900 mb-2 group-hover:text-[#00b7ff] transition-colors">{{ $article->title }}</h3>
                    <p class="text-sm text-slate-500 line-clamp-2">{{ Str::limit(strip_tags($article->content), 120) }}</p>
                </a>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $articles->links() }}
        </div>
    </div>
</div>
@endsection
