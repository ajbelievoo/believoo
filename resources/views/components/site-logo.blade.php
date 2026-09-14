@props(['class' => 'h-10 w-auto', 'settings' => null, 'mode' => 'auto'])

@php
    if (!$settings) {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
    }
    $alt = $settings['site_name'] ?? config('app.name', 'BELIEVOO');
    $hasLightLogo = isset($settings['logo']) && (str_contains($settings['logo'], '.') || str_contains($settings['logo'], '/'));
    $hasDarkLogo = isset($settings['dark_logo']) && (str_contains($settings['dark_logo'], '.') || str_contains($settings['dark_logo'], '/'));
    $forceDark = $mode === 'dark';
    $forceLight = $mode === 'light';
@endphp

<div {{ $attributes }}>
    @if($hasLightLogo && ($forceLight || !$forceDark))
        <img src="{{ asset('storage/' . $settings['logo']) }}" alt="{{ $alt }}" class="{{ $class }} block @if(!$forceLight && $hasDarkLogo) dark:hidden @endif" style="filter: drop-shadow(0 0 4px rgba(0,0,0,0.15));">
    @endif
    @if($hasDarkLogo && ($forceDark || !$forceLight))
        <img src="{{ asset('storage/' . $settings['dark_logo']) }}" alt="{{ $alt }}" class="{{ $class }} @if($forceDark) block @elseif($hasLightLogo) hidden dark:block @else block @endif" style="filter: drop-shadow(0 0 6px rgba(255,255,255,0.6));">
    @endif
    @if(!$hasLightLogo && !$hasDarkLogo)
        <span class="text-3xl font-black bg-gradient-to-r from-amber-400 to-amber-600 bg-clip-text text-transparent tracking-tighter uppercase">
            {{ $alt }}
        </span>
    @endif
</div>
