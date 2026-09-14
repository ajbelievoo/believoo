@php($title = $article->title)
@extends('components.layouts.believoo')

@section('content')
<div class="min-h-screen bg-white text-slate-800 pt-28 pb-20 px-4">
    <div class="max-w-4xl mx-auto">
        <a href="{{ route('support.kb.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#00b7ff] hover:underline mb-6">
            <i class="fas fa-arrow-left text-xs"></i> Back to Help Center
        </a>

        <div class="rounded-2xl border border-slate-100 p-8 md:p-12 bg-white">
            @if($article->category)
                <div class="text-[10px] font-black text-[#00b7ff] uppercase tracking-[0.3em] mb-3">{{ $article->category }}</div>
            @endif
            <h1 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight mb-6">{{ $article->title }}</h1>

            <div class="prose prose-slate max-w-none">
                {!! $article->content !!}
            </div>

            @if($article->keywords)
                <div class="mt-10 pt-8 border-t border-slate-100 flex flex-wrap gap-2">
                    @foreach(explode(',', $article->keywords) as $keyword)
                        <span class="px-3 py-1.5 rounded-full bg-slate-50 text-slate-500 text-[10px] font-black uppercase tracking-widest border border-slate-200">{{ trim($keyword) }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        @if($related->count())
            <div class="mt-10">
                <h2 class="text-xl font-black text-slate-900 tracking-tight mb-4">Related Articles</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($related as $rel)
                        <a href="{{ route('support.kb.show', $rel) }}" class="p-5 rounded-2xl border border-slate-100 hover:border-[#00b7ff]/30 hover:shadow-lg hover:shadow-blue-50 transition-all">
                            <h3 class="font-bold text-slate-900 hover:text-[#00b7ff] transition-colors">{{ $rel->title }}</h3>
                            @if($rel->category)
                                <div class="text-[10px] text-[#00b7ff] uppercase tracking-widest mt-2">{{ $rel->category }}</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
