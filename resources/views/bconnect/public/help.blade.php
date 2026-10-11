<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Help Center — BMyDesk</title>
    <meta name="description" content="BMyDesk help center — guides, troubleshooting and support.">
    <link rel="icon" href="/img/bmydesk-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script>tailwind.config={theme:{extend:{colors:{brand:'#e11d48'}}}}</script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<nav class="bg-white border-b border-slate-100 sticky top-0 z-10">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <a href="/" class="text-xl font-extrabold text-slate-900">BMy<span class="text-brand">Desk</span></a>
        <div class="flex gap-5 text-sm">
            <a href="/help" class="text-brand font-semibold">Help</a>
            <a href="/blog" class="text-slate-500 hover:text-brand">Blog</a>
            <a href="/support/portal" class="text-slate-500 hover:text-brand">Support</a>
        </div>
    </div>
</nav>

<div class="max-w-5xl mx-auto px-4 py-14">
    <h1 class="text-4xl font-extrabold text-slate-900 mb-2">Help Center</h1>
    <p class="text-slate-600 mb-10">Guides for remote access, workspace, meetings and billing.</p>

    <div class="grid sm:grid-cols-3 gap-4 mb-14">
        <a href="/download/guide" class="bg-white rounded-2xl border border-slate-100 p-6 hover:shadow-md transition">
            <i class="fas fa-download text-brand text-xl mb-3"></i>
            <h3 class="font-bold text-slate-900 mb-1">Install the agent</h3>
            <p class="text-sm text-slate-500">Windows &amp; Android setup, step by step.</p>
        </a>
        <a href="/remote/guest" class="bg-white rounded-2xl border border-slate-100 p-6 hover:shadow-md transition">
            <i class="fas fa-desktop text-brand text-xl mb-3"></i>
            <h3 class="font-bold text-slate-900 mb-1">Connect to a device</h3>
            <p class="text-sm text-slate-500">Enter a code, get approved, control remotely.</p>
        </a>
        <a href="/support/portal" class="bg-white rounded-2xl border border-slate-100 p-6 hover:shadow-md transition">
            <i class="fas fa-headset text-brand text-xl mb-3"></i>
            <h3 class="font-bold text-slate-900 mb-1">Contact support</h3>
            <p class="text-sm text-slate-500">Open a ticket — no account needed.</p>
        </a>
    </div>

    @forelse($articles as $category => $items)
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 mt-10">{{ $category }}</h2>
        <div class="bg-white rounded-2xl border border-slate-100 divide-y divide-slate-50">
            @foreach($items as $a)
                <a href="{{ route('bconnect.kb.article', $a->slug) }}" class="flex items-center justify-between px-6 py-4 hover:bg-slate-50 transition">
                    <span class="font-semibold text-slate-800">{{ $a->title }}</span>
                    <span class="text-slate-300"><i class="fas fa-chevron-right text-sm"></i></span>
                </a>
            @endforeach
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-10 text-center text-slate-500">
            Articles coming soon. Meanwhile, <a href="/support/portal" class="text-brand font-semibold">open a ticket</a>.
        </div>
    @endforelse
</div>
</body>
</html>
