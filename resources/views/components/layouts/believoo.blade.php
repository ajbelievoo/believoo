<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    @php
        $settings = $settings ?? \App\Models\Setting::pluck('value', 'key');
        $siteName = $settings['site_name'] ?? config('app.name', 'Believoo');
        $siteTitle = $settings['meta_title'] ?? $siteName;
        $siteDesc = $settings['meta_description'] ?? 'Premium VPS, web hosting and custom software development.';
        $siteKeywords = $settings['meta_keywords'] ?? 'vps, hosting, cloud, domains, software development';
        $pageTitle = $title ?? $siteTitle;
        $pageDesc = $description ?? $siteDesc;
        $pageKeywords = $keywords ?? $siteKeywords;
        $favicon = $settings['favicon'] ?? null;
        $faviconUrl = $favicon ? asset('storage/' . $favicon) : '/favicon.ico';
        if($favicon) {
            $faviconPath = storage_path('app/public/' . $favicon);
            if(file_exists($faviconPath)) {
                $faviconUrl .= '?v=' . filemtime($faviconPath);
            }
        }
        if (!empty($siteName) && !str_contains(strtolower($pageTitle), strtolower($siteName))) {
            $pageTitle .= ' | ' . $siteName;
        }
    @endphp

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDesc }}">
    <meta name="keywords" content="{{ $pageKeywords }}">
    <meta name="author" content="{{ $siteName }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph -->
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDesc }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="en_IN">
    @php
        $ogImage = !empty($settings['og_image'] ?? '')
            ? (str_starts_with($settings['og_image'], 'http') ? $settings['og_image'] : asset('storage/' . $settings['og_image']))
            : asset('og-image.png');
    @endphp
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $siteName }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDesc }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="icon" type="image/png" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="/images/icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="msapplication-TileImage" content="/images/icon-192x192.png">
    <meta name="theme-color" content="#00B7FF">

    @if(!empty($settings['google_site_verification']))
    <meta name="google-site-verification" content="{{ $settings['google_site_verification'] }}">
    @endif
    @if(!empty($settings['bing_site_verification']))
    <meta name="msvalidate.01" content="{{ $settings['bing_site_verification'] }}">
    @endif

    <!-- Google Analytics 4 -->
    @if(!empty($settings['google_analytics']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings['google_analytics'] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($settings['google_analytics']));
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- GLOBAL THEME SYSTEM - Must run before any rendering -->
    <script>
        (function() {
            // Read theme from localStorage (default: light)
            var theme = localStorage.getItem('site-theme-v2');
            if (theme !== 'light' && theme !== 'dark') theme = 'light';

            // Apply theme class to <html> immediately (before CSS loads)
            var html = document.documentElement;
            if (theme === 'dark') {
                html.classList.add('dark');
                html.classList.remove('light');
            } else {
                html.classList.add('light');
                html.classList.remove('dark');
            }

            // Global theme toggle function
            window.toggleGlobalTheme = function() {
                var isDark = html.classList.contains('dark');
                if (isDark) {
                    html.classList.remove('dark');
                    html.classList.add('light');
                    localStorage.setItem('site-theme-v2', 'light');
                } else {
                    html.classList.add('dark');
                    html.classList.remove('light');
                    localStorage.setItem('site-theme-v2', 'dark');
                }
                // Sync all theme icons
                syncThemeIcons();
                // Sync Alpine store if available (for hosting-landing page)
                if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('darkMode')) {
                    try { Alpine.store('darkMode').on = !isDark; } catch(e) {}
                }
                // Dispatch event for Alpine components
                window.dispatchEvent(new CustomEvent('global-theme-changed', {
                    detail: { dark: !isDark }
                }));
                return !isDark;
            };

            // Sync theme icon states
            window.syncThemeIcons = function() {
                var isDark = html.classList.contains('dark');
                var icons = document.querySelectorAll('.theme-toggle-icon');
                icons.forEach(function(icon) {
                    icon.className = 'fas theme-toggle-icon ' + (isDark ? 'fa-sun text-amber-600' : 'fa-moon text-slate-400');
                });
                var labels = document.querySelectorAll('.theme-toggle-label');
                labels.forEach(function(label) {
                    label.textContent = isDark ? 'Light' : 'Dark';
                });
            };

            // Listen for storage events (other tabs)
            window.addEventListener('storage', function(e) {
                if (e.key === 'site-theme-v2') {
                    var newTheme = e.newValue;
                    var isDark = newTheme === 'dark';
                    if (isDark) {
                        html.classList.add('dark');
                        html.classList.remove('light');
                    } else {
                        html.classList.add('light');
                        html.classList.remove('dark');
                    }
                    syncThemeIcons();
                    // Sync Alpine store if available
                    if (typeof Alpine !== 'undefined' && Alpine.store && Alpine.store('darkMode')) {
                        try { Alpine.store('darkMode').on = isDark; } catch(e) {}
                    }
                }
            });
        })();
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('darkMode', {
                on: localStorage.getItem('site-theme-v2') !== 'light',
                toggle() {
                    this.on = !this.on;
                    localStorage.setItem('site-theme-v2', this.on ? 'dark' : 'light');
                    // Also trigger global toggle
                    if (document.documentElement.classList.contains('dark') !== this.on) {
                        window.toggleGlobalTheme();
                    }
                }
            });
        });
    </script>
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .glass {
            background: rgba(26, 26, 26, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .animate-scroll {
            animation: scroll 30s linear infinite;
        }
        .animate-scroll:hover {
            animation-play-state: paused;
        }
        @keyframes float {
            0% { transform: translateY(0) translateX(0); }
            33% { transform: translateY(-50px) translateX(20px); }
            66% { transform: translateY(-20px) translateX(-30px); }
            100% { transform: translateY(0) translateX(0); }
        }
        
        header {
            z-index: 999999999 !important;
            pointer-events: auto !important;
        }
        
        main {
            z-index: 1 !important;
            position: relative !important;
        }
        
        .support-hub-container {
            z-index: 1000000000 !important;
        }

        /* =====================================================
           LIGHT MODE GLOBAL OVERRIDES
           These override hardcoded dark classes when html.light
           ===================================================== */
        html.light .glass {
            background: rgba(255, 255, 255, 0.85) !important;
            border-color: rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06) !important;
        }
        html.light .glass-strong {
            background: #ffffff !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08) !important;
        }
        html.light .card,
        html.light .card-hover {
            background: #ffffff !important;
            border-color: rgba(0,0,0,0.08) !important;
        }
        html.light .bg-dark {
            background-color: #ffffff !important;
        }
        html.light .bg-dark-100 {
            background-color: #f1f5f9 !important;
        }
        html.light [class*="bg-dark-100/"] {
            background-color: #f1f5f9 !important;
        }
        html.light .bg-\[\#0a0a1a\] {
            background-color: #f8fafc !important;
        }
        /* Inputs & selects in light mode */
        html.light input[class*="bg-dark"],
        html.light select[class*="bg-dark"],
        html.light textarea[class*="bg-dark"] {
            background-color: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html.light input::placeholder,
        html.light textarea::placeholder {
            color: #94a3b8 !important;
        }
        /* Border overrides */
        html.light .border-white\/5,
        html.light .border-white\/10,
        html.light .border-white\/20 {
            border-color: rgba(0,0,0,0.08) !important;
        }
        html.light .border-t-white\/5,
        html.light .border-t-white\/10 {
            border-top-color: rgba(0,0,0,0.08) !important;
        }
        html.light .border-b-white\/5,
        html.light .border-b-white\/10 {
            border-bottom-color: rgba(0,0,0,0.08) !important;
        }
        /* Text color overrides - removed global inversions, use Tailwind defaults */
        /* Card hover in light mode */
        html.light .card-hover:hover {
            border-color: rgba(0,183,255,0.2) !important;
        }
        /* Gradients on dark backgrounds need light equivalents */
        html.light .bg-gradient-to-t.from-dark {
            background: linear-gradient(to top, #f1f5f9, transparent) !important;
        }
        html.light .bg-gradient-to-b.from-dark {
            background: linear-gradient(to bottom, #f1f5f9, transparent) !important;
        }
        /* Portfolio overlay in light mode */
        html.light .group\/portfolio .bg-gradient-to-t.from-dark,
        html.light a.group .bg-gradient-to-t.from-dark {
            background: linear-gradient(to top, rgba(255,255,255,0.95), rgba(255,255,255,0.3), transparent) !important;
        }
        html.light .group\/portfolio .via-dark\/30,
        html.light a.group .via-dark\/30 {
            background: transparent !important;
        }
        /* Stat cards */
        html.light .bg-white\/5 {
            background: rgba(0,0,0,0.03) !important;
        }
        html.light .bg-white\/10 {
            background: rgba(0,0,0,0.05) !important;
        }
        html.light .hover\:bg-white\/5:hover,
        html.light .hover\:bg-white\/10:hover {
            background: rgba(0,0,0,0.05) !important;
        }
        /* Progress bar in inquiry form */
        html.light .bg-white\/5 {
            background: rgba(0,0,0,0.05) !important;
        }
        /* Dashboard glass-premium */
        html.light .glass-premium {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(241,245,249,0.95) 100%) !important;
            border-color: rgba(0,0,0,0.08) !important;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08) !important;
        }
        html.light .glass-premium:hover {
            border-color: rgba(0,183,255,0.3) !important;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1), 0 0 30px rgba(0,183,255,0.05) !important;
        }
        /* Dashboard specific card backgrounds */
        html.light [class*="bg-dark/"] {
            background-color: rgba(241,245,249,0.9) !important;
        }
        /* Table rows */
        html.light tbody tr:hover {
            background: rgba(0,0,0,0.03) !important;
        }
        /* Modal overlays */
        html.light [class*="bg-dark/90"] {
            background-color: rgba(255,255,255,0.9) !important;
        }
        html.light [class*="backdrop-blur"] {
            backdrop-filter: blur(12px) !important;
        }
        /* Service cards with dark backgrounds */
        html.light .hover\:bg-white\/5:hover,
        html.light .hover\:bg-white\/10:hover {
            background: rgba(0,0,0,0.04) !important;
        }
        /* Section label badges */
        html.light .section-label {
            background: rgba(0,183,255,0.08) !important;
            color: #0284c7 !important;
            border-color: rgba(0,183,255,0.15) !important;
        }
        /* Welcome page hero animated bg opacity in light mode */
        html.light .opacity-20,
        html.light .opacity-10,
        html.light .opacity-30 {
            opacity: 0.06 !important;
        }
        /* Grid overlay in light mode */
        html.light [class*="bg-\[linear-gradient(rgba(255,255,255,0.02)"] {
            opacity: 0.3 !important;
        }

        /* =====================================================
           HOSTING-LANDING PAGE CUSTOM CLASSES - LIGHT MODE
           ===================================================== */
        html.light .theme-dark { display: none !important; }
        html.light .theme-light { display: block !important; }
        html.light .bg-dark-animated {
            background: linear-gradient(135deg, #f0f9ff 0%, #ffffff 50%, #f5f3ff 100%) !important;
        }
        html.light .bg-dark-animated::before {
            background-image:
                radial-gradient(circle at 20% 50%, rgba(14, 165, 233, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 60% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 40%) !important;
        }
        html.light .hosting-card-dark {
            background: rgba(255, 255, 255, 0.95) !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
            backdrop-filter: none !important;
        }
        html.light .btn-outline-dark {
            background: white !important;
            border: 1px solid rgba(0, 0, 0, 0.2) !important;
            color: #0f172a !important;
        }
        html.light .btn-outline-dark:hover {
            border-color: #0ea5e9 !important;
            color: #0ea5e9 !important;
            background: white !important;
        }
        html.light .gradient-text-dark {
            background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%) !important;
            background-clip: text !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
        }
        html.light .trust-badge-dark {
            background: rgba(16, 185, 129, 0.1) !important;
            border: 1px solid rgba(16, 185, 129, 0.2) !important;
            color: #059669 !important;
        }
        html.light .icon-box-dark {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%) !important;
            border: 1px solid rgba(14, 165, 233, 0.2) !important;
        }
        html.light .plan-card-dark {
            background: white !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08) !important;
        }
        html.light .plan-card-dark:hover {
            border-color: rgba(14, 165, 233, 0.3) !important;
            box-shadow: 0 20px 40px rgba(14, 165, 233, 0.15) !important;
            transform: translateY(-8px);
        }
        html.light .plan-popular-dark {
            background: linear-gradient(145deg, rgba(14, 165, 233, 0.1) 0%, rgba(255, 255, 255, 0.95) 100%) !important;
            border: 2px solid #0ea5e9 !important;
        }
        html.light .toast-dark {
            background: white !important;
            border: 1px solid rgba(14, 165, 233, 0.3) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1) !important;
        }
        html.light .progress-bar-bg-dark {
            background: rgba(0, 0, 0, 0.1) !important;
        }
        html.light .particle-dark {
            background: rgba(14, 165, 233, 0.4) !important;
        }
        /* Hosting-landing page text colors in light mode */
        html.light .text-cyan-400 {
            color: #0ea5e9 !important;
        }
        html.light .text-emerald-400 {
            color: #059669 !important;
        }
        html.light .bg-cyan-500\/10,
        html.light .bg-cyan-500\/20 {
            background: rgba(14, 165, 233, 0.1) !important;
        }
        html.light .bg-emerald-500\/20 {
            background: rgba(5, 150, 105, 0.1) !important;
        }
        html.light .border-cyan-500\/30 {
            border-color: rgba(14, 165, 233, 0.2) !important;
        }

        /* =====================================================
           DOMAIN SEARCH PAGE OVERRIDES - LIGHT MODE
           ===================================================== */
        html.light .domain-search-wrapper {
            background: #f8fafc !important;
        }
        html.light .domain-search-wrapper::before {
            background-image:
                radial-gradient(circle at 20% 50%, rgba(14, 165, 233, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(139, 92, 246, 0.05) 0%, transparent 50%) !important;
        }
        /* Search Box & Titles */
        html.light .search-title {
            color: #0f172a !important;
            text-shadow: none !important;
        }
        html.light .search-subtitle {
            color: #64748b !important;
        }
        html.light .search-box-card {
            background: white !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08) !important;
        }
        html.light .search-input {
            background: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html.light .search-input::placeholder {
            color: #94a3b8 !important;
        }
        /* TLD Cards */
        html.light .tld-card {
            background: white !important;
            border-color: rgba(0, 0, 0, 0.06) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
        }
        html.light .tld-card:hover {
            background: #f8fafc !important;
            border-color: rgba(14, 165, 233, 0.3) !important;
        }
        html.light .tld-card .tld-name {
            color: #0f172a !important;
        }
        html.light .tld-card .tld-price {
            color: #64748b !important;
        }
        /* Result Cards (Domain Cards) */
        html.light .result-card {
            background: white !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06) !important;
        }
        html.light .result-card.taken {
            background: #f1f5f9 !important;
            border-color: rgba(0, 0, 0, 0.06) !important;
            opacity: 0.85 !important;
        }
        html.light .result-card h3 {
            color: #0f172a !important;
        }
        html.light .result-card .domain-info h3 {
            color: #0f172a !important;
        }
        html.light .result-card .domain-info h3.taken {
            color: #94a3b8 !important;
        }
        html.light .result-card .tld-badge {
            color: white !important;
        }
        html.light .result-card .tld-badge.available {
            background: #10b981 !important;
        }
        html.light .result-card .tld-badge.taken {
            background: #cbd5e1 !important;
            color: #64748b !important;
        }
        html.light .result-card .domain-price {
            color: #0ea5e9 !important;
            font-weight: 700 !important;
        }
        html.light .result-card .domain-price .original-price {
            color: #94a3b8 !important;
            text-decoration: line-through !important;
        }
        html.light .result-card .status-badge.available {
            color: #059669 !important;
            background: rgba(5, 150, 105, 0.1) !important;
        }
        html.light .result-card .status-badge.taken {
            color: #64748b !important;
            background: rgba(0, 0, 0, 0.05) !important;
        }
        html.light .result-card .great-alternative {
            color: #f59e0b !important;
            background: rgba(245, 158, 11, 0.1) !important;
        }
        html.light .result-card .check-whois-btn {
            color: #64748b !important;
            border-color: rgba(0, 0, 0, 0.1) !important;
        }
        html.light .result-card .check-whois-btn:hover {
            color: #0ea5e9 !important;
            border-color: #0ea5e9 !important;
        }
        /* Domain Cards (backup) */
        html.light .domain-card {
            background: white !important;
            border-color: rgba(0, 0, 0, 0.06) !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06) !important;
        }
        html.light .domain-card.available {
            background: white !important;
            border-color: rgba(5, 150, 105, 0.2) !important;
        }
        html.light .domain-card.unavailable {
            background: #f8fafc !important;
            border-color: rgba(0, 0, 0, 0.04) !important;
            opacity: 0.8 !important;
        }
        html.light .domain-card .domain-name {
            color: #0f172a !important;
        }
        html.light .domain-card .domain-price {
            color: #0ea5e9 !important;
        }
        html.light .domain-card .status-text {
            color: #64748b !important;
        }
        /* Category Tabs */
        html.light .category-tab {
            background: white !important;
            border-color: rgba(0, 0, 0, 0.06) !important;
            color: #64748b !important;
        }
        html.light .category-tab.active {
            background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%) !important;
            color: white !important;
            border-color: transparent !important;
        }
        /* Status Badges */
        html.light .status-badge.available {
            color: #059669 !important;
            background: rgba(5, 150, 105, 0.1) !important;
        }
        html.light .status-badge.unavailable {
            color: #dc2626 !important;
            background: rgba(220, 38, 38, 0.1) !important;
        }
        html.light .badge-premium {
            background: linear-gradient(135deg, #0ea5e9 0%, #8b5cf6 100%) !important;
            color: white !important;
        }
        /* Filter Tags */
        html.light .filter-tag {
            background: white !important;
            border-color: rgba(0, 0, 0, 0.06) !important;
            color: #475569 !important;
        }
        html.light .filter-tag.active {
            background: rgba(14, 165, 233, 0.1) !important;
            border-color: rgba(14, 165, 233, 0.3) !important;
            color: #0ea5e9 !important;
        }
        /* Empty State */
        html.light .empty-state {
            background: white !important;
            border-color: rgba(0, 0, 0, 0.06) !important;
        }
        html.light .empty-state h3 {
            color: #0f172a !important;
        }
        html.light .empty-state p {
            color: #64748b !important;
        }
        /* Popular TLDs Label */
        html.light .popular-tlds-label {
            color: #94a3b8 !important;
            letter-spacing: 0.1em !important;
        }
        /* Domain Table */
        html.light .domain-table thead {
            background: #f8fafc !important;
        }
        html.light .domain-table th {
            color: #475569 !important;
        }
        html.light .domain-table tbody tr {
            border-bottom-color: #f1f5f9 !important;
        }
        html.light .domain-table td {
            color: #334155 !important;
        }
        html.light .domain-table td.domain-name {
            color: #0f172a !important;
            font-weight: 600 !important;
        }
        html.light .domain-available {
            color: #059669 !important;
            background: rgba(5, 150, 105, 0.08) !important;
        }
        html.light .domain-unavailable {
            color: #dc2626 !important;
            background: rgba(220, 38, 38, 0.08) !important;
        }
        /* Button glow effects */
        html.light .btn-glow {
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.2) !important;
        }
        html.light .btn-glow:hover {
            box-shadow: 0 0 40px rgba(14, 165, 233, 0.3) !important;
        }

        /* =====================================================
           B HOSTING HEADER/FOOTER LIGHT MODE OVERRIDES
           ===================================================== */
        html.light .bhosting-nav {
            background: rgba(255,255,255,0.95) !important;
            border-bottom-color: rgba(0,0,0,0.08) !important;
        }
        html.light header.bg-\[\#0a0a1a\]\/95 {
            background: rgba(255,255,255,0.95) !important;
            border-bottom-color: rgba(0,0,0,0.08) !important;
        }
        html.light .bhosting-logo {
            color: #0f172a !important;
        }
        html.light .bhosting-logo span {
            color: #0ea5e9 !important;
        }
        /* Footer text uses Tailwind defaults */

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
        html.light .checkout-input {
            background: white !important;
            border-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html.light .checkout-divider {
            border-color: #e2e8f0 !important;
        }
        html.light .billing-cycle-card {
            background: white !important;
            border-color: #e2e8f0 !important;
        }
        html.light .billing-cycle-card:hover {
            border-color: #0ea5e9 !important;
        }
        html.light .billing-cycle-card.active {
            border-color: #0ea5e9 !important;
            background: rgba(14,165,233,0.05) !important;
        }
        html.light .price-display {
            color: #0f172a !important;
        }
        html.light .price-original {
            color: #94a3b8 !important;
        }
        html.light .price-total {
            color: #0ea5e9 !important;
        }
        html.light .order-summary-item {
            color: #334155 !important;
        }
        html.light .gst-text {
            color: #64748b !important;
        }
    </style>
    <style>
        /* ===== Scroll progress bar ===== */
        #bel-progress {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            z-index: 2147483645;
            background: linear-gradient(90deg, #00B7FF, #f59e0b);
            transform-origin: left;
            transform: scaleX(0);
            pointer-events: none;
        }

        /* ===== Starfield background ===== */
        #bel-stars {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        /* ===== Back to top ===== */
        #bel-top {
            position: fixed;
            bottom: 24px;
            left: 24px;
            z-index: 2147483645;
            width: 48px;
            height: 48px;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #00B7FF, #0369a1);
            color: #fff;
            font-size: 15px;
            box-shadow: 0 8px 24px rgba(0,183,255,.35);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(16px);
            transition: opacity .3s ease, transform .3s ease, visibility .3s, box-shadow .3s ease;
        }
        #bel-top:hover {
            transform: translateY(-3px) scale(1.06);
            box-shadow: 0 12px 32px rgba(0,183,255,.5);
        }
        #bel-top.bel-top-show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* ===== Custom scrollbar ===== */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #00B7FF, #f59e0b);
            border-radius: 8px;
            border: 2px solid #050505;
        }
        html { scrollbar-width: thin; scrollbar-color: #00B7FF #111; }
        html.light ::-webkit-scrollbar-thumb { border-color: #f8fafc; }
        html.light { scrollbar-color: #00B7FF #e2e8f0; }

        /* ===== 3D tilt cards ===== */
        .bel-tilt {
            transform-style: preserve-3d;
            will-change: transform;
        }
        .bel-tilt.bel-tilting {
            transition: transform .06s ease-out !important;
            box-shadow: 0 24px 60px rgba(0,183,255,.18), 0 6px 20px rgba(0,0,0,.1) !important;
        }

        /* ===== Announcement bar ===== */
        #bel-announce {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 42px;
            z-index: 2147483644;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 48px;
            background: linear-gradient(90deg, #0891b2, #00B7FF, #f59e0b, #00B7FF, #0891b2);
            background-size: 300% 100%;
            animation: bel-announce-flow 10s linear infinite;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }
        @keyframes bel-announce-flow { to { background-position: 300% 0; } }
        #bel-announce a { color: #fff; text-decoration: underline; }
        #bel-announce-close {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            color: #fff; opacity: .8; cursor: pointer; font-size: 13px;
        }
        #bel-announce-close:hover { opacity: 1; }
        body.bel-has-announce { padding-top: 42px; }
        body.bel-has-announce header,
        body.bel-has-announce .bel-site-header { top: 42px !important; }
        body.bel-has-announce #bel-progress { top: 42px; }

        /* ===== Social proof popup ===== */
        #bel-proof {
            position: fixed;
            left: 24px;
            bottom: 88px;
            z-index: 2147483644;
            max-width: 320px;
            background: rgba(17,17,17,.92);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0,183,255,.25);
            border-radius: 14px;
            padding: 12px 38px 12px 14px;
            color: #e5e7eb;
            box-shadow: 0 12px 40px rgba(0,0,0,.35);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(24px);
            opacity: 0;
            visibility: hidden;
            transition: all .45s cubic-bezier(.22,.61,.36,1);
        }
        html.light #bel-proof {
            background: rgba(255,255,255,.95);
            color: #0f172a;
            border-color: rgba(0,183,255,.35);
            box-shadow: 0 12px 40px rgba(0,0,0,.15);
        }
        #bel-proof.bel-proof-show { transform: translateY(0); opacity: 1; visibility: visible; }
        #bel-proof .bel-proof-icon {
            width: 38px; height: 38px; flex-shrink: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #00B7FF, #0891b2);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 14px;
        }
        #bel-proof-close {
            position: absolute; top: 6px; right: 8px;
            background: none; border: none;
            color: inherit; opacity: .5; cursor: pointer; font-size: 12px;
        }
        #bel-proof-close:hover { opacity: 1; }

        /* ===== Typing caret ===== */
        .bel-typed-caret {
            display: inline-block;
            width: 3px;
            height: 1.1em;
            margin-left: 3px;
            background: #f59e0b;
            vertical-align: -0.15em;
            animation: bel-caret-blink .8s step-end infinite;
        }
        @keyframes bel-caret-blink { 50% { opacity: 0; } }

        /* ===== Click ripple ===== */
        .bel-ripple {
            position: fixed;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(0,183,255,.35);
            border: 1px solid rgba(0,183,255,.5);
            transform: translate(-50%,-50%);
            pointer-events: none;
            z-index: 2147483643;
            animation: bel-ripple .7s ease-out forwards;
        }
        @keyframes bel-ripple {
            to { transform: translate(-50%,-50%) scale(6); opacity: 0; }
        }

        /* ===== Countdown chip in announcement bar ===== */
        #bel-countdown {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(0,0,0,.25);
            border-radius: 999px;
            padding: 2px 10px;
            font-size: 11px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        /* ===== Cookie consent ===== */
        #bel-consent {
            position: fixed;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2147483644;
            max-width: 540px;
            width: calc(100% - 32px);
            background: rgba(17,17,17,.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0,183,255,.25);
            border-radius: 16px;
            padding: 14px 18px;
            color: #e5e7eb;
            box-shadow: 0 12px 40px rgba(0,0,0,.4);
            display: none;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        html.light #bel-consent {
            background: rgba(255,255,255,.97);
            color: #0f172a;
            border-color: rgba(0,183,255,.35);
            box-shadow: 0 12px 40px rgba(0,0,0,.15);
        }
        #bel-consent p { flex: 1; font-size: 12.5px; line-height: 1.45; margin: 0; min-width: 180px; }
        #bel-consent button {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        #bel-consent-yes {
            background: linear-gradient(135deg, #00B7FF, #0891b2);
            color: #fff;
            border: none;
        }
        #bel-consent-no {
            background: transparent;
            border: 1px solid rgba(128,128,128,.4);
            color: inherit;
        }

        html { scroll-behavior: smooth; }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            #bel-top { transition: none !important; }
            #bel-announce { animation: none !important; }
            .bel-typed-caret { animation: none !important; }
        }
    </style>
    @stack('styles')

    <!-- Organization Schema -->
    @php
        $socialLinks = array_filter([
            $settings['facebook'] ?? '',
            $settings['twitter'] ?? '',
            $settings['instagram'] ?? '',
            $settings['linkedin'] ?? '',
            $settings['youtube'] ?? '',
            $settings['github'] ?? '',
        ]);
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings['site_name'] ?? 'Believoo',
            'url' => url('/'),
            'logo' => asset('favicon.ico'),
            'description' => $siteDesc,
        ];
        if (!empty($settings['company_legal_name'])) {
            $schema['legalName'] = $settings['company_legal_name'];
        }
        if (!empty($settings['company_cin'])) {
            $schema['identifier'] = [
                '@type' => 'PropertyValue',
                'propertyID' => 'CIN',
                'value' => $settings['company_cin'],
            ];
        }
        if (!empty($settings['company_incorporation_date'])) {
            $incDate = strtotime($settings['company_incorporation_date']);
            if ($incDate) {
                $schema['foundingDate'] = date('Y-m-d', $incDate);
            }
        }
        if (!empty($settings['company_registered_office'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['company_registered_office'],
                'addressCountry' => 'IN',
            ];
        }
        if (!empty($socialLinks)) {
            $schema['sameAs'] = array_values($socialLinks);
        }
    @endphp
    <script type="application/ld+json">
    {!! json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @php
        $localSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $settings['site_name'] ?? 'Believoo',
            'url' => url('/'),
            'logo' => asset('favicon.ico'),
            'description' => $siteDesc,
            'telephone' => $settings['contact_phone'] ?? '',
            'email' => $settings['contact_email'] ?? '',
            'priceRange' => '₹₹',
            'openingHours' => $settings['business_opening_hours'] ?? 'Mo-Su 00:00-23:59',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['address'] ?? '',
                'addressLocality' => $settings['business_city'] ?? '',
                'addressRegion' => $settings['business_state'] ?? '',
                'postalCode' => $settings['business_postal'] ?? '',
                'addressCountry' => $settings['business_country'] ?? 'IN',
            ],
        ];
        if (!empty($settings['business_latitude']) && !empty($settings['business_longitude'])) {
            $localSchema['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $settings['business_latitude'],
                'longitude' => (float) $settings['business_longitude'],
            ];
        }
        if (!empty($settings['contact_phone']) || !empty($settings['contact_email'])) {
            $localSchema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $settings['contact_phone'] ?? '',
                'email' => $settings['contact_email'] ?? '',
                'contactType' => 'Customer Support',
                'availableLanguage' => ['English', 'Hindi'],
            ];
        }
    @endphp
    <script type="application/ld+json">
    {!! json_encode($localSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>

    @if(!empty($faqSchema))
    <script type="application/ld+json">
    {!! json_encode($faqSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    @endif

    @php
        $breadcrumbItems = [['name' => 'Home', 'item' => url('/')]];
        if (request()->routeIs('about')) {
            $breadcrumbItems[] = ['name' => 'About Us', 'item' => url('/about')];
        } elseif (request()->routeIs('services.index')) {
            $breadcrumbItems[] = ['name' => 'Services', 'item' => url('/services')];
        } elseif (request()->routeIs('services.show')) {
            $breadcrumbItems[] = ['name' => 'Services', 'item' => url('/services')];
            $breadcrumbItems[] = ['name' => $pageTitle, 'item' => url()->current()];
        } elseif (request()->routeIs('services.streaming')) {
            $breadcrumbItems[] = ['name' => 'Services', 'item' => url('/services')];
            $breadcrumbItems[] = ['name' => 'Streaming', 'item' => url('/services/streaming')];
        } elseif (request()->routeIs('portfolio.index')) {
            $breadcrumbItems[] = ['name' => 'Portfolio', 'item' => url('/portfolio')];
        } elseif (request()->routeIs('portfolio.show')) {
            $breadcrumbItems[] = ['name' => 'Portfolio', 'item' => url('/portfolio')];
            $breadcrumbItems[] = ['name' => $pageTitle, 'item' => url()->current()];
        } elseif (request()->routeIs('vps-plans.index')) {
            $breadcrumbItems[] = ['name' => 'VPS Hosting', 'item' => url('/vps')];
        } elseif (request()->routeIs('vps-plans.category')) {
            $breadcrumbItems[] = ['name' => 'VPS Hosting', 'item' => url('/vps')];
            $breadcrumbItems[] = ['name' => $pageTitle, 'item' => url()->current()];
        } elseif (request()->routeIs('vps-plans.show')) {
            $breadcrumbItems[] = ['name' => 'VPS Hosting', 'item' => url('/vps')];
            $breadcrumbItems[] = ['name' => $pageTitle, 'item' => url()->current()];
        } elseif (request()->routeIs('contact')) {
            $breadcrumbItems[] = ['name' => 'Contact Us', 'item' => url('/contact')];
        } elseif (request()->routeIs('terms')) {
            $breadcrumbItems[] = ['name' => 'Terms of Service', 'item' => url('/terms')];
        } elseif (request()->routeIs('policy')) {
            $breadcrumbItems[] = ['name' => 'Privacy Policy', 'item' => url('/policy')];
        } elseif (request()->routeIs('home')) {
            // only home
        } else {
            $breadcrumbItems[] = ['name' => $pageTitle, 'item' => url()->current()];
        }

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn($item, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['item'],
            ], $breadcrumbItems, array_keys($breadcrumbItems)),
        ];
    @endphp
    <script type="application/ld+json">
    {!! json_encode($breadcrumbSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
</head>
<body class="font-sans antialiased selection:bg-amber-500 selection:text-dark transition-colors duration-300 bg-slate-50 text-slate-900 dark:bg-dark dark:text-gray-200">

    <!-- Scroll progress bar -->
    <div id="bel-progress" aria-hidden="true"></div>

    <!-- Starfield background (dark mode) -->
    <canvas id="bel-stars" aria-hidden="true"></canvas>

    <!-- Back to top -->
    <button id="bel-top" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    @php
        $services = \App\Models\Service::where('is_active', true)
            ->whereNotIn('category', ['ovh_dedicated', 'ovh_web_hosting', 'ovh_vps'])
            ->whereNotIn('slug', [
                'managed-vps-cloud',
                'vps-1', 'vps-2', 'vps-3', 'vps-4', 'vps-5', 'vps-6',
                'web-hosting-starter', 'web-hosting-business', 'web-hosting-pro',
                'streaming-addon',
                'app-development',
                'adsense-approval-service',
            ])
            ->get();
        $isHostingPage = request()->routeIs('hosting') || request()->routeIs('web-hosting') || request()->routeIs('vps-hosting') || request()->routeIs('vps-plans.*') || request()->routeIs('services.streaming') || request()->routeIs('streaming.*') || request()->is('services/streaming*') || request()->routeIs('client.domains.search') || request()->routeIs('domains.search') || request()->is('domains*');
        $isActive = fn(string $route) => request()->routeIs($route) ? 'text-amber-600' : 'text-slate-600 hover:text-amber-600';
        $hostingActive = fn(string $route) => request()->routeIs($route) ? 'text-cyan-400' : 'text-gray-300 hover:text-white';
        $isClientDashboard = request()->routeIs('client.dashboard');
    @endphp

    {{-- Announcement bar (Admin → Settings → General → Announcement Bar) --}}
    @php
        $announceText = trim($settings['announcement_text'] ?? '');
        $announceEnabled = ($settings['announcement_enabled'] ?? '1') !== '0';
        $announceEndTs = null;
        if (!empty($settings['announcement_end_at'] ?? '')) {
            try {
                $announceEnd = \Illuminate\Support\Carbon::parse($settings['announcement_end_at']);
                if ($announceEnd->isFuture()) $announceEndTs = $announceEnd->toIso8601String();
            } catch (\Throwable $e) {}
        }
    @endphp
    @if($announceText !== '' && $announceEnabled && !$isClientDashboard)
    <div id="bel-announce" role="banner">
        <i class="fas fa-bolt text-xs"></i>
        <span>
            @if(!empty($settings['announcement_url']))
                <a href="{{ $settings['announcement_url'] }}">{{ $announceText }}</a>
            @else
                {{ $announceText }}
            @endif
        </span>
        @if($announceEndTs)
        <span id="bel-countdown" data-end="{{ $announceEndTs }}"><i class="far fa-clock"></i><span class="bel-countdown-text"></span></span>
        @endif
        <button id="bel-announce-close" aria-label="Close announcement"><i class="fas fa-times"></i></button>
    </div>
    <script>
        (function () {
            var el = document.getElementById('bel-announce');
            function closeAnnounce() {
                if (el) el.remove();
                document.body.classList.remove('bel-has-announce');
            }
            if (sessionStorage.getItem('belAnnounceClosed')) { closeAnnounce(); return; }
            document.body.classList.add('bel-has-announce');
            document.getElementById('bel-announce-close').addEventListener('click', function () {
                sessionStorage.setItem('belAnnounceClosed', '1');
                closeAnnounce();
            });
        })();
    </script>
    @endif

    {{-- Social proof popups (real paid orders) --}}
    @if(!$isClientDashboard)
    @php
        $belOrders = \App\Models\Order::with('user')->where('status', 'paid')->whereNotNull('paid_at')
            ->latest('paid_at')->take(12)->get()
            ->map(function ($o) {
                return [
                    'name' => explode(' ', trim($o->user->name ?? 'A customer'))[0] ?: 'A customer',
                    'item' => trim(($o->service_name ?? 'a service') . ' ' . ($o->tier_name ?? '')),
                    'time' => $o->paid_at ? $o->paid_at->diffForHumans() : '',
                ];
            })->filter(fn($o) => $o['name'] !== '' && $o['item'] !== ''
                && !str_contains(strtolower($o['item']), 'stream'))->values(); // PAYU-REVIEW: hide streaming orders
    @endphp
    @if($belOrders->count() >= 2)
    <div id="bel-proof" aria-live="polite">
        <button id="bel-proof-close" aria-label="Dismiss"><i class="fas fa-times"></i></button>
        <div class="bel-proof-icon"><i class="fas fa-bag-shopping"></i></div>
        <div class="text-sm leading-snug">
            <span class="bel-proof-text font-semibold"></span><br>
            <span class="bel-proof-time text-xs opacity-60"></span>
        </div>
    </div>
    <script>window.belProofData = @json($belOrders);</script>
    @endif

    {{-- Cookie consent --}}
    <div id="bel-consent" role="dialog" aria-label="Cookie consent">
        <i class="fas fa-cookie-bite" style="color:#f59e0b; font-size:20px; flex-shrink:0;"></i>
        <p>
            We use cookies to improve your experience and analyze traffic.
            <a href="{{ route('policy') }}" style="color:#00b7ff; text-decoration:underline;">Privacy Policy</a>
        </p>
        <div style="display:flex; gap:8px; flex-shrink:0;">
            <button id="bel-consent-no" type="button">Decline</button>
            <button id="bel-consent-yes" type="button">Accept</button>
        </div>
    </div>
    @endif

    @if(!$isClientDashboard)
    @if($isHostingPage)
    <!-- B HOSTING HEADER -->
    <header class="fixed top-0 left-0 w-full z-[100000000] bg-[#0a0a1a]/95 backdrop-blur-xl border-b border-white/5">
        <style>
            .bhosting-nav { background: rgba(10, 10, 26, 0.95); border-bottom: 1px solid rgba(0, 212, 255, 0.1); }
            .bhosting-logo { font-family: 'Inter', sans-serif; }
            .bhosting-link { color: #a0a0b0; transition: all 0.3s ease; }
            .bhosting-link:hover, .bhosting-link.active { color: #00d4ff; }
            .bhosting-btn { background: linear-gradient(135deg, #00d4ff 0%, #0891b2 100%); color: #000; font-weight: 700; }
        </style>
        <nav class="w-full py-3">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <!-- B Hosting Logo -->
                    <div class="flex-shrink-0 flex items-center gap-3">
                        <a href="{{ route('home') }}" class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center">
                                <span class="text-black font-black text-xl">B</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-white font-black text-lg leading-none tracking-tight">B HOSTING</span>
                                <span class="text-cyan-400 text-[10px] font-bold uppercase tracking-wider">by Believoo</span>
                            </div>
                        </a>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="{{ route('home') }}" class="text-sm font-bold uppercase tracking-wider {{ $hostingActive('hosting') }}">Home</a>
                        <a href="{{ route('services.show', 'web-hosting-vps') }}" class="text-sm font-bold uppercase tracking-wider {{ $hostingActive('web-hosting') }}">Web Hosting</a>
                        <a href="{{ route('vps-plans.index') }}" class="text-sm font-bold uppercase tracking-wider {{ $hostingActive('vps-hosting') }}">VPS</a>
                        <a href="{{ route('client.domains.search') }}" class="text-sm font-bold uppercase tracking-wider text-gray-300 hover:text-cyan-400">Domains</a>
                        <a href="https://support.believoo.com" class="text-sm font-bold uppercase tracking-wider text-gray-300 hover:text-cyan-400">Support</a>
                        
                        <!-- Theme Toggle - All B Hosting Pages -->
                        <button onclick="window.toggleGlobalTheme()"
                                class="w-8 h-8 rounded-full flex items-center justify-center border transition-all bg-white/10 border-cyan-500/50 text-amber-600 hover:bg-white/20"
                                title="Toggle Theme">
                            <i class="fas theme-toggle-icon fa-sun text-amber-600 text-xs"></i>
                        </button>
                        
                        @auth
                            <a href="{{ route('client.dashboard') }}" class="px-5 py-2.5 rounded-lg bg-white/5 border border-white/10 text-white font-bold text-sm uppercase hover:bg-white/10 transition-all">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-bold uppercase tracking-wider text-gray-300 hover:text-cyan-400">Login</a>
                            <a href="{{ route('vps-plans.index') }}" class="px-5 py-2.5 rounded-lg bhosting-btn text-sm font-bold uppercase tracking-wide hover:shadow-lg hover:shadow-cyan-500/30 transition-all">
                                Get Started
                            </a>
                        @endauth
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <div class="md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="w-10 h-10 rounded-lg bg-white/5 flex items-center justify-center text-white">
                            <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" x-cloak class="md:hidden bg-[#0a0a1a] border-t border-white/5">
                <div class="px-4 py-4 space-y-2">
                    <a href="{{ route('home') }}" @click="mobileMenuOpen = false" class="block py-3 px-4 rounded-lg hover:bg-white/5 text-white font-bold">Home</a>
                    <a href="{{ route('services.show', 'web-hosting-vps') }}" @click="mobileMenuOpen = false" class="block py-3 px-4 rounded-lg hover:bg-white/5 text-gray-300">Web Hosting</a>
                    <a href="{{ route('vps-plans.index') }}" @click="mobileMenuOpen = false" class="block py-3 px-4 rounded-lg hover:bg-white/5 text-gray-300">VPS Servers</a>
                    <a href="{{ route('client.domains.search') }}" @click="mobileMenuOpen = false" class="block py-3 px-4 rounded-lg hover:bg-white/5 text-gray-300">Domains</a>
                    <a href="https://support.believoo.com" @click="mobileMenuOpen = false" class="block py-3 px-4 rounded-lg hover:bg-white/5 text-gray-300">Support</a>
                    <!-- Mobile Theme Toggle -->
                    <button onclick="window.toggleGlobalTheme()"
                            class="flex items-center justify-between py-3 px-4 rounded-lg hover:bg-white/5 w-full text-gray-300">
                        <span class="flex items-center gap-3">
                            <i class="fas theme-toggle-icon fa-sun text-amber-600"></i>
                            <span class="theme-toggle-label">Light</span>
                        </span>
                        <div class="w-10 h-6 rounded-full relative transition-colors bg-amber-500">
                            <div class="absolute top-1 w-4 h-4 rounded-full bg-white transition-all left-5"></div>
                        </div>
                    </button>
                    <div class="pt-2 border-t border-white/10">
                        @auth
                            <a href="{{ route('client.dashboard') }}" class="block py-3 px-4 rounded-lg bg-cyan-500 text-black font-bold text-center">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="block py-3 px-4 rounded-lg hover:bg-white/5 text-gray-300 text-center">Login</a>
                            <a href="{{ route('vps-plans.index') }}" class="block py-3 px-4 mt-2 rounded-lg bhosting-btn text-center font-bold">Get Started</a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </header>
    @else
    <!-- BELIEVOO MAIN HEADER -->
    <div class="bel-site-header sticky top-0 z-[100000000] bg-white shadow-sm">
        <div class="bg-slate-900 text-slate-300 text-xs py-2 border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center">
                <div class="flex items-center gap-4">
                    @if($settings['contact_phone'] ?? false)
                        <a href="tel:{{ $settings['contact_phone'] }}" class="hover:text-white transition-colors flex items-center gap-2">
                            <i class="fas fa-phone-alt"></i> {{ $settings['contact_phone'] }}
                        </a>
                    @endif
                    @if($settings['contact_email'] ?? false)
                        <a href="mailto:{{ $settings['contact_email'] }}" class="hover:text-white transition-colors flex items-center gap-2">
                            <i class="fas fa-envelope"></i> {{ $settings['contact_email'] }}
                        </a>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('client.dashboard') }}" class="hover:text-white transition-colors">My Portal</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-white transition-colors">Customer Login</a>
                    @endif
                </div>
            </div>
        </div>
        <nav x-data="{ mobileMenuOpen: false }"
             class="w-full bg-white border-b border-slate-200 py-3 pointer-events-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="relative z-[100000001]">
                            <x-site-logo :settings="$settings" class="h-14 w-auto" />
                        </a>
                    </div>
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-center space-x-10 relative z-[100000001]">
                            <a href="{{ route('home') }}"
                               class="relative text-sm font-medium transition-colors {{ $isActive('home') }}">
                                Home
                                @if(request()->routeIs('home'))
                                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-amber-500"></span>
                                @endif
                            </a>
                            {{-- PAYU-REVIEW: Bmydesk nav link hidden during payment-gateway review
                            <a href="https://bc.believoo.com" target="_blank"
                               class="relative text-sm font-bold text-brand hover:text-brandDark transition-colors">
                                Bmydesk
                            </a>
                            --}}
                            <div class="relative group" x-data="{ open: false }">
                                <button @click="open = !open" @mouseenter="open = true" class="hover:text-amber-600 transition-colors text-sm font-medium flex items-center gap-1 text-slate-600">
                                    Services <i class="fas fa-chevron-down text-[10px] transition-transform" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open" @mouseleave="open = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-2"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="absolute top-full -left-4 w-64 pt-4 z-[110]">
                                    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-xl">
                                        @foreach($services as $s)
                                            <a href="{{ route('services.show', $s->slug) }}" class="block px-4 py-3 rounded-xl hover:bg-slate-100 transition-colors text-sm font-medium text-slate-700">{{ $s->title }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('portfolio.index') }}"
                               class="relative text-sm font-medium transition-colors {{ $isActive('portfolio.*') }}">
                                Portfolio
                                @if(request()->routeIs('portfolio.*'))
                                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-amber-500"></span>
                                @endif
                            </a>
                            <a href="{{ route('blog.index') }}"
                               class="relative text-sm font-medium transition-colors {{ $isActive('blog.*') }}">
                                Blog
                                @if(request()->routeIs('blog.*'))
                                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-amber-500"></span>
                                @endif
                            </a>
                            <a href="{{ route('about') }}"
                               class="relative text-sm font-medium transition-colors {{ $isActive('about') }}">
                                About
                                @if(request()->routeIs('about'))
                                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-amber-500"></span>
                                @endif
                            </a>

                            <a href="{{ route('client.domains.search') }}"
                               class="relative text-sm font-medium transition-colors text-slate-600 hover:text-amber-600">
                                Domains
                            </a>
                            <a href="{{ route('contact') }}"
                               class="relative text-sm font-medium transition-colors {{ $isActive('contact') }}">
                                Contact
                                @if(request()->routeIs('contact'))
                                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-amber-500"></span>
                                @endif
                            </a>

                            <!-- Theme Toggle - Global -->
                            <button onclick="window.toggleGlobalTheme()"
                                    class="w-8 h-8 rounded-full flex items-center justify-center border transition-all bg-slate-100 border-slate-300 text-slate-600 hover:bg-slate-200"
                                    title="Toggle Theme">
                                <i class="fas theme-toggle-icon fa-sun text-slate-600 text-xs"></i>
                            </button>

                            @auth
                                <div class="flex items-center space-x-6">
                                    @livewire('navbar-notifications')
                                    <a href="{{ route('client.dashboard') }}"
                                       class="relative text-sm font-medium transition-colors {{ $isActive('client.dashboard') }}">
                                        Dashboard
                                        @if(request()->routeIs('client.dashboard'))
                                            <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1 h-1 rounded-full bg-amber-500"></span>
                                        @endif
                                    </a>
                                </div>
                            @else
                                <div class="flex items-center space-x-6">
                                    <a href="{{ route('login') }}" class="hover:text-amber-600 transition-colors text-sm font-medium text-slate-600">Login</a>
                                    @if(config('auth.registration_enabled', true))
                                        <a href="{{ route('register') }}" class="hover:text-amber-600 transition-colors text-sm font-medium text-slate-600">Register</a>
                                    @endif
                                </div>
                            @endauth

                            <a href="{{ route('home') }}#inquiry" class="px-8 py-3 rounded-full bg-amber-500 text-white font-bold hover:scale-105 transition-all text-sm shadow-[0_0_20px_rgba(245,158,11,0.4)] hover:shadow-[0_0_35px_rgba(245,158,11,0.6)]">Start Project</a>
                        </div>
                    </div>
                    <div class="md:hidden relative z-[101]">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Open menu"
                                class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-700 hover:bg-slate-100 transition-all">
                            <i class="fas" :class="mobileMenuOpen ? 'fa-times' : 'fa-bars'"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Full-Screen Overlay Menu -->
            <div x-show="mobileMenuOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[99999998] bg-white/98 backdrop-blur-2xl flex flex-col md:hidden"
                 x-cloak>
                <div class="flex justify-between items-center px-6 py-5 border-b border-slate-200">
                    <a href="{{ route('home') }}" @click="mobileMenuOpen = false">
                        <x-site-logo :settings="$settings" class="h-14 w-auto" />
                    </a>
                    <button @click="mobileMenuOpen = false" aria-label="Close menu"
                            class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center hover:bg-slate-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <nav class="flex-1 overflow-y-auto px-6 py-8 space-y-2">
                    <a href="{{ route('home') }}" @click="mobileMenuOpen = false"
                       class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px] {{ request()->routeIs('home') ? 'text-amber-600' : '' }}">
                        Home <i class="fas fa-arrow-right text-sm opacity-30"></i>
                    </a>
                    {{-- PAYU-REVIEW: Bmydesk nav link hidden during payment-gateway review
                    <a href="https://bc.believoo.com" @click="mobileMenuOpen = false"
                       class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px]">
                        Bmydesk <i class="fas fa-arrow-right text-sm opacity-30 text-brand"></i>
                    </a>
                    --}}
                    <div x-data="{ open: false }">
                        <button @click="open = !open"
                                class="w-full flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px]">
                            Services <i class="fas fa-chevron-down text-sm opacity-30 transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" class="pl-4 py-2 space-y-1">
                            @foreach($services as $s)
                                <a href="{{ route('services.show', $s->slug) }}" @click="mobileMenuOpen = false"
                                   class="flex items-center py-3 px-4 rounded-xl hover:bg-slate-100 transition-colors text-sm font-medium text-slate-600 hover:text-amber-600 min-h-[44px]">
                                    {{ $s->title }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <a href="{{ route('portfolio.index') }}" @click="mobileMenuOpen = false"
                       class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px] {{ request()->routeIs('portfolio.*') ? 'text-amber-600' : '' }}">
                        Portfolio <i class="fas fa-arrow-right text-sm opacity-30"></i>
                    </a>
                    <a href="{{ route('blog.index') }}" @click="mobileMenuOpen = false"
                       class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px] {{ request()->routeIs('blog.*') ? 'text-amber-600' : '' }}">
                        Blog <i class="fas fa-arrow-right text-sm opacity-30"></i>
                    </a>
                    <a href="{{ route('about') }}" @click="mobileMenuOpen = false"
                       class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px] {{ request()->routeIs('about') ? 'text-amber-600' : '' }}">
                        About <i class="fas fa-arrow-right text-sm opacity-30"></i>
                    </a>

                    <a href="{{ route('contact') }}" @click="mobileMenuOpen = false"
                       class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px] {{ request()->routeIs('contact') ? 'text-amber-600' : '' }}">
                        Contact <i class="fas fa-arrow-right text-sm opacity-30"></i>
                    </a>
                    @auth
                        <a href="{{ route('client.dashboard') }}" @click="mobileMenuOpen = false"
                           class="flex items-center justify-between py-4 px-4 rounded-2xl hover:bg-slate-100 transition-colors text-slate-700 text-lg font-black uppercase tracking-widest min-h-[56px] {{ request()->routeIs('client.dashboard') ? 'text-amber-600' : '' }}">
                            Dashboard <i class="fas fa-arrow-right text-sm opacity-30"></i>
                        </a>
                    @else
                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <a href="{{ route('login') }}" @click="mobileMenuOpen = false"
                               class="text-center py-4 rounded-2xl bg-slate-100 text-slate-700 text-sm font-black uppercase tracking-widest hover:bg-slate-200 min-h-[52px] flex items-center justify-center">Login</a>
                            @if(config('auth.registration_enabled', true))
                                <a href="{{ route('register') }}" @click="mobileMenuOpen = false"
                                   class="text-center py-4 rounded-2xl bg-slate-100 text-slate-700 text-sm font-black uppercase tracking-widest hover:bg-slate-200 min-h-[52px] flex items-center justify-center">Register</a>
                            @endif
                        </div>
                    @endauth
                </nav>
                <div class="px-6 pb-8">
                    <a href="{{ route('home') }}#inquiry" @click="mobileMenuOpen = false"
                       class="btn-primary w-full text-center block py-5 text-base">Start Project</a>
                </div>
            </div>
        </nav>
    </div>
    @endif
    @endif

    @if(!request()->routeIs('home') && !$isClientDashboard)
    @php
        $routeName = Route::currentRouteName() ?? '';
        $heroTitle = match(true) {
            request()->routeIs('contact') => 'Contact Us',
            request()->routeIs('about') => 'About Us',
            request()->routeIs('services.index') => 'Our Services',
            request()->routeIs('services.show') => $title ?? 'Service Details',
            request()->routeIs('portfolio.index') => 'Portfolio',
            request()->routeIs('portfolio.show') => $title ?? 'Project Details',
            request()->routeIs('blog.index') => 'Blog',
            request()->routeIs('blog.show') => $title ?? 'Blog Post',
            request()->routeIs('vps-plans.index') => 'VPS Hosting',
            request()->routeIs('vps-plans.category') => $title ?? 'VPS Category',
            request()->routeIs('vps-plans.show') => $title ?? 'VPS Plan',
            request()->routeIs('terms') => 'Terms of Service',
            request()->routeIs('policy') => 'Privacy Policy',
            default => ucwords(str_replace(['.', '-', '_'], ' ', $routeName)),
        };
    @endphp
    <!-- Page Hero -->
    <section class="relative py-24 md:py-32 bg-slate-900 overflow-hidden">
        <div class="absolute inset-0" style="background-image: radial-gradient(#334155 1px, transparent 1px); background-size: 24px 24px; opacity: 0.3;"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900 via-slate-900/90 to-slate-800/80"></div>
        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-3xl md:text-5xl font-bold text-white mb-4">{{ $heroTitle }}</p>
            <div class="text-slate-300 text-sm flex items-center gap-2">
                <a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-white">{{ $heroTitle }}</span>
            </div>
        </div>
    </section>
    @endif

    @if(request()->getHost() === 'support.believoo.com')
    <!-- Support Portal Top Bar -->
    <div class="bg-[#0a0a1a] border-b border-white/5 text-white text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="text-gray-400">Believoo ecosystem:</span>
                <a href="https://believoo.com" class="hover:text-[#00b7ff] transition-colors font-bold">Believoo</a>
                <a href="https://ghc.believoo.com" class="hover:text-[#00b7ff] transition-colors font-bold">GHC</a>
                <a href="https://bc.believoo.com" class="hover:text-[#00b7ff] transition-colors font-bold">Bmydesk</a>
                <a href="https://mail.believoo.com" class="hover:text-[#00b7ff] transition-colors font-bold">Webmail</a>
            </div>
            <div>
                <a href="https://believoo.com" class="text-gray-400 hover:text-white transition-colors">← Back to main site</a>
            </div>
        </div>
    </div>
    @endif

    <!-- Content -->
    <main class="relative z-[1]">
        {{ $slot ?? '' }}
        @hasSection('content')
            @yield('content')
        @endif
    </main>

    @if(!$isClientDashboard)
    <!-- Support Hub -->
    @livewire('support-hub')

    <!-- WhatsApp Chat -->
    {{-- WhatsApp floating button removed — now inside Support Hub widget --}}
    @endif

    @if(!$isClientDashboard)
    @if($isHostingPage)
    <!-- B HOSTING FOOTER -->
    <footer class="bg-[#0a0a1a] border-t border-white/5 pt-16 pb-8">
        <style>
            .bhosting-footer-link { color: #a0a0b0; transition: color 0.3s; }
            .bhosting-footer-link:hover { color: #00d4ff; }
        </style>
        <div class="max-w-7xl mx-auto px-4">
            <!-- Top Section -->
            <div class="grid md:grid-cols-5 gap-8 mb-12">
                <!-- Brand -->
                <div class="md:col-span-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center">
                            <span class="text-black font-black text-xl">B</span>
                        </div>
                        <div>
                            <span class="text-white font-black text-lg tracking-tight block">B HOSTING</span>
                            <span class="text-cyan-400 text-[10px] font-bold uppercase tracking-wider">Enterprise Cloud Infrastructure</span>
                        </div>
                    </a>
                    <p class="text-gray-400 text-sm mb-6 max-w-sm">
                        Premium hosting solutions powered by GHC Cloud. 99.9% uptime guarantee with 24/7 expert support.
                    </p>
                    <div class="flex gap-3">
                        @foreach(['twitter' => 'fab fa-twitter', 'linkedin' => 'fab fa-linkedin-in', 'github' => 'fab fa-github', 'instagram' => 'fab fa-instagram'] as $social => $icon)
                            <a href="#" class="w-9 h-9 rounded-lg bg-white/5 flex items-center justify-center text-gray-400 hover:bg-cyan-500 hover:text-black transition-all">
                                <i class="{{ $icon }}"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
                
                <!-- Products -->
                <div>
                    <h4 class="text-white font-bold uppercase text-sm tracking-wider mb-4">Products</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('services.show', 'web-hosting-vps') }}" class="bhosting-footer-link text-sm">Web Hosting</a></li>
                        <li><a href="{{ route('vps-plans.index') }}" class="bhosting-footer-link text-sm">VPS Servers</a></li>
                        <li><a href="{{ route('vps-plans.index') }}" class="bhosting-footer-link text-sm">Dedicated Servers</a></li>
                        <li><a href="{{ route('client.domains.search') }}" class="bhosting-footer-link text-sm">Domain Registration</a></li>
                    </ul>
                </div>
                
                <!-- Company -->
                <div>
                    <h4 class="text-white font-bold uppercase text-sm tracking-wider mb-4">Company</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('about') }}" class="bhosting-footer-link text-sm">About Us</a></li>
                        <li><a href="{{ route('blog.index') }}" class="bhosting-footer-link text-sm">Blog</a></li>
                        <li><a href="{{ route('home') }}" class="bhosting-footer-link text-sm">Believoo Systems</a></li>
                        <li><a href="{{ route('contact') }}" class="bhosting-footer-link text-sm">Contact</a></li>
                        <li><a href="{{ route('client.dashboard') }}" class="bhosting-footer-link text-sm">Client Portal</a></li>
                    </ul>
                </div>
                
                <!-- Support -->
                <div>
                    <h4 class="text-white font-bold uppercase text-sm tracking-wider mb-4">Support</h4>
                    <ul class="space-y-3">
                        <li><a href="https://support.believoo.com/help" class="bhosting-footer-link text-sm">Help Center</a></li>
                        <li><a href="https://support.believoo.com/contact" class="bhosting-footer-link text-sm">24/7 Live Chat</a></li>
                        <li><a href="https://support.believoo.com/tickets" class="bhosting-footer-link text-sm">Submit Ticket</a></li>
                        <li><a href="https://support.believoo.com/status" class="bhosting-footer-link text-sm">Server Status</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- Trust Badges -->
            <div class="flex flex-wrap justify-center gap-4 mb-8 py-6 border-y border-white/5">
                @foreach(['99.9% Uptime SLA', 'DDoS Protection', 'NVMe SSD', 'Free SSL', '24/7 Support', '30-Day Money Back'] as $badge)
                    <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10">
                        <i class="fas fa-check-circle text-cyan-400 text-sm"></i>
                        <span class="text-gray-400 text-xs font-medium">{{ $badge }}</span>
                    </div>
                @endforeach
                <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 border border-white/10">
                    <i class="fas fa-building text-cyan-400 text-sm"></i>
                    <span class="text-gray-400 text-xs font-medium">MCA Registered Company</span>
                </div>
            </div>

            <!-- Bottom -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-gray-500 text-sm text-center md:text-left">
                    © {{ date('Y') }} <span class="text-cyan-400 font-semibold">B Hosting</span> by {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }}. All rights reserved.
                    @if($settings['company_cin'] ?? false)
                        <div class="text-gray-600 text-xs mt-1">CIN: {{ $settings['company_cin'] }} · Incorporated under the Companies Act, 2013, Govt. of India</div>
                    @endif
                </div>
                <div class="flex gap-6">
                    <a href="{{ route('policy') }}" class="text-gray-500 hover:text-cyan-400 text-sm transition-colors">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="text-gray-500 hover:text-cyan-400 text-sm transition-colors">Terms of Service</a>
                    <a href="{{ route('refund') }}" class="text-gray-500 hover:text-cyan-400 text-sm transition-colors">Refund Policy</a>
                    <a href="#" class="text-gray-500 hover:text-cyan-400 text-sm transition-colors">SLA</a>
                </div>
            </div>
        </div>
    </footer>
    @else
    <!-- BELIEVOO MAIN FOOTER -->
    <footer class="bg-slate-900 text-slate-300 pt-20 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8 pb-12 border-b border-slate-800">
                <div>
                    <h3 class="text-2xl font-bold text-white mb-2">Sign up now and stay updated!</h3>
                    <p class="text-slate-400 text-sm">Get the latest news and updates from Believoo.</p>
                </div>
                <div class="w-full lg:w-auto">
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="flex w-full gap-2">
                        @csrf
                        <div class="relative flex-1 lg:w-80">
                            <i class="fas fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="email" name="email" required placeholder="Email Address" class="w-full pl-10 pr-4 py-3 rounded-full bg-slate-800 border border-slate-700 text-white placeholder-slate-400 focus:outline-none focus:border-amber-500 text-sm">
                        </div>
                        <button type="submit" class="px-8 py-3 rounded-full bg-amber-500 text-white font-semibold hover:bg-amber-600 transition text-sm whitespace-nowrap">Sign Up</button>
                    </form>
                    @if(session('newsletter_success'))
                        <p class="mt-3 text-sm text-emerald-400"><i class="fas fa-check-circle"></i> {{ session('newsletter_success') }}</p>
                    @endif
                    @error('email')
                        <p class="mt-3 text-sm text-red-400"><i class="fas fa-exclamation-circle"></i> {{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 py-16">
                <div>
                    <h4 class="font-bold text-white mb-6 text-sm uppercase tracking-wider">Contact Us</h4>
                    <ul class="space-y-4 text-sm">
                        <li class="flex items-center gap-3"><i class="fas fa-phone-alt text-amber-500 w-4"></i> {{ $settings['contact_phone'] ?? '+1 (555) 000-0000' }}</li>
                        <li class="flex items-center gap-3"><i class="fas fa-envelope text-amber-500 w-4"></i> {{ $settings['contact_email'] ?? 'hello@believoo.com' }}</li>
                        <li class="flex items-start gap-3"><i class="fas fa-map-marker-alt text-amber-500 w-4 mt-1"></i> <span>{{ $settings['address'] ?? 'London, UK' }}</span></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-6 text-sm uppercase tracking-wider">Company Links</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('about') }}" class="hover:text-amber-500 transition-colors">About</a></li>
                        <li><a href="{{ route('blog.index') }}" class="hover:text-amber-500 transition-colors">Blog</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-amber-500 transition-colors">Contact</a></li>
                        <li><a href="https://support.believoo.com" class="hover:text-amber-500 transition-colors">Support</a></li>
                        <li><a href="{{ route('portfolio.index') }}" class="hover:text-amber-500 transition-colors">Latest Work</a></li>
                        <li><a href="{{ route('home') }}#services" class="hover:text-amber-500 transition-colors">Services</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-6 text-sm uppercase tracking-wider">Services</h4>
                    <ul class="space-y-3 text-sm">
                        @foreach($services->take(5) as $s)
                            <li><a href="{{ route('services.show', $s->slug) }}" class="hover:text-amber-500 transition-colors">{{ $s->title }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-white mb-6 text-sm uppercase tracking-wider">Quick Links</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="{{ route('services.index') }}" class="hover:text-amber-500 transition-colors">Browse Services</a></li>
                        <li><a href="{{ route('client.dashboard') }}" class="hover:text-amber-500 transition-colors">My Portal</a></li>
                        {{-- PAYU-REVIEW: external brand links hidden during payment-gateway review
                        <li><a href="https://bc.believoo.com" class="hover:text-amber-500 transition-colors">Bmydesk Workspace</a></li>
                        <li><a href="https://ghc.believoo.com" class="hover:text-amber-500 transition-colors">GHC Cloud Hosting</a></li>
                        --}}
                        <li><a href="{{ route('login') }}" class="hover:text-amber-500 transition-colors">Account Login</a></li>
                        <li><a href="{{ route('home') }}#inquiry" class="hover:text-amber-500 transition-colors">Start Project</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-12 pb-4">
                <h4 class="font-bold text-white mb-6 text-sm uppercase tracking-wider">All Products</h4>
                @php
                    // PAYU-REVIEW: external product links removed during payment-gateway review — restore after approval
                    $allProducts = [
                        ['Web Hosting', route('services.show', 'web-hosting-vps')],
                        ['VPS Servers', route('vps-plans.index')],
                        ['Website Builder', route('services.show', 'web-application-development')],
                        ['Domains', route('client.domains.search')],
                        ['Email Marketing', route('services.show', 'seo-digital-growth')],
                        ['Reputation Management', route('services.show', 'seo-digital-growth')],
                        ['SEO', route('services.show', 'seo-digital-growth')],
                        ['Business Website', route('services.show', 'web-application-development')],
                    ];
                @endphp
                <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-x-8 gap-y-3 text-sm text-slate-400">
                    @foreach($allProducts as [$label, $url])
                        <li><a href="{{ $url }}" class="hover:text-amber-500 transition-colors">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>

            @if(($settings['company_cin'] ?? false) || ($settings['company_legal_name'] ?? false))
            <div class="border-t border-slate-800 pt-6 pb-2 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                <div class="flex items-center gap-2">
                    <i class="fas fa-certificate text-amber-500"></i>
                    <span>{{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }} is incorporated under the Companies Act, 2013, Ministry of Corporate Affairs, Govt. of India.</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 justify-center">
                    @if($settings['company_cin'] ?? false)<span>CIN: {{ $settings['company_cin'] }}</span>@endif
                    @if($settings['company_pan'] ?? false)<span>PAN: {{ $settings['company_pan'] }}</span>@endif
                </div>
            </div>
            @endif

            <div class="border-t border-slate-800 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex items-center">
                    <x-site-logo :settings="$settings" class="h-10 w-auto" mode="dark" />
                </a>
                <div class="text-slate-500 text-xs text-center">
                    &copy; {{ date('Y') }} {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }}. All rights reserved.
                    <div class="text-slate-600 text-[11px] mt-1">A brand of {{ $settings['company_legal_name'] ?? 'Believoo Private Limited' }}.</div>
                    <div class="flex gap-4 justify-center mt-2 text-[11px]">
                        <a href="{{ route('policy') }}" class="hover:text-amber-500 transition-colors">Privacy Policy</a>
                        <a href="{{ route('terms') }}" class="hover:text-amber-500 transition-colors">Terms of Service</a>
                        <a href="{{ route('refund') }}" class="hover:text-amber-500 transition-colors">Refund Policy</a>
                    </div>
                </div>
                <div class="flex gap-4">
                    @php
                        $socialLinks = [
                            'twitter' => ['icon' => 'fab fa-twitter', 'url' => $settings['twitter'] ?? '#'],
                            'linkedin-in' => ['icon' => 'fab fa-linkedin-in', 'url' => $settings['linkedin'] ?? '#'],
                            'github' => ['icon' => 'fab fa-github', 'url' => $settings['github'] ?? '#'],
                            'instagram' => ['icon' => 'fab fa-instagram', 'url' => $settings['instagram'] ?? '#'],
                        ];
                    @endphp
                    @foreach($socialLinks as $key => $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-full border border-slate-700 flex items-center justify-center text-slate-400 hover:bg-amber-500 hover:text-white hover:border-amber-500 transition-all text-sm">
                            <i class="{{ $social['icon'] }}"></i>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
    @endif
    @endif

    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script src="/vendor/livewire/livewire.js" data-csrf="{{ csrf_token() }}" data-update-uri="/livewire/update" data-navigate-once="true"></script>
    {{-- Using published asset because /livewire/livewire.js returns 404 on this server --}}
    @livewire('notifications')
    <div x-data="{ 
            audio: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'),
            playNotification() {
                this.audio.play().catch(e => console.log('Audio play blocked'));
            }
        }" @notification-received.window="playNotification()" @play-notification-sound.window="playNotification()" @play-ping-sound.window="playNotification()"></div>

    <script>
        // ===== Scroll progress + back to top =====
        (function () {
            var bar = document.getElementById('bel-progress');
            var top = document.getElementById('bel-top');
            if (top) {
                top.addEventListener('click', function () {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
            if (!bar && !top) return;
            function onScroll() {
                var h = document.documentElement;
                var max = h.scrollHeight - h.clientHeight;
                if (bar) bar.style.transform = 'scaleX(' + (max > 0 ? h.scrollTop / max : 0) + ')';
                if (top) top.classList.toggle('bel-top-show', h.scrollTop > 500);
            }
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
        })();

        // ===== Starfield (dark mode only) =====
        (function () {
            var canvas = document.getElementById('bel-stars');
            if (!canvas) return;
            var ctx = canvas.getContext('2d');
            var stars = [];
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            function resize() {
                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;
                stars = [];
                var count = Math.min(140, Math.floor(canvas.width * canvas.height / 12000));
                for (var i = 0; i < count; i++) {
                    stars.push({
                        x: Math.random() * canvas.width,
                        y: Math.random() * canvas.height,
                        r: Math.random() * 1.4 + 0.4,
                        a: Math.random() * Math.PI * 2,
                        s: Math.random() * 0.02 + 0.005
                    });
                }
            }
            function draw() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (!document.documentElement.classList.contains('light')) {
                    for (var i = 0; i < stars.length; i++) {
                        var st = stars[i];
                        st.a += st.s;
                        ctx.globalAlpha = 0.25 + Math.abs(Math.sin(st.a)) * 0.55;
                        ctx.fillStyle = '#9fdcff';
                        ctx.beginPath();
                        ctx.arc(st.x, st.y, st.r, 0, Math.PI * 2);
                        ctx.fill();
                    }
                }
                ctx.globalAlpha = 1;
                if (!reduce) requestAnimationFrame(draw);
            }
            resize();
            window.addEventListener('resize', resize);
            draw();
        })();

        // ===== Animated counters =====
        (function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            if (!('IntersectionObserver' in window)) return;
            var els = document.querySelectorAll('[data-bel-count]');
            if (!els.length) return;
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (en) {
                    if (!en.isIntersecting) return;
                    io.unobserve(en.target);
                    var el = en.target;
                    var target = parseFloat(el.getAttribute('data-bel-count')) || 0;
                    var dec = parseInt(el.getAttribute('data-bel-decimals') || '0', 10);
                    var prefix = el.getAttribute('data-bel-prefix') || '';
                    var suffix = el.getAttribute('data-bel-suffix') || '';
                    var dur = 1400, t0 = null;
                    function step(ts) {
                        if (!t0) t0 = ts;
                        var p = Math.min(1, (ts - t0) / dur);
                        var e = 1 - Math.pow(1 - p, 3);
                        el.textContent = prefix + (target * e).toFixed(dec) + suffix;
                        if (p < 1) requestAnimationFrame(step);
                    }
                    requestAnimationFrame(step);
                });
            }, { threshold: 0.5 });
            els.forEach(function (el) { io.observe(el); });
        })();

        // ===== 3D tilt on cards =====
        (function () {
            if (!window.matchMedia('(pointer: fine)').matches) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            document.querySelectorAll('.bel-tilt').forEach(function (card) {
                card.addEventListener('mousemove', function (e) {
                    var r = card.getBoundingClientRect();
                    var x = (e.clientX - r.left) / r.width - 0.5;
                    var y = (e.clientY - r.top) / r.height - 0.5;
                    card.classList.add('bel-tilting');
                    card.style.transform = 'perspective(800px) rotateX(' + (-y * 8).toFixed(2) + 'deg) rotateY(' + (x * 8).toFixed(2) + 'deg) translateY(-4px)';
                });
                card.addEventListener('mouseleave', function () {
                    card.classList.remove('bel-tilting');
                    card.style.transform = '';
                });
            });
        })();

        // ===== Magnetic buttons =====
        (function () {
            if (!window.matchMedia('(pointer: fine)').matches) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var els = Array.prototype.slice.call(document.querySelectorAll('.bel-magnetic'));
            if (!els.length) return;
            var state = els.map(function () { return { x: 0, y: 0, tx: 0, ty: 0 }; });
            document.addEventListener('mousemove', function (e) {
                els.forEach(function (el, i) {
                    var r = el.getBoundingClientRect();
                    var dx = e.clientX - (r.left + r.width / 2);
                    var dy = e.clientY - (r.top + r.height / 2);
                    var range = 100 + Math.max(r.width, r.height) / 2;
                    var d = Math.sqrt(dx * dx + dy * dy);
                    if (d < range) {
                        var f = (1 - d / range) * 0.35;
                        state[i].tx = dx * f; state[i].ty = dy * f;
                    } else {
                        state[i].tx = 0; state[i].ty = 0;
                    }
                });
            }, { passive: true });
            (function loop() {
                els.forEach(function (el, i) {
                    var s = state[i];
                    s.x += (s.tx - s.x) * 0.18;
                    s.y += (s.ty - s.y) * 0.18;
                    if (Math.abs(s.x) > 0.05 || Math.abs(s.y) > 0.05) {
                        el.style.transform = 'translate(' + s.x.toFixed(1) + 'px,' + s.y.toFixed(1) + 'px)';
                    } else if (el.style.transform) {
                        el.style.transform = '';
                    }
                });
                requestAnimationFrame(loop);
            })();
        })();

        // ===== Social proof popups =====
        (function () {
            var box = document.getElementById('bel-proof');
            var data = window.belProofData;
            if (!box || !data || !data.length) return;
            var idx = 0, stopped = false;
            var verbs = ['purchased', 'ordered', 'activated', 'subscribed to'];
            var textEl = box.querySelector('.bel-proof-text');
            var timeEl = box.querySelector('.bel-proof-time');
            document.getElementById('bel-proof-close').addEventListener('click', function () {
                stopped = true;
                box.classList.remove('bel-proof-show');
            });
            function show() {
                if (stopped) return;
                var o = data[idx % data.length];
                idx++;
                textEl.textContent = o.name + ' ' + verbs[idx % verbs.length] + ' ' + o.item;
                timeEl.textContent = (o.time ? o.time + ' · ' : '') + 'verified order';
                box.classList.add('bel-proof-show');
                setTimeout(function () { box.classList.remove('bel-proof-show'); }, 5500);
                setTimeout(show, 24000);
            }
            setTimeout(show, 6000);
        })();

        // ===== Typewriter =====
        (function () {
            var el = document.querySelector('[data-bel-typing]');
            if (!el) return;
            var words = (el.getAttribute('data-bel-words') || '').split('|').filter(Boolean);
            if (!words.length) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                el.textContent = words[0];
                return;
            }
            var wi = 0, ci = 0, deleting = false;
            function tick() {
                var w = words[wi];
                ci += deleting ? -1 : 1;
                el.textContent = w.substring(0, ci);
                var delay = deleting ? 35 : 75;
                if (!deleting && ci === w.length) { delay = 1800; deleting = true; }
                else if (deleting && ci === 0) { deleting = false; wi = (wi + 1) % words.length; delay = 400; }
                setTimeout(tick, delay);
            }
            tick();
        })();

        // ===== Announcement countdown =====
        (function () {
            var chip = document.getElementById('bel-countdown');
            if (!chip) return;
            var end = new Date(chip.getAttribute('data-end')).getTime();
            var textEl = chip.querySelector('.bel-countdown-text');
            function pad(n) { return (n < 10 ? '0' : '') + n; }
            function upd() {
                var diff = end - Date.now();
                if (diff <= 0) {
                    var bar = document.getElementById('bel-announce');
                    if (bar) bar.remove();
                    document.body.classList.remove('bel-has-announce');
                    return;
                }
                var s = Math.floor(diff / 1000);
                var d = Math.floor(s / 86400); s -= d * 86400;
                var h = Math.floor(s / 3600); s -= h * 3600;
                var m = Math.floor(s / 60); s -= m * 60;
                textEl.textContent = (d > 0 ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s) + ' left';
            }
            upd();
            setInterval(upd, 1000);
        })();

        // ===== Click ripple =====
        (function () {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            document.addEventListener('click', function (e) {
                var r = document.createElement('span');
                r.className = 'bel-ripple';
                r.style.left = e.clientX + 'px';
                r.style.top = e.clientY + 'px';
                document.body.appendChild(r);
                setTimeout(function () { r.remove(); }, 750);
            });
        })();

        // ===== Cookie consent =====
        (function () {
            var el = document.getElementById('bel-consent');
            if (!el) return;
            if (!localStorage.getItem('belConsent')) el.style.display = 'flex';
            document.getElementById('bel-consent-yes').addEventListener('click', function () {
                localStorage.setItem('belConsent', 'accepted');
                el.remove();
            });
            document.getElementById('bel-consent-no').addEventListener('click', function () {
                localStorage.setItem('belConsent', 'declined');
                el.remove();
            });
        })();
    </script>

    <!-- PWA service worker registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/sw.js')
                    .then(function (registration) {
                        console.log('SW registered:', registration.scope);
                    })
                    .catch(function (error) {
                        console.log('SW registration failed:', error);
                    });
            });
        }
    </script>
</body>
</html>
