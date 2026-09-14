{{--
    BelieVoo Dashboard — Collapsible Sidebar
    Default: 64px (icons only). Hover: 240px (icons + labels).
    Acrylic blur effect. Keyboard accessible.
    Requirements: 11.1–11.7
--}}

@php
    $currentTab = $activeTab ?? 'projects';
    $navItems = [
        ['tab' => 'projects',    'icon' => 'fa-th-large',      'label' => 'Dashboard',    'badge' => null],
        ['tab' => 'hosting',     'icon' => 'fa-server',        'label' => 'VPS Instances','badge' => null],
        ['tab' => 'dns_manager', 'icon' => 'fa-globe',         'label' => 'DNS Manager',  'badge' => null],
        ['tab' => 'tickets',     'icon' => 'fa-headset',       'label' => 'Support',      'badge' => $openTicketCount ?? null],
        ['tab' => 'agreements',  'icon' => 'fa-file-contract', 'label' => 'Agreements',   'badge' => null],
        ['tab' => 'activity',    'icon' => 'fa-history',       'label' => 'Activity',     'badge' => null],
    ];
@endphp

<nav class="believoo-sidebar" role="navigation" aria-label="Main navigation">

    {{-- Logo --}}
    <div class="sidebar-logo">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
             style="background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));">
            <i class="fas fa-bolt text-white text-sm"></i>
        </div>
        <span class="sidebar-logo-text">BelieVoo</span>
    </div>

    {{-- Navigation Items --}}
    <ul class="sidebar-nav list-none m-0 p-0 flex-1" role="list">
        @foreach($navItems as $item)
        <li role="listitem">
            @if($item['tab'] === 'activity')
                {{-- Activity opens the drawer, not a tab --}}
                <button
                    class="sidebar-nav-item {{ $currentTab === $item['tab'] ? 'active' : '' }}"
                    @click="$dispatch('open-activity-drawer')"
                    aria-label="{{ $item['label'] }}"
                    tabindex="0"
                    type="button">
                    <span class="sidebar-icon" aria-hidden="true">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="sidebar-label">{{ $item['label'] }}</span>
                    @if($item['badge'])
                        <span class="sidebar-badge" aria-label="{{ $item['badge'] }} notifications">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </button>
            @else
                <button
                    class="sidebar-nav-item {{ $currentTab === $item['tab'] ? 'active' : '' }}"
                    wire:click="switchTab('{{ $item['tab'] }}')"
                    aria-label="{{ $item['label'] }}"
                    aria-current="{{ $currentTab === $item['tab'] ? 'page' : 'false' }}"
                    tabindex="0"
                    type="button">
                    <span class="sidebar-icon" aria-hidden="true">
                        <i class="fas {{ $item['icon'] }}"></i>
                    </span>
                    <span class="sidebar-label">{{ $item['label'] }}</span>
                    @if($item['badge'])
                        <span class="sidebar-badge" aria-label="{{ $item['badge'] }} notifications">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </button>
            @endif
        </li>
        @endforeach
    </ul>

    {{-- Divider --}}
    <div class="sidebar-divider" role="separator"></div>

    {{-- Theme Switcher --}}
    <div class="px-3 pb-4">
        <div class="sidebar-nav-item" style="cursor: default; padding-bottom: 0.5rem;">
            <span class="sidebar-icon" aria-hidden="true">
                <i class="fas fa-palette"></i>
            </span>
            <span class="sidebar-label text-xs" style="color: var(--text-muted);">Theme</span>
        </div>
        <div class="flex flex-col gap-1 pl-1">
            @foreach([
                ['midnight-onyx',      'fa-moon',      'Midnight Onyx'],
                ['frost-white',        'fa-snowflake',  'Frost White'],
                ['believoo-signature', 'fa-star',       'BelieVoo Signature'],
            ] as [$themeKey, $themeIcon, $themeLabel])
            <button
                @click="$store.theme.set('{{ $themeKey }}')"
                :class="$store.theme.current === '{{ $themeKey }}' ? 'active' : ''"
                class="sidebar-nav-item"
                style="padding: 0.5rem 1rem; font-size: 0.75rem;"
                aria-label="Switch to {{ $themeLabel }} theme"
                tabindex="0"
                type="button">
                <span class="sidebar-icon" aria-hidden="true" style="font-size: 0.75rem;">
                    <i class="fas {{ $themeIcon }}"></i>
                </span>
                <span class="sidebar-label" style="font-size: 0.7rem; text-transform: none; letter-spacing: 0;">
                    {{ $themeLabel }}
                </span>
            </button>
            @endforeach
        </div>
    </div>

    {{-- User avatar at bottom --}}
    <div class="sidebar-divider" role="separator"></div>
    <div class="sidebar-nav-item" style="cursor: default; margin-bottom: 0.5rem;">
        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
             style="background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary)); min-width: 32px;">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
        </div>
        <div class="sidebar-label" style="text-transform: none; letter-spacing: 0; font-size: 0.8rem;">
            <div class="font-bold" style="color: var(--text-primary);">{{ Auth::user()->name ?? 'User' }}</div>
            <div style="font-size: 0.65rem; color: var(--text-muted); font-weight: 400;">Client Portal</div>
        </div>
    </div>

</nav>
