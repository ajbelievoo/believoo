<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Believoo') }} - Client Portal</title>
    <meta name="description" content="Access your Believoo account.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,900&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col">
    <div class="flex-1 flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center">
                    @php
                        $siteSettings = \App\Models\Setting::pluck('value', 'key');
                        $siteName = $siteSettings['site_name'] ?? config('app.name', 'Believoo');
                    @endphp
                    @if(isset($siteSettings['logo']) && (str_contains($siteSettings['logo'], '.') || str_contains($siteSettings['logo'], '/')))
                        <img src="{{ asset('storage/' . $siteSettings['logo']) }}" alt="{{ $siteName }}" class="h-12 w-auto mb-4">
                    @else
                        <span class="text-2xl font-black text-slate-900 mb-4">{{ $siteName }}</span>
                    @endif
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                {{ $slot }}
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-amber-600 transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Back to Homepage
                </a>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
