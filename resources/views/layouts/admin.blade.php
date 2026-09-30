@php
    $siteSettings = \App\Models\Setting::pluck('value', 'key');
    $siteName = $siteSettings['site_name'] ?? config('app.name', 'Believoo');
    $siteLogo = $siteSettings['logo'] ?? null;

    $adminNav = [
        'Sales' => [
            ['route' => 'admin.dashboard', 'icon' => 'fa-chart-line', 'label' => 'Dashboard'],
            ['route' => 'admin.brands.index', 'icon' => 'fa-layer-group', 'label' => 'Brand Hub'],
            ['route' => 'admin.orders.index', 'icon' => 'fa-shopping-cart', 'label' => 'Orders'],
            ['route' => 'admin.domain-providers.index', 'icon' => 'fa-globe', 'label' => 'Domain Providers'],
            ['route' => 'admin.agreements.index', 'icon' => 'fa-file-contract', 'label' => 'Agreements'],
            ['route' => 'admin.leads.index', 'icon' => 'fa-user-plus', 'label' => 'Leads'],
        ],
        'Brands' => [
            ['route' => 'admin.bconnect.index', 'icon' => 'fa-rocket', 'label' => 'B-CONNECT'],
            ['route' => 'admin.ghc.index', 'icon' => 'fa-cloud', 'label' => 'GHC'],
            ['route' => 'admin.ovh-products.index', 'icon' => 'fa-cubes', 'label' => 'OVH Products'],
            ['route' => 'admin.ovh-pricing-rules.index', 'icon' => 'fa-sliders-h', 'label' => 'OVH Pricing Rules'],
        ],
        'Projects' => [
            ['route' => 'admin.agreement-requests.index', 'icon' => 'fa-clipboard-list', 'label' => 'Project Requests'],
            ['route' => 'admin.projects.index', 'icon' => 'fa-project-diagram', 'label' => 'Projects'],
            ['route' => 'admin.tickets.index', 'icon' => 'fa-ticket-alt', 'label' => 'Support Tickets'],
            ['route' => 'admin.support-agents.index', 'icon' => 'fa-headset', 'label' => 'Support Team'],
            ['route' => 'admin.knowledge.index', 'icon' => 'fa-brain', 'label' => 'AI Knowledge'],
            ['route' => 'admin.ai-analytics.index', 'icon' => 'fa-chart-pie', 'label' => 'AI Analytics'],
            ['route' => 'admin.agent-leaderboard.index', 'icon' => 'fa-trophy', 'label' => 'Leaderboard'],
            ['route' => 'admin.conversions.index', 'icon' => 'fa-funnel-dollar', 'label' => 'Conversions'],
            ['route' => 'admin.ai-training.index', 'icon' => 'fa-brain', 'label' => 'AI Training'],
            ['route' => 'admin.chat-flows.index', 'icon' => 'fa-project-diagram', 'label' => 'Chat Flows'],
            ['route' => 'admin.chat-archive.index', 'icon' => 'fa-archive', 'label' => 'Chat Archive'],
            ['route' => 'admin.referrals.index', 'icon' => 'fa-gift', 'label' => 'Referrals'],
            ['route' => 'admin.security.index', 'icon' => 'fa-shield-alt', 'label' => 'Security'],
            ['route' => 'admin.audit-logs.index', 'icon' => 'fa-clipboard-list', 'label' => 'Audit Logs'],
            ['route' => 'admin.teams.index', 'icon' => 'fa-users-cog', 'label' => 'Teams'],
        ],
        'Email' => [
            ['route' => 'admin.mailboxes.index', 'icon' => 'fa-envelope', 'label' => 'Email Accounts'],
            ['route' => 'admin.mail.deliverability', 'icon' => 'fa-shield-alt', 'label' => 'Deliverability'],
            ['route' => null, 'icon' => 'fa-inbox', 'label' => 'Webmail', 'url' => 'https://mail.believoo.com', 'external' => true],
        ],
        'Billing' => [
            ['route' => 'admin.invoices.index', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Invoices'],
            ['route' => 'admin.amc-subscriptions.index', 'icon' => 'fa-sync-alt', 'label' => 'AMC Subscriptions'],
            ['route' => 'admin.streaming-plans.index', 'icon' => 'fa-broadcast-tower', 'label' => 'Streaming Plans'],
            ['route' => 'admin.stream-analytics.index', 'icon' => 'fa-chart-bar', 'label' => 'Stream Analytics'],
        ],
        'Content' => [
            ['route' => 'admin.services.index', 'icon' => 'fa-cube', 'label' => 'Services'],
            ['route' => 'admin.posts.index', 'icon' => 'fa-newspaper', 'label' => 'Blog Posts'],
            ['route' => 'admin.portfolio.index', 'icon' => 'fa-images', 'label' => 'Portfolio'],
            ['route' => 'admin.stores.index', 'icon' => 'fa-store', 'label' => 'Stores'],
            ['route' => 'admin.testimonials.index', 'icon' => 'fa-comment-dots', 'label' => 'Testimonials'],
        ],
        'Settings' => [
            ['route' => 'admin.users.index', 'icon' => 'fa-users', 'label' => 'Users'],
            ['route' => 'admin.announcements.index', 'icon' => 'fa-bullhorn', 'label' => 'Announcements'],
            ['route' => 'admin.ai-messages.index', 'icon' => 'fa-robot', 'label' => 'AI Chat'],
            ['route' => 'admin.churn-risk.index', 'icon' => 'fa-chart-line', 'label' => 'Churn Risk'],
            ['route' => 'admin.campaigns.index', 'icon' => 'fa-envelope-open-text', 'label' => 'Email Campaigns'],
            ['route' => 'admin.api-keys.index', 'icon' => 'fa-key', 'label' => 'API Keys'],
            ['route' => 'admin.settings.index', 'icon' => 'fa-cog', 'label' => 'Settings'],
            ['route' => 'admin.sso-providers.index', 'icon' => 'fa-shield-alt', 'label' => 'SSO Providers'],
            ['route' => 'admin.resellers.index', 'icon' => 'fa-store', 'label' => 'Resellers'],
            ['route' => 'admin.exchange-rates.index', 'icon' => 'fa-exchange-alt', 'label' => 'Exchange Rates'],
            ['route' => null, 'icon' => 'fa-layer-group', 'label' => 'Advanced Tools (Filament)', 'url' => url('/panel'), 'external' => true],
        ],
    ];

    $currentRouteName = request()->route()?->getName() ?? '';
    $breadcrumbSegments = [];
    $path = request()->path();
    $segments = array_filter(explode('/', $path));
    $built = '';
    foreach ($segments as $i => $segment) {
        $built .= ($built ? '/' : '') . $segment;
        $label = ucwords(str_replace(['-', '_'], ' ', $segment));
        if ($i === count($segments) - 1) {
            $breadcrumbSegments[] = ['label' => $label, 'active' => true];
        } else {
            $breadcrumbSegments[] = ['label' => $label, 'url' => url('/' . $built)];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en" class="{{ session('theme', 'dark') }} admin-panel">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | {{ $siteName }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">

    @stack('styles')
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-brand">
                @if($siteLogo && (str_contains($siteLogo, '.') || str_contains($siteLogo, '/')))
                    <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}">
                @else
                    <div class="sidebar-brand-icon"><i class="fas fa-bolt"></i></div>
                @endif
                <span class="sidebar-brand-text">{{ $siteName }}</span>
                <button class="sidebar-toggle" id="sidebarToggle" title="Collapse sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>

            <nav class="sidebar-nav">
                @foreach($adminNav as $group => $items)
                    <div class="nav-group" data-group="{{ $group }}">
                        <div class="nav-group-header" onclick="toggleNavGroup(this)">
                            <span>{{ $group }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="nav-items">
                            @foreach($items as $item)
                                @php
                                    $isActive = $item['route'] && str_starts_with($currentRouteName, $item['route']);
                                    $href = $item['url'] ?? ($item['route'] ? route($item['route']) : '#');
                                @endphp
                                <a href="{{ $href }}" class="nav-link {{ $isActive ? 'active' : '' }}" @if(!empty($item['external'])) target="_blank" @endif>
                                    <i class="fas {{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                    @if(!empty($item['external']))
                                        <i class="fas fa-external-link-alt" style="margin-left:auto;font-size:0.65rem;opacity:0.5;"></i>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="header-breadcrumb">
                    <button class="mobile-menu-btn icon-btn" id="mobileMenuBtn" style="margin-right:6px;">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="{{ route('admin.dashboard') }}"><i class="fas fa-home"></i></a>
                    <span>/</span>
                    @foreach($breadcrumbSegments as $segment)
                        @if($segment['active'] ?? false)
                            <span class="current">{{ $segment['label'] }}</span>
                        @else
                            <a href="{{ $segment['url'] }}">{{ $segment['label'] }}</a>
                            <span>/</span>
                        @endif
                    @endforeach
                </div>

                <div style="display:flex;align-items:center;gap:14px;">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search admin...">
                    </div>

                    <div class="header-actions">
                        <button class="icon-btn" id="themeToggle" title="Toggle theme">
                            <i class="fas fa-moon"></i>
                        </button>

                        @php
                            $adminUnreadCount = auth()->user()->unreadNotifications->count();
                            $adminNotifications = auth()->user()->notifications()->latest()->take(8)->get();
                        @endphp
                        <div style="position:relative;">
                            <button class="icon-btn" id="notifToggle" title="Notifications">
                                <i class="fas fa-bell"></i>
                                @if($adminUnreadCount > 0)
                                    <span class="dot">{{ $adminUnreadCount > 99 ? '99+' : $adminUnreadCount }}</span>
                                @endif
                            </button>
                            <div class="header-dropdown" id="notifDropdown">
                                <div class="header-dropdown-head">
                                    <h4>Notifications</h4>
                                    @if($adminUnreadCount > 0)
                                        <form method="post" action="{{ route('admin.notifications.read-all') }}">
                                            @csrf
                                            <button type="submit">Mark all read</button>
                                        </form>
                                    @endif
                                </div>
                                <div class="notif-list">
                                    @forelse($adminNotifications as $notification)
                                        @php
                                            $nTitle = $notification->data['title'] ?? 'Notification';
                                            $nBody = $notification->data['body'] ?? ($notification->data['message'] ?? '');
                                        @endphp
                                        <form method="post" action="{{ route('admin.notifications.read', $notification->id) }}">
                                            @csrf
                                            <button type="submit" class="notif-item {{ $notification->read_at ? '' : 'unread' }}">
                                                <div class="notif-title">{{ $nTitle }}</div>
                                                @if($nBody)<div class="notif-body">{{ \Illuminate\Support\Str::limit($nBody, 90) }}</div>@endif
                                                <div class="notif-time">{{ $notification->created_at->diffForHumans() }}</div>
                                            </button>
                                        </form>
                                    @empty
                                        <div style="padding:32px 16px;text-align:center;color:var(--admin-text-muted);font-size:0.85rem;">
                                            <i class="fas fa-bell-slash" style="font-size:1.4rem;display:block;margin-bottom:8px;opacity:0.5;"></i>
                                            No notifications
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="user-menu" style="position:relative;">
                            <button class="user-menu-btn" id="userMenuToggle">
                                <div class="user-avatar">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span>{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down" style="font-size:0.7rem;color:var(--admin-text-muted);"></i>
                            </button>
                            <div class="header-dropdown" id="userMenuDropdown" style="width:220px;">
                                <a href="{{ route('profile.edit') }}" class="header-menu-item"><i class="fas fa-user"></i> My Profile</a>
                                <a href="{{ route('admin.settings.index') }}" class="header-menu-item"><i class="fas fa-cog"></i> Settings</a>
                                <a href="{{ url('/') }}" class="header-menu-item"><i class="fas fa-globe"></i> View Site</a>
                                <form method="post" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="header-menu-item" style="color:var(--admin-danger);"><i class="fas fa-sign-out-alt"></i> Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        {{ session('info') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        let currentTheme = localStorage.getItem('admin-theme') || (html.classList.contains('dark') ? 'dark' : 'light');
        html.className = currentTheme + ' admin-panel';
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', () => {
            currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.className = currentTheme + ' admin-panel';
            localStorage.setItem('admin-theme', currentTheme);
            updateThemeIcon(currentTheme);
        });

        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            icon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        }

        function setupHeaderDropdown(btnId, menuId) {
            const btn = document.getElementById(btnId);
            const menu = document.getElementById(menuId);
            if (!btn || !menu) return;
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                document.querySelectorAll('.header-dropdown.open').forEach(function (d) {
                    if (d !== menu) d.classList.remove('open');
                });
                menu.classList.toggle('open');
            });
            menu.addEventListener('click', function (e) { e.stopPropagation(); });
        }
        setupHeaderDropdown('notifToggle', 'notifDropdown');
        setupHeaderDropdown('userMenuToggle', 'userMenuDropdown');
        document.addEventListener('click', function () {
            document.querySelectorAll('.header-dropdown.open').forEach(function (d) {
                d.classList.remove('open');
            });
        });

        // Sidebar collapse (desktop)
        const sidebar = document.getElementById('adminSidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        if (sidebarToggle && sidebar) {
            const collapsed = localStorage.getItem('admin-sidebar-collapsed') === '1';
            if (collapsed) sidebar.classList.add('collapsed');
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('admin-sidebar-collapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
            });
        }

        function toggleNavGroup(header) {
            const group = header.closest('.nav-group');
            group.classList.toggle('collapsed');
            const state = JSON.parse(localStorage.getItem('admin-nav-groups') || '{}');
            state[group.dataset.group] = group.classList.contains('collapsed');
            localStorage.setItem('admin-nav-groups', JSON.stringify(state));
        }

        // Restore nav group states
        (function() {
            const state = JSON.parse(localStorage.getItem('admin-nav-groups') || '{}');
            document.querySelectorAll('.nav-group').forEach(group => {
                if (state[group.dataset.group]) group.classList.add('collapsed');
            });
        })();

        // Mobile sidebar
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024 && sidebar.classList.contains('open') && !sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            });
        }

        // Bootstrap-style tab polyfill (used by some admin views)
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetSelector = trigger.getAttribute('data-bs-target');
                if (!targetSelector) return;
                const pane = document.querySelector(targetSelector);
                const group = trigger.closest('[role="tablist"]') || trigger.closest('.nav');
                if (group) {
                    group.querySelectorAll('[data-bs-toggle="tab"]').forEach(t => {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                    });
                }
                trigger.classList.add('active');
                trigger.setAttribute('aria-selected', 'true');
                if (pane) {
                    pane.closest('.tab-content')?.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active', 'show'));
                    pane.classList.add('active', 'show');
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
