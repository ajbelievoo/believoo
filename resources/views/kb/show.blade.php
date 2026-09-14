@php($title = $article->title)
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

    <div class="max-w-4xl mx-auto relative z-10">
        <a href="{{ route('kb.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-electric-blue hover:underline mb-6">
            <i class="fas fa-arrow-left text-xs"></i> Back to Help Center
        </a>

        <div class="glass rounded-3xl border border-white/5 p-8 md:p-12">
            @if($article->category)
                <div class="text-[10px] font-black text-electric-blue uppercase tracking-[0.3em] mb-3">{{ $article->category }}</div>
            @endif
            <h1 class="text-3xl md:text-4xl font-black text-white uppercase tracking-tight mb-6">{{ $article->title }}</h1>

            <div class="prose prose-invert prose-electric-blue max-w-none">
                {!! $article->content !!}
            </div>

            @if($article->keywords)
                <div class="mt-10 pt-8 border-t border-white/5 flex flex-wrap gap-2">
                    @foreach(explode(',', $article->keywords) as $keyword)
                        <span class="px-3 py-1.5 rounded-full bg-white/5 text-gray-400 text-[10px] font-black uppercase tracking-widest border border-white/10">{{ trim($keyword) }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if($related->count())
            <div class="mt-12">
                <h2 class="text-2xl font-black text-white uppercase tracking-tight mb-6">Related Articles</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($related as $rel)
                        <a href="{{ route('kb.show', $rel) }}" class="glass rounded-2xl border border-white/5 p-5 hover:border-electric-blue/30 transition-all">
                            <h3 class="text-sm font-black text-white hover:text-electric-blue transition-colors">{{ $rel->title }}</h3>
                            @if($rel->category)
                                <div class="text-[10px] text-electric-blue uppercase tracking-widest mt-2">{{ $rel->category }}</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
