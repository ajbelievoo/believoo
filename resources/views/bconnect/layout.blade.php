<!DOCTYPE html>
<html lang="en" class="dark">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', $bconnectBrand['title'])</title>
<meta name="description" content="{{ $bconnectBrand['description'] }}">
<meta name="keywords" content="{{ $bconnectBrand['keywords'] }}">
<link rel="icon" type="image/png" href="{{ $bconnectBrand['favicon'] }}">
<link rel="shortcut icon" type="image/x-icon" href="{{ $bconnectBrand['favicon'] }}">
<meta name="theme-color" content="#00B7FF">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{brand:'#06b6d4'}}};</script>
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="vapid-key" content="{{ \App\Models\Setting::where('key', 'vapid_public_key')->value('value') ?? '' }}">
<link rel="manifest" href="/manifest.json">
@vite(['resources/js/app.js'])
<script src="/push-init.js"></script>
<style>body{background:#0f172a;color:#e2e8f0;font-family:Inter,ui-sans-serif,system-ui;}</style>
</head>
<body class="min-h-screen flex">
<aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col">
    <div class="p-6 flex items-center gap-3">
        <img src="{{ $bconnectBrand['logo'] }}" class="h-8 w-8 rounded" alt="B-CONNECT">
        <div><h1 class="text-xl font-black text-cyan-400">B-CONNECT</h1><p class="text-xs text-slate-500 mt-1">{{ $bconnectCompany->name ?? 'Workspace' }}</p></div>
    </div>
    <nav class="flex-1 px-4 space-y-1">
        <a href="{{ route('bconnect.dashboard') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.dashboard') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-home w-6"></i>Dashboard</a>
        <a href="{{ route('bconnect.projects.index') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.projects.*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-folder w-6"></i>Projects</a>
        <a href="{{ route('bconnect.meetings') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.meeting*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-video w-6"></i>Meetings</a>
        <a href="{{ route('bconnect.tickets') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.tickets*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-bug w-6"></i>Tickets</a>
        @if(in_array($bconnectCompany?->plan ?? 'free', ['pro','enterprise']))
        <a href="{{ route('bconnect.remote') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.remote*') && !request()->routeIs('bconnect.remote.agent') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-desktop w-6"></i>Remote</a>
        <a href="{{ route('bconnect.remote.agent') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.remote.agent') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-download w-6"></i>Desktop Agent</a>
        @endif
        <a href="{{ route('bconnect.billing') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.billing*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-file-invoice w-6"></i>Billing</a>
        <a href="{{ route('bconnect.members') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.members*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-users w-6"></i>Members</a>
        <a href="{{ route('bconnect.company') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.company*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-building w-6"></i>Company</a>
        <a href="{{ route('bconnect.files') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.files*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-folder-open w-6"></i>Files</a>
        <a href="{{ route('bconnect.audit') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.audit*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-shield-alt w-6"></i>Audit Logs</a>
        <a href="{{ route('bconnect.support') }}" class="block px-4 py-3 rounded-lg hover:bg-slate-800 {{ request()->routeIs('bconnect.support*') ? 'bg-cyan-500/10 text-cyan-400' : 'text-slate-300' }}"><i class="fas fa-question-circle w-6"></i>Support</a>
    </nav>
    <div class="p-4 border-t border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold text-xs">{{ substr(Auth::user()->name,0,1) }}</div>
            <div class="text-sm"><div class="font-medium">{{ Auth::user()->name }}</div><div class="text-slate-500 text-xs">{{ ucfirst($bconnectRole ?? '') }}</div></div>
        </div>
        <form method="POST" action="{{ route('bconnect.logout') }}" class="mt-3">@csrf
            <button type="submit" class="w-full text-left px-4 py-2 rounded-lg hover:bg-red-500/10 text-red-400 text-sm"><i class="fas fa-sign-out-alt mr-2"></i>Logout</button>
        </form>
    </div>
</aside>
<main class="flex-1 flex flex-col overflow-hidden">
    <header class="h-16 bg-slate-900/80 border-b border-slate-800 flex items-center justify-between px-8">
        <h2 class="font-bold text-lg">@yield('title')</h2>
        <div class="flex items-center gap-4">
            <a href="{{ route('bconnect.notifications') }}" class="relative text-slate-400 hover:text-white">
                <i class="fas fa-bell"></i>
                @php $unread = \App\Models\Bconnect\Notification::where('member_id', optional($bconnectMember)->id)->where('is_read', false)->count(); @endphp
                @if($unread > 0)<span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-[10px] rounded-full flex items-center justify-center">{{ $unread }}</span>@endif
            </a>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto p-8">
        @if(session('success'))<div class="mb-4 p-4 rounded-lg bg-green-500/10 text-green-400 border border-green-500/20">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-4 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20">{{ session('error') }}</div>@endif
        @yield('content')
    </div>
</main>
</body>
</html>
