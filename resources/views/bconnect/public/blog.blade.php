<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog — BMyDesk</title>
    <meta name="description" content="Remote work, IT support and product updates from the BMyDesk team.">
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100 sticky top-0 z-10">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <div class="flex gap-5 text-sm">
            <a href="/help" class="text-slate-500 hover:text-brand">Help</a>
            <a href="/blog" class="text-brand font-semibold">Blog</a>
            <a href="/support/portal" class="text-slate-500 hover:text-brand">Support</a>
        </div>
    </div>
</nav>
<div class="max-w-6xl mx-auto px-4 py-14">
    <h1 class="text-4xl font-extrabold text-slate-900 mb-2">Blog</h1>
    <p class="text-slate-600 mb-12">Remote work, IT support and product updates.</p>
    <div class="grid md:grid-cols-3 gap-6">
        @forelse($posts as $p)
            <a href="{{ route('bconnect.blog.post', $p->slug) }}" class="bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-md transition group">
                @if($p->cover)
                    <img src="{{ $p->cover }}" class="w-full h-44 object-cover" alt="{{ $p->title }}">
                @else
                    <div class="w-full h-44 bg-gradient-to-br from-slate-800 to-slate-900 flex items-center justify-center text-slate-600 text-4xl"><i class="fas fa-pen"></i></div>
                @endif
                <div class="p-6">
                    <p class="text-xs text-slate-400 mb-2">{{ $p->published_at ? \Carbon\Carbon::parse($p->published_at)->format('M d, Y') : '' }}</p>
                    <h2 class="font-bold text-lg text-slate-900 group-hover:text-brand transition mb-2">{{ $p->title }}</h2>
                    <p class="text-sm text-slate-500">{{ $p->excerpt }}</p>
                </div>
            </a>
        @empty
            <div class="col-span-3 bg-white rounded-2xl border border-slate-100 p-10 text-center text-slate-500">Posts coming soon.</div>
        @endforelse
    </div>
    <div class="mt-10">{{ $posts->links() }}</div>
</div>
</body>
</html>
