<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $post->title }} — BMyDesk Blog</title>
    <meta name="description" content="{{ $post->excerpt }}">
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <a href="/blog" class="text-sm text-slate-500 hover:text-brand"><i class="fas fa-arrow-left mr-1"></i>Blog</a>
    </div>
</nav>
<div class="max-w-3xl mx-auto px-4 py-12">
    <p class="text-xs text-slate-400 mb-2">{{ $post->published_at ? \Carbon\Carbon::parse($post->published_at)->format('M d, Y') : '' }}</p>
    <h1 class="text-3xl font-extrabold text-slate-900 mb-8">{{ $post->title }}</h1>
    @if($post->cover)<img src="{{ $post->cover }}" class="w-full rounded-2xl mb-8" alt="{{ $post->title }}">@endif
    <div class="prose max-w-none text-slate-700 leading-relaxed bg-white rounded-2xl border border-slate-100 p-8">
        {!! $post->body !!}
    </div>
    <div class="mt-10 bg-slate-900 rounded-2xl p-6 text-white text-center">
        <p class="font-bold text-lg mb-3">Try BMyDesk free</p>
        <a href="/register" class="inline-block px-6 py-2.5 bg-brand rounded-xl font-bold text-sm">Create workspace</a>
    </div>
</div>
</body>
</html>
