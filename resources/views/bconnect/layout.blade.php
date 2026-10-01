<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<link rel="stylesheet" href="/css/bconnect.css">
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
<aside id="sidebar" class="bc-sidebar w-64 bg-[#0b1220] border-r border-[var(--bc-border)] flex flex-col h-screen z-50">
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
    </nav>
    <div class="p-4 border-t border-[var(--bc-border)]">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-sm">{{ substr(Auth::user()->name,0,1) }}</div>
            <div class="text-sm">
                <div class="font-medium truncate max-w-[120px]">{{ Auth::user()->name }}</div>
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
            <a href="{{ route('bconnect.billing') }}" class="hidden sm:flex items-center gap-2 text-xs text-slate-400 hover:text-white bc-badge bc-badge-slate">
                <i class="fas fa-crown text-cyan-400"></i>{{ ucfirst($bconnectCompany?->plan ?? 'free') }}
            </a>
            <a href="{{ route('bconnect.notifications') }}" class="relative text-slate-400 hover:text-white text-lg">
                <i class="fas fa-bell"></i>
                @php $unread = \App\Models\Bconnect\Notification::where('member_id', optional($bconnectMember)->id)->where('is_read', false)->count(); @endphp
                @if($unread > 0)<span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] rounded-full flex items-center justify-center">{{ $unread }}</span>@endif
            </a>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto p-4 lg:p-8">
        @if(session('success'))<div class="bc-animate mb-4 p-4 rounded-lg bg-green-500/10 text-green-400 border border-green-500/20 flex items-center gap-2"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
        @if(session('error'))<div class="bc-animate mb-4 p-4 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 flex items-center gap-2"><i class="fas fa-exclamation-circle"></i>{{ session('error') }}</div>@endif
        @if(session('info'))<div class="bc-animate mb-4 p-4 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 flex items-center gap-2"><i class="fas fa-info-circle"></i>{{ session('info') }}</div>@endif
        @yield('content')
    </div>
</main>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('mobileOverlay').classList.toggle('open');
}
</script>
</body>
</html>
