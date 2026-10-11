<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $a->title }} — BMyDesk Help</title>
    <meta name="description" content="{{ Str::limit(strip_tags($a->body), 150) }}">
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <a href="/help" class="text-sm text-slate-500 hover:text-brand"><i class="fas fa-arrow-left mr-1"></i>Help Center</a>
    </div>
</nav>
<div class="max-w-3xl mx-auto px-4 py-12">
    <p class="text-xs font-bold uppercase tracking-wider text-brand mb-2">{{ $a->category }}</p>
    <h1 class="text-3xl font-extrabold text-slate-900 mb-8">{{ $a->title }}</h1>
    <div class="prose max-w-none text-slate-700 leading-relaxed bg-white rounded-2xl border border-slate-100 p-8">
        {!! $a->body !!}
    </div>
    <div class="mt-10 bg-slate-900 rounded-2xl p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="font-semibold">Still stuck?</p>
        <a href="/support/portal" class="px-5 py-2.5 bg-cyan-500 hover:bg-cyan-400 rounded-xl font-bold text-sm transition">Open a support ticket</a>
    </div>
</div>
</body>
</html>
