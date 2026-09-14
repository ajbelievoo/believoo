<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @php
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            $siteName = $settings['site_name'] ?? config('app.name', 'Laravel');
            $favicon = $settings['favicon'] ?? null;
            $faviconUrl = $favicon ? asset('storage/' . $favicon) : '/favicon.ico';
            if($favicon) {
                $faviconPath = storage_path('app/public/' . $favicon);
                if(file_exists($faviconPath)) {
                    $faviconUrl .= '?v=' . filemtime($faviconPath);
                }
            }
        @endphp

        <title>{{ $siteName }} - {{ $settings['site_tagline'] ?? 'Premium Software Development Agency' }}</title>

        <!-- SEO Meta Tags -->
        <meta name="description" content="{{ $settings['meta_description'] ?? 'Believoo - Premium VPS, web hosting, live streaming and domain services for businesses worldwide.' }}">
        <meta name="keywords" content="{{ $settings['meta_keywords'] ?? 'VPS hosting, web hosting, live streaming, domain, server management' }}">
        <meta name="author" content="{{ $siteName }}">
        <meta name="robots" content="index, follow">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph / Twitter -->
        <meta property="og:title" content="{{ $siteName }}">
        <meta property="og:description" content="{{ $settings['meta_description'] ?? '' }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $siteName }}">
        <meta name="twitter:description" content="{{ $settings['meta_description'] ?? '' }}">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- FontAwesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Google Analytics -->
        @if(!empty($settings['google_analytics']))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings['google_analytics'] }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '{{ $settings['google_analytics'] }}');
            </script>
        @endif

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50">
        <div class="min-h-screen flex">
            <!-- Sidebar -->
            <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col fixed h-full z-10">
                <!-- Logo -->
                <div class="h-16 flex items-center px-6 border-b border-gray-200">
                    <a href="{{ route('client.dashboard') }}" class="flex items-center">
                        <x-site-logo class="h-12 w-auto" />
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                    <a href="{{ route('client.dashboard') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('client.dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-home w-5 h-5 mr-3 {{ request()->routeIs('client.dashboard') ? 'text-blue-600' : 'text-gray-400' }}"></i>
                        Dashboard
                    </a>

                    <a href="{{ route('client.migration.wizard') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('client.migration.*') ? 'bg-green-50 text-green-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-rocket w-5 h-5 mr-3 {{ request()->routeIs('client.migration.*') ? 'text-green-600' : 'text-gray-400' }}"></i>
                        Migration Wizard
                        <span style="margin-left: auto; padding: 2px 6px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border-radius: 4px; font-size: 0.6rem; font-weight: 700;">NEW</span>
                    </a>

                    <a href="{{ route('profile.edit') }}" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg {{ request()->routeIs('profile.edit') ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                        <i class="fas fa-user w-5 h-5 mr-3 {{ request()->routeIs('profile.edit') ? 'text-blue-600' : 'text-gray-400' }}"></i>
                        Profile
                    </a>

                    <a href="#" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50 hover:text-gray-900">
                        <i class="fas fa-project-diagram w-5 h-5 mr-3 text-gray-400"></i>
                        Projects
                    </a>

                    <a href="#" 
                       class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-700 hover:bg-gray-50 hover:text-gray-900">
                        <i class="fas fa-ticket-alt w-5 h-5 mr-3 text-gray-400"></i>
                        Support Tickets
                    </a>
                </nav>

                <!-- User Section -->
                <div class="border-t border-gray-200 p-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Log Out
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 md:ml-64">
                <!-- Mobile Header -->
                <header class="md:hidden bg-white border-b border-gray-200 sticky top-0 z-20">
                    <div class="flex items-center justify-between h-16 px-4">
                        <a href="{{ route('client.dashboard') }}" class="flex items-center">
                            <x-site-logo class="h-12 w-auto" />
                        </a>
                        <button x-data="{ open: false }" @click="open = !open" class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="py-8 px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
