<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Clytrix') }} - Web Hosting, Domains & Cloud Services</title>
    <meta name="description" content="Premium web hosting, domain registration, and cloud solutions. Expert support, 99.9% uptime, and free migration.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        /* Light Theme Variables */
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --secondary: #7c3aed;
            --accent: #06b6d4;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #0f172a;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
        }

        body {
            background: #ffffff;
            color: var(--gray-800);
        }

        /* Gradient Text */
        .text-gradient {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .text-gradient-green {
            background: linear-gradient(135deg, #10b981 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glass Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Card Hover Effect */
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Gradient Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            border-color: var(--gray-300);
        }

        /* Marquee Animation */
        @keyframes marquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        .animate-marquee {
            animation: marquee 30s linear infinite;
        }

        /* Navigation Dropdown */
        .nav-dropdown {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease;
        }

        .nav-item:hover .nav-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Hero Background */
        .hero-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #312e81 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%236366f1' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        /* TLD Card */
        .tld-card {
            background: white;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .tld-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.15);
        }

        /* FAQ Accordion */
        .faq-item {
            border-bottom: 1px solid var(--gray-200);
        }

        .faq-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-content {
            max-height: 500px;
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }

        /* Cookie Banner */
        .cookie-banner {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
        }

        /* Section Backgrounds */
        .section-light {
            background: #ffffff;
        }

        .section-gray {
            background: var(--gray-50);
        }

        .section-dark {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
        }

        /* Testimonial Card */
        .testimonial-card {
            background: white;
            border: 1px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .testimonial-card:hover {
            border-color: var(--primary);
            box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.2);
        }

        .testimonial-card.featured {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
        }

        /* Hosting Plan Card */
        .plan-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 1rem;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .plan-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }

        .plan-card.popular {
            border: 2px solid var(--primary);
            position: relative;
        }

        .plan-card.popular::before {
            content: 'Most Popular';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 4px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* Feature Icon Box */
        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        /* Search Input */
        .domain-search {
            background: white;
            border: 2px solid var(--gray-200);
            transition: all 0.3s ease;
        }

        .domain-search:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        /* Stats Counter */
        .stat-number {
            font-weight: 700;
            color: var(--primary);
        }

        /* Floating Animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .animate-float {
            animation: float 3s ease-in-out infinite;
        }

        /* Glow Effect */
        .glow {
            box-shadow: 0 0 40px rgba(79, 70, 229, 0.3);
        }
    </style>
</head>
<body class="antialiased" x-data="{ currency: localStorage.getItem('currency') || 'INR', cookieAccepted: localStorage.getItem('cookieAccepted') }" x-init="$watch('currency', val => localStorage.setItem('currency', val))">

    {{-- Navigation --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center">
                        <i class="fas fa-rocket text-white text-lg"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-900 tracking-tight">CLYTRIX</span>
                </a>

                {{-- Nav Links --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="#" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">Home</a>
                    
                    <div class="nav-item relative">
                        <button class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">
                            Domain <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="nav-dropdown absolute top-full left-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-200 p-2">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Register Domain</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Transfer Domain</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Domain Pricing</a>
                        </div>
                    </div>

                    <div class="nav-item relative">
                        <button class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">
                            Hosting <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="nav-dropdown absolute top-full left-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-200 p-2">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Shared Hosting</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Cloud Hosting</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">VPS Hosting</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Dedicated Server</a>
                        </div>
                    </div>

                    <div class="nav-item relative">
                        <button class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">
                            Services <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="nav-dropdown absolute top-full left-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-200 p-2">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">SSL Certificates</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Website Builder</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Email Hosting</a>
                        </div>
                    </div>

                    <div class="nav-item relative">
                        <button class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">
                            Company <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="nav-dropdown absolute top-full left-0 mt-1 w-48 bg-white rounded-xl shadow-xl border border-gray-200 p-2">
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">About Us</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Contact</a>
                            <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition-colors">Blog</a>
                        </div>
                    </div>

                    <a href="#" class="flex items-center gap-1 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-colors">
                        Offers <span class="px-2 py-0.5 bg-red-500 text-white text-xs rounded-full">Hot</span>
                    </a>
                </div>

                {{-- Right Side --}}
                <div class="flex items-center gap-3">
                    {{-- Currency Switcher --}}
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            <i class="fas" :class="currency === 'INR' ? 'fa-rupee-sign' : 'fa-dollar-sign'"></i>
                            <span x-text="currency"></span>
                            <i class="fas fa-chevron-down text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="open" @click.outside="open = false" x-transition class="absolute top-full right-0 mt-1 w-32 bg-white rounded-xl shadow-xl border border-gray-200 p-1 z-50">
                            <button @click="currency = 'INR'; open = false" class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors flex items-center gap-2" :class="currency === 'INR' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'">
                                <i class="fas fa-rupee-sign"></i> INR
                            </button>
                            <button @click="currency = 'USD'; open = false" class="w-full text-left px-3 py-2 text-sm rounded-lg transition-colors flex items-center gap-2" :class="currency === 'USD' ? 'bg-indigo-50 text-indigo-600' : 'text-gray-600 hover:bg-gray-50'">
                                <i class="fas fa-dollar-sign"></i> USD
                            </button>
                        </div>
                    </div>
                    <a href="{{ route('login') }}" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg text-sm font-semibold text-white hover:opacity-90 transition-opacity">
                        Client Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Main Content --}}
    <main class="pt-16">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8">
                {{-- Brand --}}
                <div class="col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                            <i class="fas fa-rocket text-white text-sm"></i>
                        </div>
                        <span class="text-xl font-bold">CLYTRIX</span>
                    </div>
                    <p class="text-sm text-gray-400 mb-4">Premium web hosting and domain services for businesses worldwide. 99.99% Uptime Guarantee.</p>
                    <div class="flex gap-3">
                        <a href="#" class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="fab fa-twitter text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="fab fa-instagram text-sm"></i>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-lg bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition-colors">
                            <i class="fab fa-linkedin-in text-sm"></i>
                        </a>
                    </div>
                </div>

                {{-- Hosting --}}
                <div>
                    <h4 class="font-semibold mb-4 text-white">Hosting</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Shared Hosting</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Cloud Hosting</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">VPS Hosting</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Dedicated Server</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">WordPress Hosting</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Eco Hosting</a></li>
                    </ul>
                </div>

                {{-- Domains --}}
                <div>
                    <h4 class="font-semibold mb-4 text-white">Domains</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Register Domain</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Transfer Domain</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Domain Pricing</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Whois Lookup</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Domain Reseller</a></li>
                    </ul>
                </div>

                {{-- Company --}}
                <div>
                    <h4 class="font-semibold mb-4 text-white">Company</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">About Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Careers</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>

                {{-- Support --}}
                <div>
                    <h4 class="font-semibold mb-4 text-white">Support</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Live Chat</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Submit Ticket</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Status Page</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Knowledge Base</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-gray-500">&copy; 2025 Clytrix. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <i class="fas fa-shield-alt text-emerald-400"></i>
                        <span>Secure Payments</span>
                    </div>
                    <div class="flex gap-2">
                        <i class="fab fa-cc-visa text-2xl text-gray-400"></i>
                        <i class="fab fa-cc-mastercard text-2xl text-gray-400"></i>
                        <i class="fab fa-cc-paypal text-2xl text-gray-400"></i>
                        <i class="fab fa-cc-amex text-2xl text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    {{-- Cookie Banner --}}
    <div x-show="!cookieAccepted" x-transition class="cookie-banner fixed bottom-0 left-0 right-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-300 text-center md:text-left">
                    We use cookies to personalise content, analyse traffic, and improve your experience. By clicking Accept All, you agree to our <a href="#" class="text-indigo-400 hover:text-indigo-300">Privacy Policy</a>.
                </p>
                <div class="flex gap-3">
                    <button @click="cookieAccepted = true; localStorage.setItem('cookieAccepted', 'necessary')" class="px-4 py-2 text-sm text-gray-400 hover:text-white transition-colors">
                        Necessary Only
                    </button>
                    <button @click="cookieAccepted = true; localStorage.setItem('cookieAccepted', 'all')" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg text-sm font-medium text-white hover:opacity-90 transition-opacity">
                        Accept All
                    </button>
                </div>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
