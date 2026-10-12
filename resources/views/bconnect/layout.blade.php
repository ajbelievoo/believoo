<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
// Theme init — before first paint, no flash
(function(){
    var t = localStorage.getItem('bconnect_theme');
    if (!t) t = window.matchMedia && matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    document.documentElement.classList.remove('dark','light');
    document.documentElement.classList.add(t);
})();
function bcToggleTheme(){
    var el = document.documentElement;
    var t = el.classList.contains('light') ? 'dark' : 'light';
    el.classList.remove('dark','light'); el.classList.add(t);
    localStorage.setItem('bconnect_theme', t);
    var b = document.getElementById('bcThemeBtn');
    if (b) b.innerHTML = '<i class="fas ' + (t === 'light' ? 'fa-moon' : 'fa-sun') + '"></i>';
}
</script>
<title>@yield('title', $bconnectBrand['title'])</title>
<meta name="description" content="{{ $bconnectBrand['description'] }}">
<meta name="keywords" content="{{ $bconnectBrand['keywords'] }}">
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ $bconnectBrand['favicon'] }}">
<meta name="theme-color" content="{{ $bconnectBrand['brand_color'] }}">
<meta property="og:title" content="{{ $bconnectBrand['title'] }}">
<meta property="og:description" content="{{ $bconnectBrand['description'] }}">
<meta property="og:type" content="website">
<meta property="og:url" content="https://bmydesk.believoo.com">
<meta property="og:site_name" content="{{ $bconnectBrand['name'] }} by Believoo">
<meta property="og:image" content="{{ $bconnectBrand['og_image'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $bconnectBrand['title'] }}">
<meta name="twitter:description" content="{{ $bconnectBrand['description'] }}">
<meta name="twitter:image" content="{{ $bconnectBrand['og_image'] }}">
<link rel="canonical" href="https://bmydesk.believoo.com{{ request()->getPathInfo() }}">
@if($bconnectBrand['google_site_verification'])
<meta name="google-site-verification" content="{{ $bconnectBrand['google_site_verification'] }}">
@endif
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/css/bconnect.css?v={{ filemtime(public_path('css/bconnect.css')) }}">
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:'{{ $bconnectBrand['brand_color'] }}'}}};</script>
<style>:root{--bc-cyan:{{ $bconnectBrand['brand_color_light'] }};--bc-cyan-dark:{{ $bconnectBrand['brand_color'] }};}.bc-badge-cyan{background:rgba({{ $bconnectBrand['brand_rgb_light'] }},0.12);}</style>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="vapid-key" content="{{ \App\Models\Setting::where('key', 'vapid_public_key')->value('value') ?? '' }}">
<link rel="manifest" href="/manifest.json">
@vite(['resources/js/app.js'])
<script src="/push-init.js"></script>
<style>body{background:var(--bc-bg);color:var(--bc-text);}</style>
</head>
<body class="min-h-screen flex">
<div id="mobileOverlay" class="bc-overlay" onclick="toggleSidebar()"></div>
<aside id="sidebar" class="bc-sidebar w-64 border-r border-[var(--bc-border)] flex flex-col h-screen z-50">
    <div class="p-4 border-b border-[var(--bc-border)]">
        <img src="{{ $bconnectBrand['logo'] }}" class="w-auto max-h-11 max-w-full object-contain object-left" alt="{{ $bconnectBrand['name'] }}">
        <p class="text-[11px] text-slate-500 mt-1.5 truncate">{{ $bconnectCompany->name ?? 'Workspace' }}</p>
    </div>
    <div class="px-4 py-3">
        @php
            $planStatus = \App\Services\BconnectSubscriptionService::status($bconnectCompany ?? new \App\Models\Bconnect\Company(['plan' => 'free']));
            $days = \App\Services\BconnectSubscriptionService::daysUntilExpiry($bconnectCompany ?? null);
        @endphp
        <div class="bc-plan-ring {{ $planStatus }} w-full justify-between">
            <span class="truncate">{{ ucfirst($bconnectCompany?->plan ?? 'free') }}</span>
            <span class="text-[10px] text-slate-400">{{ $days !== null ? $days . 'd' : '—' }}</span>
        </div>
    </div>
    <nav class="flex-1 px-4 py-2 overflow-y-auto">
        <a href="{{ route('bconnect.dashboard') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.dashboard') ? 'active' : '' }}"><i class="fas fa-home"></i>Dashboard</a>
        <a href="{{ route('bconnect.projects.index') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.projects.*') ? 'active' : '' }}"><i class="fas fa-folder"></i>Projects</a>
        <a href="{{ route('bconnect.meetings') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.meeting*') ? 'active' : '' }}"><i class="fas fa-video"></i>Meetings</a>
        <a href="{{ route('bconnect.chat') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.chat*') ? 'active' : '' }}"><i class="fas fa-hashtag"></i>Channels</a>
        <a href="{{ route('bconnect.kanban') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.kanban*') || request()->routeIs('bconnect.projects.kanban') ? 'active' : '' }}"><i class="fas fa-columns"></i>Kanban</a>
        <a href="{{ route('bconnect.tickets') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.tickets*') ? 'active' : '' }}"><i class="fas fa-bug"></i>Tickets</a>
        <a href="{{ route('bconnect.sprints.index') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.sprints*') || request()->routeIs('bconnect.projects.sprints') ? 'active' : '' }}"><i class="fas fa-running"></i>Sprints</a>
        <a href="{{ route('bconnect.time_tracking') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.time_tracking*') || request()->routeIs('bconnect.projects.time_tracking') ? 'active' : '' }}"><i class="fas fa-clock"></i>Time</a>
        @if(in_array($bconnectCompany?->plan ?? 'free', ['pro','enterprise']))
        <a href="{{ route('bconnect.remote') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.remote*') && !request()->routeIs('bconnect.remote.agent') ? 'active' : '' }}"><i class="fas fa-desktop"></i>Remote</a>
        @endif
        <a href="{{ route('bconnect.billing') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.billing*') ? 'active' : '' }}"><i class="fas fa-file-invoice"></i>Billing</a>
        <a href="{{ route('bconnect.members') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.members*') ? 'active' : '' }}"><i class="fas fa-users"></i>Members</a>
        <a href="{{ route('bconnect.company') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.company*') ? 'active' : '' }}"><i class="fas fa-building"></i>Company</a>
        <a href="{{ route('bconnect.files') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.files*') ? 'active' : '' }}"><i class="fas fa-folder-open"></i>Files</a>
        <a href="{{ route('bconnect.audit') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.audit*') ? 'active' : '' }}"><i class="fas fa-shield-alt"></i>Audit Logs</a>
        <a href="{{ route('bconnect.support') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.support*') ? 'active' : '' }}"><i class="fas fa-question-circle"></i>Support</a>
        @if(($bconnectRole ?? '') === 'company_admin')
        <div class="mt-4 pt-3 border-t border-[var(--bc-border)]">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">Admin</p>
            <a href="{{ route('bconnect.admin.fleet') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.admin.fleet') ? 'active' : '' }}"><i class="fas fa-server"></i>Device Fleet</a>
            <a href="{{ route('bconnect.admin.monitor') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.admin.monitor') ? 'active' : '' }}"><i class="fas fa-satellite-dish"></i>Live Monitor</a>
            <a href="{{ route('bconnect.admin.policies') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.admin.policies') ? 'active' : '' }}"><i class="fas fa-list-check"></i>Remote Policies</a>
            <a href="{{ route('bconnect.admin.health') }}" class="bc-sidebar-link {{ request()->routeIs('bconnect.admin.health') ? 'active' : '' }}"><i class="fas fa-heart-pulse"></i>Server Health</a>
        </div>
        @endif
    </nav>
    <div class="p-4 border-t border-[var(--bc-border)]">
        <div class="flex items-center gap-3 mb-3">
            <a href="{{ route('bconnect.profile') }}" class="w-9 h-9 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-sm overflow-hidden shrink-0" title="Profile">
                @php $_av = Auth::user()->avatar ?: Auth::user()->profile_photo_path; @endphp
                @if($_av)<img src="{{ $_av }}" class="w-full h-full object-cover">@else{{ substr(Auth::user()->name,0,1) }}@endif
            </a>
            <div class="text-sm">
                <a href="{{ route('bconnect.profile') }}" class="font-medium truncate max-w-[120px] block hover:text-cyan-400">{{ Auth::user()->name }}</a>
                <div class="text-slate-500 text-xs">{{ ucfirst(str_replace('_', ' ', $bconnectRole ?? '')) }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('bconnect.logout') }}">@csrf
            <button type="submit" class="bc-btn bc-btn-danger w-full text-sm"><i class="fas fa-sign-out-alt"></i>Logout</button>
        </form>
    </div>
</aside>
<main class="flex-1 flex flex-col overflow-hidden min-w-0">
    <header class="h-16 bg-[#0f172a]/90 backdrop-blur border-b border-[var(--bc-border)] flex items-center justify-between px-4 lg:px-8 sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <button class="bc-mobile-menu-btn bc-btn bc-btn-secondary p-2 text-sm" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2 class="font-bold text-lg truncate">@yield('title')</h2>
        </div>
        <div class="flex items-center gap-4">
            <button onclick="bcPaletteOpen()" class="hidden md:flex items-center gap-2 text-xs text-slate-400 hover:text-white border border-[var(--bc-border)] rounded-lg px-3 py-1.5" title="Search (Ctrl+K)">
                <i class="fas fa-search"></i><span>Search</span><kbd class="text-[10px] bg-white/10 rounded px-1">⌘K</kbd>
            </button>
            <a href="{{ route('bconnect.billing') }}" class="hidden sm:flex items-center gap-2 text-xs text-slate-400 hover:text-white bc-badge bc-badge-slate">
                <i class="fas fa-crown text-cyan-400"></i>{{ ucfirst($bconnectCompany?->plan ?? 'free') }}
            </a>
            <button id="bcThemeBtn" onclick="bcToggleTheme()" class="bc-theme-btn" title="Toggle light / dark">
                <i class="fas fa-sun"></i>
            </button>
            <a href="{{ route('bconnect.notifications') }}" class="relative text-slate-400 hover:text-white text-lg">
                <i class="fas fa-bell"></i>
                @php $unread = \App\Models\Bconnect\Notification::where('member_id', optional($bconnectMember)->id)->where('is_read', false)->count(); @endphp
                @if($unread > 0)<span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] rounded-full flex items-center justify-center">{{ $unread }}</span>@endif
            </a>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto p-4 lg:p-8" id="bcMain">
        <nav id="bcCrumbs" class="text-xs text-slate-500 mb-4 hidden sm:block"></nav>
        @if(session('success'))<div class="bc-animate mb-4 p-4 rounded-lg bg-green-500/10 text-green-400 border border-green-500/20 flex items-center gap-2"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
        @if(session('error'))<div class="bc-animate mb-4 p-4 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 flex items-center gap-2"><i class="fas fa-exclamation-circle"></i>{{ session('error') }}</div>@endif
        @if(session('info'))<div class="bc-animate mb-4 p-4 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center gap-2"><i class="fas fa-info-circle"></i>{{ session('info') }}</div>@endif
        @yield('content')
    </div>
</main>

{{-- Command palette (Ctrl+K) --}}
<div id="bcPalette" class="fixed inset-0 z-[90] hidden items-start justify-center pt-[12vh] bg-black/60 backdrop-blur-sm" onclick="if(event.target===this)bcPaletteClose()">
    <div class="w-full max-w-xl rounded-2xl overflow-hidden shadow-2xl border border-[var(--bc-border)]" style="background:var(--bc-card);">
        <div class="flex items-center gap-3 px-4 border-b border-[var(--bc-border)]">
            <i class="fas fa-search text-slate-500 text-sm"></i>
            <input id="bcPaletteInput" placeholder="Search tickets, projects, members, meetings, files…" class="flex-1 bg-transparent py-3.5 text-sm outline-none placeholder-slate-500" autocomplete="off">
            <kbd class="text-[10px] text-slate-500 border border-[var(--bc-border)] rounded px-1.5 py-0.5">ESC</kbd>
        </div>
        <div id="bcPaletteResults" class="max-h-[50vh] overflow-y-auto p-2"></div>
    </div>
</div>

{{-- Keyboard shortcuts overlay --}}
<div id="bcShortcuts" class="fixed inset-0 z-[90] hidden items-center justify-center bg-black/60 backdrop-blur-sm" onclick="if(event.target===this)this.classList.add('hidden')">
    <div class="w-full max-w-md rounded-2xl p-6 shadow-2xl border border-[var(--bc-border)]" style="background:var(--bc-card);">
        <h3 class="font-bold mb-4">Keyboard shortcuts</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-400">Search / command palette</span><kbd class="bc-kbd">Ctrl+K</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">Go to Dashboard</span><kbd class="bc-kbd">g d</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">Go to Tickets</span><kbd class="bc-kbd">g t</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">Go to Projects</span><kbd class="bc-kbd">g p</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">Go to Meetings</span><kbd class="bc-kbd">g m</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">Go to Chat</span><kbd class="bc-kbd">g c</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">Toggle theme</span><kbd class="bc-kbd">g x</kbd></div>
            <div class="flex justify-between"><span class="text-slate-400">This help</span><kbd class="bc-kbd">?</kbd></div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('mobileOverlay').classList.toggle('open');
}
(function(){
    var b = document.getElementById('bcThemeBtn');
    if (b) b.innerHTML = '<i class="fas ' + (document.documentElement.classList.contains('light') ? 'fa-moon' : 'fa-sun') + '"></i>';
})();

// ── Auto breadcrumbs from URL ──
(function(){
    var el = document.getElementById('bcCrumbs');
    if (!el) return;
    var parts = location.pathname.split('/').filter(Boolean);
    if (parts.length < 2) { el.remove(); return; }
    var html = '<a href="/dashboard" class="hover:text-cyan-400"><i class="fas fa-home"></i></a>';
    var acc = '';
    parts.forEach(function(p, i){
        acc += '/' + p;
        var label = p.replace(/-/g,' ').replace(/\b\w/g, c=>c.toUpperCase());
        if (/^\d+$/.test(p)) label = '#' + p;
        html += ' <span class="mx-1 text-slate-600">/</span> ' + (i === parts.length-1 ? '<span class="text-slate-300">'+label+'</span>' : '<a href="'+acc+'" class="hover:text-cyan-400">'+label+'</a>');
    });
    el.innerHTML = html;
})();

// ── Command palette ──
var _bcPalTimer = null;
function bcPaletteOpen(){
    var m = document.getElementById('bcPalette');
    m.classList.remove('hidden'); m.classList.add('flex');
    var i = document.getElementById('bcPaletteInput');
    i.value = ''; i.focus();
    bcPaletteRender(null);
}
function bcPaletteClose(){
    var m = document.getElementById('bcPalette');
    m.classList.add('hidden'); m.classList.remove('flex');
}
var _bcNav = [
    {t:'Dashboard', i:'fa-home', u:'/dashboard'},
    {t:'Projects', i:'fa-folder', u:'/projects'},
    {t:'Meetings', i:'fa-video', u:'/meetings'},
    {t:'Kanban', i:'fa-columns', u:'/kanban'},
    {t:'Tickets', i:'fa-bug', u:'/tickets'},
    {t:'Sprints', i:'fa-running', u:'/sprints'},
    {t:'Time Tracking', i:'fa-clock', u:'/time-tracking'},
    {t:'Billing', i:'fa-file-invoice', u:'/billing'},
    {t:'Members', i:'fa-users', u:'/members'},
    {t:'Chat', i:'fa-comments', u:'/chat'},
    {t:'Files', i:'fa-folder-open', u:'/files'},
    {t:'Remote Access', i:'fa-desktop', u:'/remote'},
    {t:'Audit Logs', i:'fa-shield-alt', u:'/audit'},
    {t:'My Profile', i:'fa-user', u:'/profile'},
    {t:'Settings', i:'fa-cog', u:'/settings'},
];
function bcPalRow(icon, title, sub, url){
    return '<a href="'+url+'" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition">'
        + '<span class="w-7 h-7 rounded-lg bg-cyan-500/15 text-cyan-400 flex items-center justify-center text-xs"><i class="fas '+icon+'"></i></span>'
        + '<span class="flex-1 min-w-0"><span class="block text-sm font-medium truncate">'+title+'</span>'
        + (sub ? '<span class="block text-[11px] text-slate-500">'+sub+'</span>' : '') + '</span></a>';
}
function bcPaletteRender(d){
    var r = document.getElementById('bcPaletteResults');
    var q = document.getElementById('bcPaletteInput').value.trim().toLowerCase();
    if (!d) {
        var items = q ? _bcNav.filter(n => n.t.toLowerCase().includes(q)) : _bcNav;
        r.innerHTML = '<p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Navigate</p>'
            + items.map(n => bcPalRow(n.i, n.t, null, n.u)).join('')
            + (q.length >= 2 ? '<p class="px-3 py-2 text-[10px] font-bold uppercase tracking-wider text-slate-500 mt-2">Search all — press Enter…</p>' : '');
        return;
    }
    var h = '';
    (d.tickets||[]).forEach(x => h += bcPalRow('fa-bug', x.title, 'Ticket · '+(x.status||''), '/tickets/'+x.id));
    (d.projects||[]).forEach(x => h += bcPalRow('fa-folder', x.name, 'Project · '+(x.status||''), '/projects/'+x.id));
    (d.members||[]).forEach(x => h += bcPalRow('fa-user', x.name, 'Member · '+(x.role||''), '/members'));
    (d.meetings||[]).forEach(x => h += bcPalRow('fa-video', x.title, 'Meeting · '+(x.room_id||''), '/meetings/'+x.room_id));
    (d.files||[]).forEach(x => h += bcPalRow('fa-file', x.name, 'File', '/files'));
    r.innerHTML = h || '<p class="px-3 py-6 text-center text-sm text-slate-500">No results for “'+q+'”</p>';
}
document.getElementById('bcPaletteInput').addEventListener('input', function(){
    var q = this.value.trim();
    bcPaletteRender(null);
    clearTimeout(_bcPalTimer);
    if (q.length < 2) return;
    _bcPalTimer = setTimeout(function(){
        fetch('/search?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}})
            .then(r => r.json()).then(bcPaletteRender).catch(()=>{});
    }, 220);
});
document.getElementById('bcPaletteInput').addEventListener('keydown', function(e){
    if (e.key === 'Enter') {
        var first = document.querySelector('#bcPaletteResults a');
        if (first) location.href = first.href;
    }
});

// ── Keyboard shortcuts ──
var _bcG = false;
document.addEventListener('keydown', function(e){
    var tag = (e.target.tagName || '').toLowerCase();
    var typing = tag === 'input' || tag === 'textarea' || e.target.isContentEditable;
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); bcPaletteOpen(); return; }
    if (e.key === 'Escape') { bcPaletteClose(); document.getElementById('bcShortcuts').classList.add('hidden'); document.getElementById('bcShortcuts').classList.remove('flex'); return; }
    if (typing) return;
    if (e.key === '?') { var s = document.getElementById('bcShortcuts'); s.classList.toggle('hidden'); s.classList.toggle('flex'); return; }
    if (_bcG) {
        _bcG = false;
        var map = {d:'/dashboard', t:'/tickets', p:'/projects', m:'/meetings', c:'/chat'};
        if (e.key === 'x') { bcToggleTheme(); return; }
        if (map[e.key]) { location.href = map[e.key]; return; }
    }
    if (e.key === 'g') { _bcG = true; setTimeout(function(){ _bcG = false; }, 900); }
});
</script>
<style>.bc-kbd{font-family:ui-monospace,monospace;font-size:10px;background:rgba(255,255,255,.07);border:1px solid var(--bc-border);border-radius:5px;padding:2px 6px;color:var(--bc-text-muted,#94a3b8);}</style>

{{-- Column customization — any table with data-colt on cells gets a Columns picker --}}
<script>
(function(){
    var table = document.querySelector('table[data-coltable]');
    if (!table) return;
    var key = 'bconnect_cols_' + location.pathname;
    var saved = {};
    try { saved = JSON.parse(localStorage.getItem(key) || '{}'); } catch(e) {}
    var ths = table.querySelectorAll('thead th');
    var menu = document.createElement('div');
    menu.className = 'flex justify-end mb-2';
    menu.innerHTML = '<details class="relative"><summary class="bc-btn bc-btn-secondary text-xs cursor-pointer list-none"><i class="fas fa-sliders-h mr-1"></i>Columns</summary>'
        + '<div class="absolute right-0 mt-2 w-44 rounded-lg border border-[var(--bc-border)] p-2 z-40 shadow-xl" style="background:var(--bc-card)">' 
        + [...ths].map((th,i)=>th.dataset.colname ? '<label class="flex items-center gap-2 px-2 py-1.5 text-xs rounded hover:bg-white/5 cursor-pointer"><input type="checkbox" data-coli="'+i+'" class="accent-cyan-500" checked>'+th.dataset.colname+'</label>' : '').join('')
        + '</div></details>';
    table.parentNode.insertBefore(menu, table);
    function applyCol(i, on){
        table.querySelectorAll('tr').forEach(r => {
            var c = r.children[i];
            if (c) c.style.display = on ? '' : 'none';
        });
    }
    menu.querySelectorAll('input[data-coli]').forEach(cb => {
        var i = +cb.dataset.coli;
        if (saved[i] === false) { cb.checked = false; applyCol(i, false); }
        cb.onchange = () => { saved[i] = cb.checked; localStorage.setItem(key, JSON.stringify(saved)); applyCol(i, cb.checked); };
    });
})();
</script>
</body>
</html>
