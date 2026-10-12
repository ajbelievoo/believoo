<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $bconnectBrand['title'] }}</title>
    <meta name="description" content="{{ $bconnectBrand['description'] }}">
    <meta name="keywords" content="{{ $bconnectBrand['keywords'] }}">
    <meta name="author" content="Believoo">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://bmydesk.believoo.com">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $bconnectBrand['favicon'] }}">
    <link rel="apple-touch-icon" href="{{ $bconnectBrand['logo'] }}">
    <meta name="theme-color" content="{{ $bconnectBrand['brand_color'] }}">
    <meta property="og:title" content="{{ $bconnectBrand['title'] }}">
    <meta property="og:description" content="{{ $bconnectBrand['description'] }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://bmydesk.believoo.com">
    <meta property="og:site_name" content="{{ $bconnectBrand['name'] }} by Believoo">
    <meta property="og:image" content="{{ $bconnectBrand['og_image'] }}">
    <meta property="og:locale" content="en_IN">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $bconnectBrand['title'] }}">
    <meta name="twitter:description" content="{{ $bconnectBrand['description'] }}">
    <meta name="twitter:image" content="{{ $bconnectBrand['og_image'] }}">
    @if($bconnectBrand['google_site_verification'])
    <meta name="google-site-verification" content="{{ $bconnectBrand['google_site_verification'] }}">
    @endif
    @if($bconnectBrand['google_analytics'])
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $bconnectBrand['google_analytics'] }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);} gtag('js',new Date()); gtag('config','{{ $bconnectBrand['google_analytics'] }}');</script>
    @endif
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"SoftwareApplication","name":"{{ $bconnectBrand['name'] }}","applicationCategory":"BusinessApplication","offers":[{"@type":"Offer","name":"Free","price":"0","priceCurrency":"INR"},{"@type":"Offer","name":"Pro","price":"1999","priceCurrency":"INR"},{"@type":"Offer","name":"Enterprise","price":"9999","priceCurrency":"INR"}],"operatingSystem":"Web","provider":{"@type":"Organization","name":"Believoo"}}
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: '{{ $bconnectBrand['brand_color'] }}', brandDark: '{{ $bconnectBrand['brand_color_dark'] }}', dark: '#0a0a1a' } }, fontFamily: { sans: ['Figtree','Inter','sans-serif'] } } };
    </script>
    <style>
        html,body{font-family:Figtree,Inter,sans-serif;scroll-behavior:smooth;}
        .btn-gradient{background:linear-gradient(135deg,#f59e0b 0%,#e11d48 55%,#7c3aed 100%);}
        .btn-gradient:hover{background:linear-gradient(135deg,#d97706 0%,#be123c 55%,#6d28d9 100%);}
        .text-gradient{background:linear-gradient(90deg,#f59e0b,#e11d48,#8b5cf6);-webkit-background-clip:text;background-clip:text;color:transparent;}
        .icon-gradient{background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(225,29,72,0.12),rgba(139,92,246,0.12));}
    </style>
</head>
<body class="bg-white text-slate-900 antialiased">

<!-- Navbar -->
<nav class="fixed w-full z-50 bg-white/90 backdrop-blur border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <a href="https://believoo.com" class="flex items-center gap-2">
                <img src="{{ $bconnectBrand['logo'] }}" class="h-10 w-auto" alt="{{ $bconnectBrand['name'] }}">
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#features" class="hover:text-brand transition">Features</a>
                <a href="#pricing" class="hover:text-brand transition">Pricing</a>
                <a href="#remote" class="hover:text-brand transition">Remote</a>
                <a href="https://believoo.com/help" target="_blank" class="hover:text-brand transition">Support</a>
                <a href="https://believoo.com" class="hover:text-brand transition">Believoo</a>
            </div>
            <div class="flex items-center gap-3">
                <span id="statusPill" class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-[11px] font-semibold text-slate-500">
                    <span class="w-1.5 h-1.5 rounded-full bg-slate-300" id="statusDot"></span><span id="statusTxt">…</span>
                </span>
                <button id="langBtn" class="px-2.5 py-1.5 text-xs font-bold text-slate-600 border border-slate-200 rounded-lg hover:border-brand transition" title="Switch language">EN</button>
                <a href="{{ route('bconnect.remote.guest') }}" class="text-sm font-bold text-brand hover:underline" data-i18n="nav_remote">Remote Access</a>
                @auth
                    <a href="{{ route('bconnect.dashboard') }}" class="px-5 py-2.5 text-sm font-bold text-white btn-gradient rounded-full transition shadow-lg shadow-rose-500/30"><i class="fas fa-th-large mr-1"></i>Dashboard</a>
                @else
                    <a href="{{ route('bconnect.login') }}" class="text-sm font-semibold text-slate-700 hover:text-brand">Log in</a>
                    <a href="{{ route('bconnect.register') }}" class="px-5 py-2.5 text-sm font-bold text-white btn-gradient rounded-full transition shadow-lg shadow-rose-500/30">Get Started Free</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="relative pt-32 pb-20 lg:pt-44 lg:pb-32 overflow-hidden">
    <div class="absolute inset-0 -z-10">
        <div class="absolute top-0 right-0 w-2/3 h-2/3 bg-gradient-to-bl from-brand/10 to-transparent rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-1/2 h-1/2 bg-gradient-to-tr from-cyan-300/10 to-transparent rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1.5 mb-6 text-xs font-bold tracking-wide text-brand bg-brand/10 rounded-full">#1 IT Team Workspace</span>
        <h1 class="text-5xl lg:text-7xl font-extrabold tracking-tight leading-tight mb-6 text-slate-900">
            One workspace for<br><span class="text-gradient">calls, code & clients</span>
        </h1>
        <p class="max-w-2xl mx-auto text-lg text-slate-600 mb-10">
            Bmydesk unifies video conferencing, remote desktop, bug tracking, AI summaries, client billing and real-time team chat — built for IT companies, developers and global clients.
        </p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            @auth
                <a href="{{ route('bconnect.dashboard') }}" class="px-8 py-4 text-lg font-bold text-white btn-gradient rounded-full transition shadow-xl shadow-rose-500/30"><i class="fas fa-th-large mr-2"></i>Open Dashboard</a>
                <a href="{{ route('bconnect.remote') }}" class="px-8 py-4 text-lg font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-full transition"><i class="fas fa-desktop mr-2"></i>Remote Desktop</a>
            @else
                <a href="{{ route('bconnect.register') }}" class="px-8 py-4 text-lg font-bold text-white btn-gradient rounded-full transition shadow-xl shadow-rose-500/30">Start Free Workspace</a>
                <a href="{{ route('bconnect.login') }}" class="px-8 py-4 text-lg font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-full transition">Login to Dashboard</a>
            @endauth
        </div>

        <!-- Remote Desktop Access — public quick connect, no login needed -->
        <div id="remote-access" class="mt-10 max-w-xl mx-auto">
            <div class="bg-slate-900 rounded-2xl p-6 sm:p-8 shadow-2xl border border-slate-700/60 text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400 text-lg"><i class="fas fa-desktop"></i></div>
                    <div>
                        <h3 class="font-extrabold text-white text-lg leading-tight">Remote Desktop Access</h3>
                        <p class="text-xs text-slate-400">Enter the code on the remote device — connect instantly, no account needed.</p>
                    </div>
                </div>
                <form action="{{ route('bconnect.remote.guest') }}" method="GET" onsubmit="event.preventDefault(); const c=this.code.value.trim().toUpperCase(); if(c) location.href='{{ route('bconnect.remote.guest') }}/'+c;" class="flex gap-3">
                    <input name="code" maxlength="8" placeholder="e.g. ABC123XY" autocomplete="off" spellcheck="false"
                        class="flex-1 min-w-0 text-center text-xl font-mono tracking-[0.3em] uppercase bg-slate-800 border border-slate-600 rounded-xl px-4 py-3 text-cyan-300 placeholder-slate-500 focus:outline-none focus:border-cyan-400">
                    <button type="submit" class="px-6 py-3 font-bold text-white btn-gradient rounded-xl shadow-lg shadow-rose-500/30 whitespace-nowrap">Connect <i class="fas fa-arrow-right ml-1"></i></button>
                </form>
                <div class="mt-5 pt-4 border-t border-slate-700/60">
                    @php
                        $agentWin = \App\Http\Controllers\Bconnect\AgentApiController::AGENT_LATEST_WINDOWS;
                        $agentApk = \App\Http\Controllers\Bconnect\AgentApiController::AGENT_LATEST_ANDROID;
                    @endphp
                    <p class="text-[11px] text-slate-500 mb-3"><i class="fas fa-download mr-1"></i>Hosting a session? Get the agent — shows your permanent code &amp; shares your screen:</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="/dl/agent.exe" download id="dlWin" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-slate-200 transition"><i class="fab fa-windows mr-1 text-cyan-400"></i>Windows <span class="text-[10px] text-slate-500 font-normal">v{{ $agentWin }}</span></a>
                        <a href="/dl/agent.apk" download id="dlApk" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-slate-200 transition"><i class="fab fa-android mr-1 text-green-400"></i>Android <span class="text-[10px] text-slate-500 font-normal">v{{ $agentApk }}</span></a>
                        <div id="apkQr" class="hidden items-center gap-2 px-2 py-1 rounded-lg bg-slate-800/60 border border-slate-700">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=https%3A%2F%2Fbmydesk.believoo.com%2Fdl%2Fagent.apk" width="72" height="72" alt="Scan to download Android agent" class="rounded">
                            <span class="text-[10px] text-slate-400 leading-tight max-w-[90px]">Scan with your phone to install the Android agent</span>
                        </div>
                        <a href="/dl/agent-portable.zip" download class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm font-semibold text-slate-400 transition"><i class="fas fa-file-archive mr-1"></i>Portable ZIP</a>
                    </div>
                    <p class="text-[10px] text-slate-500 mt-3 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span><i class="fas fa-shield-halved text-green-500 mr-1"></i>Signed release build</span>
                        <span><i class="fas fa-lock text-slate-400 mr-1"></i>End-to-end encrypted sessions</span>
                        <span><i class="fas fa-user-check text-slate-400 mr-1"></i>You approve every connection</span>
                        <a href="{{ route('bconnect.install.guide') }}" class="text-cyan-400 hover:underline font-semibold"><i class="fas fa-book-open mr-1"></i>Install guide &amp; verification</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-12 relative rounded-2xl shadow-2xl border border-slate-200 overflow-hidden bg-slate-900">
            <!-- Real product screenshots carousel -->
            <div id="shotTrack" class="flex transition-transform duration-500 ease-out">
                <img src="/images/screens/dashboard.png" alt="BMyDesk dashboard" class="w-full h-64 lg:h-96 object-cover object-top shrink-0">
                <img src="/images/screens/tickets.png" alt="Ticket workspace" class="w-full h-64 lg:h-96 object-cover object-top shrink-0">
                <img src="/images/screens/meetings.png" alt="Meetings" class="w-full h-64 lg:h-96 object-cover object-top shrink-0">
                <img src="/images/screens/remote.png" alt="Remote desktop" class="w-full h-64 lg:h-96 object-cover object-top shrink-0">
            </div>
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2" id="shotDots"></div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Actual product screenshots — not mockups.</p>
    </div>
</section>

<!-- Video walkthrough -->
@php $demoVideo = \App\Helpers\BconnectHelper::setting('bmydesk_demo_video_url'); @endphp
<section class="py-16 bg-white border-t border-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl lg:text-4xl font-extrabold text-slate-900 mb-8">See it in 2 minutes</h2>
        <div class="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-200 aspect-video bg-slate-900">
            @if($demoVideo)
                <iframe src="{{ $demoVideo }}" class="absolute inset-0 w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            @else
                <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 gap-3">
                    <span class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center"><i class="fas fa-play text-2xl text-white/70"></i></span>
                    <p class="text-sm">Walkthrough video coming soon — set <code class="bg-white/10 px-1.5 rounded">bmydesk_demo_video_url</code> in Settings to embed.</p>
                </div>
            @endif
        </div>
    </div>
</section>

<!-- Features -->
<section id="features" class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4">Everything your team needs</h2>
            <p class="text-slate-600 max-w-2xl mx-auto">Replace 5 different tools with one powerful platform.</p>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="p-8 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition">
                <div class="w-14 h-14 bg-brand/10 rounded-xl flex items-center justify-center text-brand text-2xl mb-5"><i class="fas fa-video"></i></div>
                <h3 class="text-xl font-bold mb-2">HD Video Calls</h3>
                <p class="text-slate-600">Agora-powered group calls with screen share, recording and noise suppression.</p>
            </div>
            <div class="p-8 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition">
                <div class="w-14 h-14 bg-pink-500/10 rounded-xl flex items-center justify-center text-pink-500 text-2xl mb-5"><i class="fas fa-desktop"></i></div>
                <h3 class="text-xl font-bold mb-2">Remote Desktop</h3>
                <p class="text-slate-600">Request screen share, accept/reject flows, session codes and audit logs.</p>
            </div>
            <div class="p-8 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition">
                <div class="w-14 h-14 bg-amber-500/10 rounded-xl flex items-center justify-center text-amber-500 text-2xl mb-5"><i class="fas fa-bug"></i></div>
                <h3 class="text-xl font-bold mb-2">Bug & Ticket Workspace</h3>
                <p class="text-slate-600">Create tickets, upload screenshots, AI priority tagging and status workflow.</p>
            </div>
            <div class="p-8 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition">
                <div class="w-14 h-14 bg-green-500/10 rounded-xl flex items-center justify-center text-green-500 text-2xl mb-5"><i class="fas fa-robot"></i></div>
                <h3 class="text-xl font-bold mb-2">AI Summaries</h3>
                <p class="text-slate-600">Meeting summaries, action items, AI bug diagnostics and smart tagging.</p>
            </div>
            <div class="p-8 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition">
                <div class="w-14 h-14 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-500 text-2xl mb-5"><i class="fas fa-file-invoice-dollar"></i></div>
                <h3 class="text-xl font-bold mb-2">Billing & Invoices</h3>
                <p class="text-slate-600">Hourly and project-based invoices with Razorpay and Cashfree payments.</p>
            </div>
            <div class="p-8 bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-xl transition">
                <div class="w-14 h-14 bg-cyan-500/10 rounded-xl flex items-center justify-center text-cyan-500 text-2xl mb-5"><i class="fas fa-chalkboard"></i></div>
                <h3 class="text-xl font-bold mb-2">Whiteboard & Chat</h3>
                <p class="text-slate-600">Real-time project chat with Reverb and per-project whiteboard for annotations.</p>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section id="pricing" class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4">Simple, transparent pricing</h2>
            <p class="text-slate-600">Start free, upgrade when you grow.</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            <div class="p-8 rounded-2xl border border-slate-200 bg-white">
                <h3 class="text-xl font-bold">Free</h3>
                <div class="text-4xl font-extrabold my-4">₹0</div>
                <ul class="space-y-3 text-slate-600 text-sm mb-8">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>2 team members</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>1-on-1 calls</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Basic screen share</li>
                </ul>
                <a href="{{ route('bconnect.register') }}" class="block text-center py-3 rounded-lg border border-slate-300 font-bold hover:bg-slate-50">Start Free</a>
            </div>
            <div class="p-8 rounded-2xl border-2 border-brand bg-slate-900 text-white relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 btn-gradient text-white text-xs font-bold rounded-full">POPULAR</div>
                <h3 class="text-xl font-bold">Pro / Developer</h3>
                <div class="text-4xl font-extrabold my-4">₹1,999<span class="text-base font-normal text-slate-400">/mo</span></div>
                <ul class="space-y-3 text-slate-300 text-sm mb-8">
                    <li><i class="fas fa-check text-brand mr-2"></i>10 team members</li>
                    <li><i class="fas fa-check text-brand mr-2"></i>Unlimited calls</li>
                    <li><i class="fas fa-check text-brand mr-2"></i>Remote control</li>
                    <li><i class="fas fa-check text-brand mr-2"></i>Basic bug tracking</li>
                </ul>
                <a href="{{ route('bconnect.register') }}" class="block text-center py-3 rounded-lg btn-gradient text-white font-bold">Get Pro</a>
            </div>
            <div class="p-8 rounded-2xl border border-slate-200 bg-white">
                <h3 class="text-xl font-bold">Enterprise</h3>
                <div class="text-4xl font-extrabold my-4">₹9,999<span class="text-base font-normal text-slate-500">/mo</span></div>
                <ul class="space-y-3 text-slate-600 text-sm mb-8">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Unlimited members</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>AI summaries</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Custom branding</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Dedicated support</li>
                </ul>
                <a href="{{ route('bconnect.register') }}" class="block text-center py-3 rounded-lg border border-slate-300 font-bold hover:bg-slate-50">Contact Sales</a>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<!-- Comparison -->
<section class="py-20 bg-slate-50">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-14">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4" data-i18n="cmp_title">Why teams switch to BMyDesk</h2>
            <p class="text-slate-600" data-i18n="cmp_sub">One subscription replaces your remote-access tool, helpdesk and meeting app.</p>
        </div>
        <div class="overflow-x-auto bg-white rounded-2xl border border-slate-100 shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="p-4 font-semibold text-slate-500"></th>
                        <th class="p-4 font-extrabold text-brand text-base">BMyDesk</th>
                        <th class="p-4 font-semibold text-slate-500">AnyDesk</th>
                        <th class="p-4 font-semibold text-slate-500">TeamViewer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-slate-700">
                    <tr><td class="p-4 font-semibold">Remote desktop + control</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4"><i class="fas fa-check text-green-500"></i></td></tr>
                    <tr><td class="p-4 font-semibold">Android + Windows agents</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4"><i class="fas fa-check text-green-500"></i></td></tr>
                    <tr><td class="p-4 font-semibold">Unattended PIN access</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4"><i class="fas fa-check text-green-500"></i></td></tr>
                    <tr><td class="p-4 font-semibold">Built-in ticketing / helpdesk</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4 text-slate-300"><i class="fas fa-minus"></i></td><td class="p-4 text-slate-300"><i class="fas fa-minus"></i></td></tr>
                    <tr><td class="p-4 font-semibold">Video meetings + AI notes</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4 text-slate-300"><i class="fas fa-minus"></i></td><td class="p-4 text-slate-300"><i class="fas fa-minus"></i></td></tr>
                    <tr><td class="p-4 font-semibold">Client billing &amp; invoices</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4 text-slate-300"><i class="fas fa-minus"></i></td><td class="p-4 text-slate-300"><i class="fas fa-minus"></i></td></tr>
                    <tr><td class="p-4 font-semibold">White-label branding</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4 text-xs text-slate-500">Enterprise only</td><td class="p-4 text-xs text-slate-500">Enterprise only</td></tr>
                    <tr><td class="p-4 font-semibold">Free tier to start</td><td class="p-4"><i class="fas fa-check text-green-500"></i></td><td class="p-4 text-xs text-slate-500">Personal use only</td><td class="p-4 text-xs text-slate-500">Personal use only</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4">How Bmydesk works</h2>
            <p class="text-slate-600">Set up your workspace in minutes, not days.</p>
        </div>
        <div class="grid md:grid-cols-4 gap-8">
            <div class="text-center p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-14 h-14 mx-auto bg-brand/10 rounded-full flex items-center justify-center text-brand text-2xl font-black mb-4">1</div>
                <h3 class="font-bold text-lg mb-2">Create workspace</h3>
                <p class="text-slate-600 text-sm">Sign up with email or Google. Add your company in one click.</p>
            </div>
            <div class="text-center p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-14 h-14 mx-auto bg-brand/10 rounded-full flex items-center justify-center text-brand text-2xl font-black mb-4">2</div>
                <h3 class="font-bold text-lg mb-2">Add projects</h3>
                <p class="text-slate-600 text-sm">Create projects, invite team members and assign clients.</p>
            </div>
            <div class="text-center p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-14 h-14 mx-auto bg-brand/10 rounded-full flex items-center justify-center text-brand text-2xl font-black mb-4">3</div>
                <h3 class="font-bold text-lg mb-2">Meet & track</h3>
                <p class="text-slate-600 text-sm">HD video calls, tickets, whiteboard and chat — all in one place.</p>
            </div>
            <div class="text-center p-6 rounded-2xl border border-slate-100 hover:shadow-lg transition">
                <div class="w-14 h-14 mx-auto bg-brand/10 rounded-full flex items-center justify-center text-brand text-2xl font-black mb-4">4</div>
                <h3 class="font-bold text-lg mb-2">Invoice & grow</h3>
                <p class="text-slate-600 text-sm">Bill clients, get paid online, scale your team.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="py-16 bg-slate-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8 text-center">
            <div><div class="text-4xl lg:text-5xl font-extrabold text-brand">10k+</div><p class="text-slate-400 mt-2">Teams supported</p></div>
            <div><div class="text-4xl lg:text-5xl font-extrabold text-brand">99.9%</div><p class="text-slate-400 mt-2">Uptime</p></div>
            <div><div class="text-4xl lg:text-5xl font-extrabold text-brand">50+</div><p class="text-slate-400 mt-2">Countries</p></div>
            <div><div class="text-4xl lg:text-5xl font-extrabold text-brand">100%</div><p class="text-slate-400 mt-2">Reliable</p></div>
        </div>
    </div>
</section>

<!-- Integrations -->
<section class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4">Works with your tools</h2>
            <p class="text-slate-600">Integrations that make your workflow seamless.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fab fa-google text-3xl text-red-500 mb-3"></i><div class="font-bold">Google</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fab fa-slack text-3xl text-purple-500 mb-3"></i><div class="font-bold">Slack</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fab fa-whatsapp text-3xl text-green-500 mb-3"></i><div class="font-bold">WhatsApp</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fab fa-telegram text-3xl text-blue-500 mb-3"></i><div class="font-bold">Telegram</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fas fa-credit-card text-3xl text-cyan-500 mb-3"></i><div class="font-bold">Razorpay</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fas fa-money-bill text-3xl text-green-600 mb-3"></i><div class="font-bold">Cashfree</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fas fa-video text-3xl text-pink-500 mb-3"></i><div class="font-bold">Agora</div></div>
            <div class="p-6 bg-white rounded-2xl border border-slate-100 shadow-sm"><i class="fas fa-envelope text-3xl text-amber-500 mb-3"></i><div class="font-bold">SMTP Mail</div></div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4">Loved by IT teams</h2>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="p-8 bg-slate-50 rounded-2xl">
                <div class="text-amber-400 text-sm mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-slate-700 mb-4">"Bmydesk replaced Zoom, Jira and AnyDesk for our agency. Clients love the shared workspace."</p>
                <div class="font-bold">Rohit K.</div><div class="text-slate-500 text-sm">CTO, Delhi</div>
            </div>
            <div class="p-8 bg-slate-50 rounded-2xl">
                <div class="text-amber-400 text-sm mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-slate-700 mb-4">"AI meeting summaries save us 30 minutes after every client call. Best investment this year."</p>
                <div class="font-bold">Priya S.</div><div class="text-slate-500 text-sm">Project Manager, Mumbai</div>
            </div>
            <div class="p-8 bg-slate-50 rounded-2xl">
                <div class="text-amber-400 text-sm mb-4"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="text-slate-700 mb-4">"We bill clients directly from tickets. Payments sync perfectly with Razorpay."</p>
                <div class="font-bold">Aman T.</div><div class="text-slate-500 text-sm">Founder, Bangalore</div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-20 bg-slate-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl lg:text-5xl font-extrabold text-slate-900 mb-4">Frequently asked questions</h2>
        </div>
        <div class="space-y-4">
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Is Bmydesk free to start? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">Yes. Free plan includes 2 members and basic calls. Upgrade anytime.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Can clients join meetings without an account? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">Clients with an assigned role can join via Bmydesk. Guest links are coming soon.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Is remote desktop secure? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">All sessions are tokenized, logged and require explicit host permission. Screen sharing is encrypted via WebRTC.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Do you support custom domains? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">Yes. Enterprise plan includes custom domain mapping and white-label branding.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">How does remote access work without any app? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">The viewer needs nothing — just open the code page in a browser. The host runs our lightweight Windows or Android agent which shows a permanent code.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Can I access a device when nobody is there? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">Yes — set an unattended-access PIN on the agent. Entering the PIN on join skips the approval step entirely.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Where is my data hosted? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">All workspace data lives on Believoo infrastructure. Remote sessions are peer-to-peer WebRTC with TURN relay fallback — we never store screen content.</p>
            </details>
            <details class="group bg-white rounded-2xl border border-slate-100 p-6 cursor-pointer">
                <summary class="font-bold text-slate-900 flex justify-between items-center">Does it work behind strict firewalls? <i class="fas fa-chevron-down text-slate-400 group-open:rotate-180 transition"></i></summary>
                <p class="text-slate-600 mt-4 text-sm">Yes — sessions fall back to our TURN relay over TCP when direct peer-to-peer is blocked.</p>
            </details>
        </div>
    </div>
</section>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {"@type":"Question","name":"Is Bmydesk free to start?","acceptedAnswer":{"@type":"Answer","text":"Yes. Free plan includes 2 members and basic calls. Upgrade anytime."}},
        {"@type":"Question","name":"Can clients join meetings without an account?","acceptedAnswer":{"@type":"Answer","text":"Clients with an assigned role can join via Bmydesk. Guest links are coming soon."}},
        {"@type":"Question","name":"Is remote desktop secure?","acceptedAnswer":{"@type":"Answer","text":"All sessions are tokenized, logged and require explicit host permission. Screen sharing is encrypted via WebRTC."}},
        {"@type":"Question","name":"Do you support custom domains?","acceptedAnswer":{"@type":"Answer","text":"Yes. Enterprise plan includes custom domain mapping and white-label branding."}},
        {"@type":"Question","name":"How does remote access work without any app?","acceptedAnswer":{"@type":"Answer","text":"The viewer needs nothing — just open the code page in a browser. The host runs our lightweight Windows or Android agent which shows a permanent code."}},
        {"@type":"Question","name":"Can I access a device when nobody is there?","acceptedAnswer":{"@type":"Answer","text":"Yes — set an unattended-access PIN on the agent. Entering the PIN on join skips the approval step entirely."}},
        {"@type":"Question","name":"Where is my data hosted?","acceptedAnswer":{"@type":"Answer","text":"All workspace data lives on Believoo infrastructure. Remote sessions are peer-to-peer WebRTC with TURN relay fallback — we never store screen content."}},
        {"@type":"Question","name":"Does it work behind strict firewalls?","acceptedAnswer":{"@type":"Answer","text":"Yes — sessions fall back to our TURN relay over TCP when direct peer-to-peer is blocked."}}
    ]
}
</script>

<!-- CTA -->
<section class="py-20 bg-slate-900 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl lg:text-5xl font-extrabold mb-6">Ready to unify your IT workflow?</h2>
        <p class="text-slate-400 mb-10 text-lg">Join companies already using Bmydesk to ship faster and support clients better.</p>
        @auth
            <a href="{{ route('bconnect.dashboard') }}" class="inline-block px-10 py-4 text-lg font-bold text-white btn-gradient rounded-full transition shadow-xl shadow-rose-500/30">Open Dashboard</a>
        @else
            <a href="{{ route('bconnect.register') }}" class="inline-block px-10 py-4 text-lg font-bold text-white btn-gradient rounded-full transition shadow-xl shadow-rose-500/30">Create Free Workspace</a>
        @endauth
    </div>
</section>

<!-- Footer -->
<footer class="bg-white border-t border-slate-100 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <div class="flex items-center gap-2">
            <img src="{{ $bconnectBrand['logo'] }}" class="h-8 w-auto" alt="{{ $bconnectBrand['name'] }}">
        </div>
        <div class="text-sm text-slate-500">
            {!! $bconnectBrand['footer_text'] !!}
        </div>
        <div class="flex gap-6 text-slate-600 flex-wrap justify-center">
            <a href="/download/guide" class="hover:text-brand">Install Guide</a>
            <a href="/help" class="hover:text-brand">Help Center</a>
            <a href="/blog" class="hover:text-brand">Blog</a>
            <a href="https://believoo.com" class="hover:text-brand">Believoo.com</a>
            @auth
                <a href="{{ route('bconnect.dashboard') }}" class="hover:text-brand">Dashboard</a>
            @else
                <a href="{{ route('bconnect.login') }}" class="hover:text-brand">Login</a>
                <a href="{{ route('bconnect.register') }}" class="hover:text-brand">Register</a>
            @endauth
        </div>
    </div>
</footer>


<script>
(function () {
    var ua = navigator.userAgent || '';
    var isAndroid = /Android/i.test(ua), isWin = /Windows/i.test(ua);
    var el = isAndroid ? document.getElementById('dlApk') : (isWin ? document.getElementById('dlWin') : null);
    if (el) { el.classList.remove('bg-slate-800'); el.classList.add('bg-cyan-600', 'ring-2', 'ring-cyan-300'); }
    if (!isAndroid) { var q = document.getElementById('apkQr'); if (q) { q.classList.remove('hidden'); q.classList.add('flex'); } }
})();

// ── Screenshot carousel ──
(function () {
    const track = document.getElementById('shotTrack');
    if (!track) return;
    const n = track.children.length, dots = document.getElementById('shotDots');
    let i = 0;
    for (let k = 0; k < n; k++) {
        const d = document.createElement('button');
        d.className = 'w-2 h-2 rounded-full bg-white/30 transition';
        d.onclick = () => go(k);
        dots.appendChild(d);
    }
    function go(k) {
        i = k % n;
        track.style.transform = 'translateX(-' + (i * 100) + '%)';
        [...dots.children].forEach((d, j) => d.className = 'w-2 h-2 rounded-full transition ' + (j === i ? 'bg-white' : 'bg-white/30'));
    }
    go(0); setInterval(() => go(i + 1), 4000);
})();

// ── Live status pill ──
(function () {
    const dot = document.getElementById('statusDot'), txt = document.getElementById('statusTxt');
    if (!dot) return;
    fetch('/api/v1/bmydesk/agent/version', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            if (d.ok) { dot.className = 'w-1.5 h-1.5 rounded-full bg-green-500'; txt.textContent = 'All systems operational'; }
            else throw 0;
        })
        .catch(() => { dot.className = 'w-1.5 h-1.5 rounded-full bg-red-500'; txt.textContent = 'Degraded'; });
})();

// ── EN/HI toggle ──
(function () {
    const T = {
        hi: {
            nav_remote: 'रिमोट एक्सेस',
            hero_title_1: 'एक ही workspace में',
            hero_title_2: 'कॉल्स, कोड और क्लाइंट्स',
            hero_sub: 'BMyDesk में वीडियो कॉल, रिमोट डेस्कटॉप, बग ट्रैकिंग, AI समरी, क्लाइंट बिलिंग और रियल-टाइम चैट — IT कंपनियों, डेवलपर्स और ग्लोबल क्लाइंट्स के लिए बनाया गया।',
            cta_start: 'फ्री workspace शुरू करें',
            cta_login: 'डैशबोर्ड लॉगिन',
            cmp_title: 'टीमें BMyDesk पर क्यों आती हैं',
            cmp_sub: 'एक सब्सक्रिप्शन — रिमोट टूल, हेल्पडेस्क और मीटिंग ऐप तीनों की जगह।'
        }
    };
    const btn = document.getElementById('langBtn');
    if (!btn) return;
    const KEY = 'bmd_lang';
    const saved = localStorage.getItem(KEY) || 'en';
    function apply(l) {
        btn.textContent = l === 'hi' ? 'हिं' : 'EN';
        if (l === 'hi') {
            const h = T.hi;
            document.querySelectorAll('[data-i18n]').forEach(el => { if (h[el.dataset.i18n]) el.textContent = h[el.dataset.i18n]; });
            const h1 = document.querySelector('h1');
            if (h1 && !h1.dataset.orig) h1.dataset.orig = h1.innerHTML;
            if (h1) h1.innerHTML = T.hi.hero_title_1 + '<br><span class="text-gradient">' + T.hi.hero_title_2 + '</span>';
            const sub = document.querySelector('section p.max-w-2xl');
            if (sub && !sub.dataset.orig) sub.dataset.orig = sub.textContent;
            if (sub) sub.textContent = T.hi.hero_sub;
        } else {
            const h1 = document.querySelector('h1');
            if (h1 && h1.dataset.orig) h1.innerHTML = h1.dataset.orig;
            const sub = document.querySelector('section p.max-w-2xl');
            if (sub && sub.dataset.orig) sub.textContent = sub.dataset.orig;
            document.querySelectorAll('[data-i18n]').forEach(el => { if (el.dataset.origText) el.textContent = el.dataset.origText; });
        }
    }
    document.querySelectorAll('[data-i18n]').forEach(el => el.dataset.origText = el.textContent);
    btn.onclick = () => { const l = (localStorage.getItem(KEY) === 'hi') ? 'en' : 'hi'; localStorage.setItem(KEY, l); apply(l); };
    if (saved === 'hi') apply('hi');
})();
</script>
</body>
</html>
