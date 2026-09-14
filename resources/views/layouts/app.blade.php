<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="vapid-key" content="{{ \App\Models\Setting::where('key','vapid_public_key')->value('value') ?? '' }}">
        @auth
        <meta name="user-id" content="{{ Auth::id() }}">
        @endauth

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

        <title>@yield('title', $siteName . ' - ' . ($settings['site_tagline'] ?? 'Premium VPS, Web Hosting & Live Streaming'))</title>

        <!-- SEO Meta Tags -->
        <meta name="description" content="@yield('meta_description', $settings['meta_description'] ?? 'Believoo - Premium VPS, web hosting, live streaming and domain services for businesses worldwide.')">
        <meta name="keywords" content="@yield('meta_keywords', $settings['meta_keywords'] ?? 'VPS hosting, web hosting, live streaming, domain, server management')">
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
        @if(file_exists(public_path('css/themes.css'))) <link rel="stylesheet" href="{{ asset('css/themes.css') }}"> @endif

        <!-- GLOBAL THEME SYSTEM -->
        <script>
            (function() {
                var theme = localStorage.getItem('site-theme');
                
                // Force Midnight Onyx theme for streaming pages
                var forceMidnightOnyx = {{ isset($dataTheme) && $dataTheme === 'midnight-onyx' ? 'true' : 'false' }};
                
                if (forceMidnightOnyx) {
                    theme = 'midnight-onyx';
                } else if (theme !== 'light' && theme !== 'dark') {
                    theme = 'dark';
                }
                
                var html = document.documentElement;
                
                // Apply theme classes
                if (theme === 'midnight-onyx') {
                    html.classList.add('midnight-onyx');
                    html.classList.remove('dark', 'light');
                    localStorage.setItem('site-theme', 'midnight-onyx');
                } else if (theme === 'dark') {
                    html.classList.add('dark');
                    html.classList.remove('light', 'midnight-onyx');
                } else {
                    html.classList.add('light');
                    html.classList.remove('dark', 'midnight-onyx');
                }
                
                window.toggleGlobalTheme = function() {
                    var isMidnightOnyx = html.classList.contains('midnight-onyx');
                    var isDark = html.classList.contains('dark');
                    
                    if (isMidnightOnyx) {
                        // Don't allow theme switching from midnight-onyx
                        return false;
                    } else if (isDark) {
                        html.classList.remove('dark'); html.classList.add('light');
                        localStorage.setItem('site-theme', 'light');
                    } else {
                        html.classList.add('dark'); html.classList.remove('light');
                        localStorage.setItem('site-theme', 'dark');
                    }
                    return !isDark;
                };
            })();
        </script>
    </head>
    <body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900 transition-colors duration-300">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @if(isset($slot))
                    {{ $slot }}
                @else
                    @yield('content')
                @endif
            </main>
        </div>

        @livewireScripts
    <style>
        /* =====================================================
           LIGHT MODE GLOBAL OVERRIDES - APP LAYOUT
           ===================================================== */
        html.light .bg-dark {
            background-color: #ffffff !important;
        }
        html.light .bg-dark-100 {
            background-color: #f1f5f9 !important;
        }
        html.light .glass {
            background: rgba(255, 255, 255, 0.85) !important;
            border-color: rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06) !important;
        }
        html.light .glass-premium {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(241,245,249,0.95) 100%) !important;
            border-color: rgba(0,0,0,0.08) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08) !important;
        }
        html.light .glass-strong {
            background: #ffffff !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
        }
        html.light .text-white {
            color: #0f172a !important;
        }
        html.light .text-gray-200 {
            color: #374151 !important;
        }
        html.light .text-gray-300 {
            color: #4b5563 !important;
        }
        html.light .text-gray-400 {
            color: #6b7280 !important;
        }
        html.light .text-gray-500 {
            color: #64748b !important;
        }
        html.light .text-gray-600 {
            color: #d1d5db !important;
        }
        html.light .text-gray-700 {
            color: #e5e7eb !important;
        }
        html.light .text-gray-800 {
            color: #f3f4f6 !important;
        }
        html.light .text-gray-900 {
            color: #f9fafb !important;
        }
        html.light .bg-gray-100 {
            background-color: #f3f4f6 !important;
        }
        html.light .bg-gray-200 {
            background-color: #e5e7eb !important;
        }
        html.light .bg-gray-300 {
            background-color: #d1d5db !important;
        }
        html.light .bg-gray-400 {
            background-color: #9ca3af !important;
        }
        html.light .bg-gray-500 {
            background-color: #6b7280 !important;
        }
        html.light .bg-gray-600 {
            background-color: #4b5563 !important;
        }
        html.light .bg-gray-700 {
            background-color: #374151 !important;
        }
        html.light .bg-gray-800 {
            background-color: #1f2937 !important;
        }
        html.light .bg-gray-900 {
            background-color: #111827 !important;
        }

        /* =====================================================
           CHECKOUT PAGE LIGHT MODE OVERRIDES
           ===================================================== */
        html.light .checkout-page-bg,
        html.light [class*="bg-[#0a0a1a]"],
        html.light [class*="bg-[#0a0a0a]"],
        html.light [class*="bg-[#111]"] {
            background: #f1f5f9 !important;
        }
        html.light .checkout-card {
            background: white !important;
            border: 1px solid rgba(0,0,0,0.08) !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06) !important;
        }
        html.light .checkout-card h2,
        html.light .checkout-card h3,
        html.light .checkout-label {
            color: #0f172a !important;
        }
        html.light .checkout-card p,
        html.light .checkout-text {
            color: #64748b !important;
        }
        html.light .billing-cycle-card {
            background: white !important;
            border-color: #e2e8f0 !important;
        }
        html.light .price-display {
            color: #0f172a !important;
        }
        html.light .price-total {
            color: #0ea5e9 !important;
        }

        /* =====================================================
           MIDNIGHT ONYX THEME - STREAMING PAGES
           ===================================================== */
        html.midnight-onyx {
            background: #0a0a1a !important;
        }
        html.midnight-onyx body {
            background: #0a0a1a !important;
            color: #ffffff !important;
        }
        html.midnight-onyx .bg-gray-100,
        html.midnight-onyx .bg-gray-200,
        html.midnight-onyx .bg-gray-300 {
            background: rgba(17, 17, 43, 0.6) !important;
        }
        html.midnight-onyx .bg-white {
            background: rgba(17, 17, 43, 0.8) !important;
        }
        html.midnight-onyx .glass {
            background: rgba(17, 17, 43, 0.6) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(0, 212, 255, 0.1) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
        }
        html.midnight-onyx .glass-premium {
            background: linear-gradient(135deg, rgba(17, 17, 43, 0.8) 0%, rgba(37, 43, 61, 0.9) 100%) !important;
            backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(0, 212, 255, 0.2) !important;
            box-shadow: 0 16px 64px rgba(0, 0, 0, 0.4) !important;
        }
        html.midnight-onyx .glass-strong {
            background: rgba(17, 17, 43, 0.95) !important;
            border: 1px solid rgba(0, 212, 255, 0.3) !important;
        }
        html.midnight-onyx .text-white {
            color: #ffffff !important;
        }
        html.midnight-onyx .text-gray-200,
        html.midnight-onyx .text-gray-300,
        html.midnight-onyx .text-gray-400 {
            color: #94a3b8 !important;
        }
        html.midnight-onyx .text-gray-500 {
            color: #64748b !important;
        }
        html.midnight-onyx .text-gray-600 {
            color: #475569 !important;
        }
        html.midnight-onyx .text-gray-700,
        html.midnight-onyx .text-gray-800,
        html.midnight-onyx .text-gray-900 {
            color: #334155 !important;
        }
        html.midnight-onyx .border-white\/10 {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        html.midnight-onyx .border-white\/20 {
            border-color: rgba(255, 255, 255, 0.2) !important;
        }
        html.midnight-onyx .bg-black\/30 {
            background: rgba(0, 0, 0, 0.3) !important;
        }
        html.midnight-onyx .hover\:bg-white\/10:hover {
            background: rgba(255, 255, 255, 0.1) !important;
        }
        html.midnight-onyx .hover\:text-white:hover {
            color: #ffffff !important;
        }

    </style>
    <script src="/push-init.js"></script>
    </body>
</html>
