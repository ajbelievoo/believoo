<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Clytrix') }} - Client Dashboard</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        :root {
            --dark-bg: #0f172a;
            --dark-card: #1e293b;
            --dark-border: #334155;
            --primary: #4f46e5;
            --primary-light: #6366f1;
        }
        body {
            background: var(--dark-bg);
            color: #e2e8f0;
        }
        .sidebar {
            background: var(--dark-card);
            border-right: 1px solid var(--dark-border);
        }
        .nav-link {
            color: #94a3b8;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background: rgba(79, 70, 229, 0.1);
            border-right: 3px solid var(--primary);
        }
        .card {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
        }
        .stat-card {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
            border: 1px solid rgba(79, 70, 229, 0.2);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #e2e8f0;
            border: 1px solid var(--dark-border);
        }
        .table-header {
            background: rgba(15, 23, 42, 0.5);
        }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body class="antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="sidebar fixed inset-y-0 left-0 z-50 w-64 transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-300" :class="{ 'translate-x-0': sidebarOpen }">
            <div class="h-full flex flex-col">
                <div class="p-6 border-b border-gray-700">
                    <a href="{{ route('home') }}" class="flex items-center dark">
                        <x-site-logo class="h-12 w-auto" />
                    </a>
                </div>
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                    <a href="{{ route('dashboard') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-home w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('client.orders') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('client.orders') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart w-5"></i>
                        <span>My Orders</span>
                    </a>
                    <a href="#" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg">
                        <i class="fas fa-server w-5"></i>
                        <span>My Services</span>
                    </a>
                    <a href="#" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg">
                        <i class="fas fa-globe w-5"></i>
                        <span>Domains</span>
                    </a>
                    <a href="#" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg">
                        <i class="fas fa-ticket-alt w-5"></i>
                        <span>Support Tickets</span>
                    </a>
                    <a href="#" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg">
                        <i class="fas fa-file-invoice w-5"></i>
                        <span>Invoices</span>
                    </a>
                    <div class="pt-4 mt-4 border-t border-gray-700">
                        <p class="px-4 text-xs font-semibold text-gray-500 uppercase mb-2">Account</p>
                        <a href="{{ route('profile.show') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('profile.show') ? 'active' : '' }}">
                            <i class="fas fa-user w-5"></i>
                            <span>Profile</span>
                        </a>
                        <a href="{{ route('teams.index') }}" class="nav-link flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('teams.*') ? 'active' : '' }}">
                            <i class="fas fa-users w-5"></i>
                            <span>Team</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="nav-link w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left text-red-400 hover:text-red-300">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Header -->
            <header class="card border-b px-4 py-4 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-lg hover:bg-gray-700">
                            <i class="fas fa-bars text-gray-400"></i>
                        </button>
                        <h1 class="text-xl font-semibold text-white">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="p-2 rounded-lg hover:bg-gray-700 relative">
                            <i class="fas fa-bell text-gray-400"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </button>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-semibold text-white text-sm">
                                {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                            </div>
                            <span class="hidden md:block text-sm text-gray-300">{{ Auth::user()->name ?? 'User' }}</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
