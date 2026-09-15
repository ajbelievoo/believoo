@php
    $siteSettings = \App\Models\Setting::pluck('value', 'key');
    $siteName = $siteSettings['site_name'] ?? config('app.name', 'Believoo');
    $siteLogo = $siteSettings['logo'] ?? null;
@endphp
<!DOCTYPE html>
<html lang="en" class="{{ session('theme', 'dark') }} admin-panel">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') | {{ $siteName }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg-primary: #f4f6fb;
            --bg-secondary: #ffffff;
            --bg-tertiary: #eef2f7;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --accent: #00b7ff;
            --accent-strong: #0099ff;
            --accent-soft: rgba(0,183,255,0.10);
            --accent-glow: rgba(0,183,255,0.25);
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow-sm: 0 1px 2px rgba(15,23,42,0.04);
            --shadow-md: 0 8px 24px rgba(15,23,42,0.08);
            --shadow-lg: 0 20px 40px rgba(15,23,42,0.12);
            --radius: 16px;
            --radius-sm: 12px;
            --radius-xs: 10px;
        }

        .dark {
            --bg-primary: #070b14;
            --bg-secondary: #0d1526;
            --bg-tertiary: rgba(148,163,184,0.06);
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #64748b;
            --border-color: rgba(148,163,184,0.12);
            --accent-soft: rgba(0,183,255,0.14);
            --accent-glow: rgba(0,183,255,0.35);
            --shadow-sm: none;
            --shadow-md: 0 8px 24px rgba(0,0,0,0.45);
            --shadow-lg: 0 24px 50px rgba(0,0,0,0.55);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== Sidebar ===== */
        .admin-sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-color);
            z-index: 100;
            display: flex;
            flex-direction: column;
            transition: all 0.25s ease;
        }
        .dark .admin-sidebar {
            background: linear-gradient(180deg, #0b1220 0%, #0d1526 60%, #0b1220 100%);
        }

        .sidebar-header {
            padding: 24px 22px;
            border-bottom: 1px solid var(--border-color);
            background: rgba(0,0,0,0.03);
        }
        .dark .sidebar-header { background: rgba(255,255,255,0.02); }

        .sidebar-logo {
            font-size: 1.4rem;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.02em;
        }
        .sidebar-logo i { color: var(--accent); }
        .sidebar-logo-img { height: 36px; width: auto; object-fit: contain; display: block; }
        .sidebar-logo .hidden { display: none !important; }
        .sidebar-logo-text,
        .sidebar-logo > div > span {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            text-transform: uppercase;
        }

        .sidebar-nav {
            padding: 18px 14px 24px;
            flex: 1;
            overflow-y: auto;
        }
        .sidebar-nav::-webkit-scrollbar { width: 4px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.25); border-radius: 2px; }

        .nav-section { margin-bottom: 26px; }
        .nav-section-title {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--text-muted);
            padding: 8px 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-section-title::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: all 0.18s ease;
            margin-bottom: 3px;
            font-size: 0.88rem;
            font-weight: 500;
            position: relative;
        }
        .nav-link i { width: 18px; text-align: center; font-size: 0.95rem; }
        .nav-link:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            transform: translateX(2px);
        }
        .nav-link.active {
            background: linear-gradient(135deg, var(--accent-soft), rgba(0,183,255,0.05));
            color: var(--accent-strong);
            font-weight: 600;
            border: 1px solid var(--accent-glow);
            box-shadow: 0 4px 12px rgba(0,183,255,0.08);
        }
        .nav-link.active::before {
            content: "";
            position: absolute;
            left: -1px;
            top: 50%;
            transform: translateY(-50%);
            width: 3px;
            height: 60%;
            background: var(--accent);
            border-radius: 2px;
        }
        .nav-badge {
            margin-left: auto;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .nav-badge.new { background: rgba(0,183,255,0.15); color: var(--accent); }
        .nav-badge.live { background: rgba(34,197,94,0.15); color: var(--success); }
        .nav-badge.warn { background: rgba(245,158,11,0.15); color: var(--warning); }

        /* ===== Main ===== */
        .admin-main { margin-left: 280px; min-height: 100vh; }

        /* ===== Header ===== */
        .admin-header {
            position: sticky;
            top: 0;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 99;
        }
        .dark .admin-header { background: rgba(13,21,38,0.85); }

        .header-search { position: relative; }
        .header-search input {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 11px 16px 11px 42px;
            color: var(--text-primary);
            width: 320px;
            font-size: 0.88rem;
            transition: all 0.2s;
        }
        .header-search input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-glow);
        }
        .header-search input::placeholder { color: var(--text-muted); }
        .header-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .header-actions { display: flex; align-items: center; gap: 12px; }

        .icon-btn {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        .icon-btn:hover { background: var(--bg-secondary); color: var(--text-primary); transform: translateY(-1px); }
        .icon-btn .dot {
            position: absolute;
            top: -4px; right: -4px;
            background: var(--danger);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .user-menu { position: relative; }
        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 8px 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .user-menu-btn:hover { background: var(--bg-secondary); }
        .user-avatar {
            width: 34px; height: 34px; border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #ffffff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 0.8rem;
        }

        /* ===== Page content ===== */
        .admin-content { padding: 28px 32px; }
        .page-header { margin-bottom: 28px; }
        .page-title { font-size: 1.9rem; font-weight: 800; color: var(--text-primary); margin-bottom: 6px; letter-spacing: -0.02em; }
        .page-subtitle { color: var(--text-muted); font-size: 0.95rem; }

        /* ===== Stats ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 22px;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: "";
            position: absolute;
            top: 0; left: 0; width: 100%; height: 3px;
            background: linear-gradient(90deg, var(--accent), transparent);
            opacity: 0;
            transition: opacity 0.2s;
        }
        .stat-card:hover { border-color: var(--accent-glow); box-shadow: var(--shadow-md); transform: translateY(-2px); }
        .stat-card:hover::before { opacity: 1; }
        .stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .stat-icon {
            width: 48px; height: 48px; border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.25rem;
        }
        .stat-icon.blue { background: var(--accent-soft); color: var(--accent-strong); }
        .stat-icon.green { background: rgba(34,197,94,0.12); color: var(--success); }
        .stat-icon.yellow { background: rgba(234,179,8,0.12); color: #eab308; }
        .stat-icon.red { background: rgba(239,68,68,0.12); color: var(--danger); }
        .stat-label { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 6px; }
        .stat-value { font-size: 1.9rem; font-weight: 800; color: var(--text-primary); letter-spacing: -0.02em; }

        /* ===== Data table ===== */
        .data-table {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        .table-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid var(--border-color);
        }
        .table-title { font-size: 1.05rem; font-weight: 700; color: var(--text-primary); }
        .table-actions { display: flex; gap: 10px; }

        table { width: 100%; border-collapse: collapse; }
        th {
            text-align: left;
            padding: 14px 18px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            background: var(--bg-tertiary);
        }
        td { padding: 14px 18px; border-bottom: 1px solid rgba(148,163,184,0.10); color: var(--text-secondary); font-size: 0.88rem; }
        tr:hover td { background: var(--bg-tertiary); }

        /* ===== Buttons ===== */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem; font-weight: 600;
            cursor: pointer; transition: all 0.2s;
            text-decoration: none; border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #ffffff;
            box-shadow: 0 4px 14px var(--accent-glow);
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px var(--accent-glow); }
        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover { background: var(--bg-secondary); color: var(--text-primary); }

        /* ===== Badges ===== */
        .badge {
            display: inline-flex; align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.4px;
        }
        .badge-success { background: rgba(34,197,94,0.15); color: var(--success); }
        .badge-warning { background: rgba(234,179,8,0.15); color: #eab308; }
        .badge-danger { background: rgba(239,68,68,0.15); color: var(--danger); }
        .badge-info { background: var(--accent-soft); color: var(--accent-strong); }

        /* ===== Scrollbar ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ===== Responsive ===== */
        @media (max-width: 900px) {
            .admin-sidebar { width: 240px; }
            .admin-main { margin-left: 240px; }
            .admin-content { padding: 20px; }
            .header-search input { width: 220px; }
        }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
                @if(View::exists('components.site-logo'))
                    <x-site-logo :settings="$siteSettings->toArray()" class="sidebar-logo-img" />
                @elseif($siteLogo && (str_contains($siteLogo, '.') || str_contains($siteLogo, '/')))
                    <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="sidebar-logo-img">
                @else
                    <i class="fas fa-bolt"></i>
                    <span class="sidebar-logo-text">{{ $siteName }}</span>
                @endif
            </a>
        </div>

        <nav class="sidebar-nav">
            {{-- Sales Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Sales</div>
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i><span>Dashboard</span>
                </a>
                <a href="{{ route('admin.brands.index') }}" class="nav-link {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i><span>Brand Hub</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="fas fa-shopping-cart"></i><span>Orders</span>
                </a>
                <a href="{{ route('admin.domain-providers.index') }}" class="nav-link {{ request()->routeIs('admin.domain-providers.*') ? 'active' : '' }}">
                    <i class="fas fa-globe"></i><span>Domain Providers</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.agreements.index') }}" class="nav-link {{ request()->routeIs('admin.agreements.*') ? 'active' : '' }}">
                    <i class="fas fa-file-contract"></i><span>Agreements</span>
                </a>
                <a href="{{ route('admin.leads.index') }}" class="nav-link {{ request()->routeIs('admin.leads.*') ? 'active' : '' }}">
                    <i class="fas fa-user-plus"></i><span>Leads</span>
                </a>
            </div>

            {{-- Brands Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Brands</div>
                <a href="{{ route('admin.bconnect.index') }}" class="nav-link {{ request()->routeIs('admin.bconnect.*') ? 'active' : '' }}">
                    <i class="fas fa-rocket"></i><span>B-CONNECT</span>
                </a>
                <a href="{{ route('admin.ghc.index') }}" class="nav-link {{ request()->routeIs('admin.ghc.*') ? 'active' : '' }}">
                    <i class="fas fa-cloud"></i><span>GHC</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.ovh-products.index') }}" class="nav-link {{ request()->routeIs('admin.ovh-products.*') ? 'active' : '' }}">
                    <i class="fas fa-cubes"></i><span>OVH Products</span>
                </a>
                <a href="{{ route('admin.ovh-pricing-rules.index') }}" class="nav-link {{ request()->routeIs('admin.ovh-pricing-rules.*') ? 'active' : '' }}">
                    <i class="fas fa-sliders-h"></i><span>OVH Pricing Rules</span>
                </a>
            </div>

            {{-- Projects Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Projects</div>
                <a href="{{ route('admin.agreement-requests.index') }}" class="nav-link {{ request()->routeIs('admin.agreement-requests.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i><span>Project Requests</span>
                </a>
                <a href="{{ route('admin.projects.index') }}" class="nav-link {{ request()->routeIs('admin.projects.*') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram"></i><span>Projects</span>
                </a>
                <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-ticket-alt"></i><span>Support Tickets</span>
                </a>
                <a href="{{ route('admin.support-agents.index') }}" class="nav-link {{ request()->routeIs('admin.support-agents.*') ? 'active' : '' }}">
                    <i class="fas fa-headset"></i><span>Support Team</span>
                    <span class="nav-badge live">LIVE</span>
                </a>
                <a href="{{ route('admin.knowledge.index') }}" class="nav-link {{ request()->routeIs('admin.knowledge.*') ? 'active' : '' }}">
                    <i class="fas fa-brain"></i><span>AI Knowledge</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.ai-analytics.index') }}" class="nav-link {{ request()->routeIs('admin.ai-analytics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i><span>AI Analytics</span>
                </a>
                <a href="{{ route('admin.agent-leaderboard.index') }}" class="nav-link {{ request()->routeIs('admin.agent-leaderboard.*') ? 'active' : '' }}">
                    <i class="fas fa-trophy"></i><span>Leaderboard</span>
                </a>
                <a href="{{ route('admin.conversions.index') }}" class="nav-link {{ request()->routeIs('admin.conversions.*') ? 'active' : '' }}">
                    <i class="fas fa-funnel-dollar"></i><span>Conversions</span>
                </a>
                <a href="{{ route('admin.ai-training.index') }}" class="nav-link {{ request()->routeIs('admin.ai-training.*') ? 'active' : '' }}">
                    <i class="fas fa-brain"></i><span>AI Training</span>
                </a>
                <a href="{{ route('admin.chat-flows.index') }}" class="nav-link {{ request()->routeIs('admin.chat-flows.*') ? 'active' : '' }}">
                    <i class="fas fa-project-diagram"></i><span>Chat Flows</span>
                </a>
                <a href="{{ route('admin.chat-archive.index') }}" class="nav-link {{ request()->routeIs('admin.chat-archive.*') ? 'active' : '' }}">
                    <i class="fas fa-archive"></i><span>Chat Archive</span>
                </a>
                <a href="{{ route('admin.referrals.index') }}" class="nav-link {{ request()->routeIs('admin.referrals.*') ? 'active' : '' }}">
                    <i class="fas fa-gift"></i><span>Referrals</span>
                </a>
                <a href="{{ route('admin.security.index') }}" class="nav-link {{ request()->routeIs('admin.security.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt"></i><span>Security</span>
                </a>
                <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i><span>Audit Logs</span>
                </a>
                <a href="{{ route('admin.teams.index') }}" class="nav-link {{ request()->routeIs('admin.teams.*') ? 'active' : '' }}">
                    <i class="fas fa-users-cog"></i><span>Teams</span>
                </a>
            </div>

            {{-- Email Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Email</div>
                <a href="{{ route('admin.mailboxes.index') }}" class="nav-link {{ request()->routeIs('admin.mailboxes.*') ? 'active' : '' }}">
                    <i class="fas fa-envelope"></i><span>Email Accounts</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.mail.deliverability') }}" class="nav-link {{ request()->routeIs('admin.mail.deliverability') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt"></i><span>Deliverability</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="https://mail.believoo.com" target="_blank" class="nav-link">
                    <i class="fas fa-inbox"></i><span>Webmail</span>
                    <i class="fas fa-external-link-alt" style="margin-left:auto;font-size:0.65rem;opacity:0.5;"></i>
                </a>
            </div>

            {{-- Billing Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Billing</div>
                <a href="{{ route('admin.invoices.index') }}" class="nav-link {{ request()->routeIs('admin.invoices.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i><span>Invoices</span>
                </a>
                <a href="{{ route('admin.amc-subscriptions.index') }}" class="nav-link {{ request()->routeIs('admin.amc-subscriptions.*') ? 'active' : '' }}">
                    <i class="fas fa-sync-alt"></i><span>AMC Subscriptions</span>
                </a>
                <a href="{{ route('admin.streaming-plans.index') }}" class="nav-link {{ request()->routeIs('admin.streaming-plans.*') ? 'active' : '' }}">
                    <i class="fas fa-broadcast-tower"></i><span>Streaming Plans</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.stream-analytics.index') }}" class="nav-link {{ request()->routeIs('admin.stream-analytics.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i><span>Stream Analytics</span>
                    <span class="nav-badge live">LIVE</span>
                </a>
            </div>

            {{-- Content Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Content</div>
                <a href="{{ route('admin.services.index') }}" class="nav-link {{ request()->routeIs('admin.services.*') ? 'active' : '' }}">
                    <i class="fas fa-cube"></i><span>Services</span>
                </a>
                <a href="{{ route('admin.posts.index') }}" class="nav-link {{ request()->routeIs('admin.posts.*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper"></i><span>Blog Posts</span>
                </a>
                <a href="{{ route('admin.portfolio.index') }}" class="nav-link {{ request()->routeIs('admin.portfolio.*') ? 'active' : '' }}">
                    <i class="fas fa-images"></i><span>Portfolio</span>
                </a>
                <a href="{{ route('admin.stores.index') }}" class="nav-link {{ request()->routeIs('admin.stores.*') ? 'active' : '' }}">
                    <i class="fas fa-store"></i><span>Stores</span>
                </a>
                <a href="{{ route('admin.testimonials.index') }}" class="nav-link {{ request()->routeIs('admin.testimonials.*') ? 'active' : '' }}">
                    <i class="fas fa-comment-dots"></i><span>Testimonials</span>
                    @php $pendingTestimonials = \App\Models\Testimonial::where('is_visible', false)->count(); @endphp
                    @if($pendingTestimonials > 0)
                        <span class="nav-badge warn">{{ $pendingTestimonials }}</span>
                    @endif
                </a>
            </div>

            {{-- Settings Section --}}
            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i><span>Users</span>
                </a>
                <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn"></i><span>Announcements</span>
                </a>
                <a href="{{ route('admin.ai-messages.index') }}" class="nav-link {{ request()->routeIs('admin.ai-messages.*') ? 'active' : '' }}">
                    <i class="fas fa-robot"></i><span>AI Chat</span>
                </a>
                <a href="{{ route('admin.churn-risk.index') }}" class="nav-link {{ request()->routeIs('admin.churn-risk.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i><span>Churn Risk</span>
                </a>
                <a href="{{ route('admin.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.campaigns.*') ? 'active' : '' }}">
                    <i class="fas fa-envelope-open-text"></i><span>Email Campaigns</span>
                </a>
                <a href="{{ route('admin.api-keys.index') }}" class="nav-link {{ request()->routeIs('admin.api-keys.*') ? 'active' : '' }}">
                    <i class="fas fa-key"></i><span>API Keys</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i><span>Settings</span>
                </a>
                <a href="{{ route('admin.sso-providers.index') }}" class="nav-link {{ request()->routeIs('admin.sso-providers.*') ? 'active' : '' }}">
                    <i class="fas fa-shield-alt"></i><span>SSO Providers</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ route('admin.resellers.index') }}" class="nav-link {{ request()->routeIs('admin.resellers.*') ? 'active' : '' }}">
                    <i class="fas fa-store"></i><span>Resellers</span>
                    <span class="nav-badge new">NEW</span>
                </a>
                <a href="{{ url('/panel') }}" class="nav-link" target="_blank" rel="noopener">
                    <i class="fas fa-layer-group"></i><span>Advanced Tools (Filament)</span>
                </a>
                <a href="{{ route('admin.exchange-rates.index') }}" class="nav-link {{ request()->routeIs('admin.exchange-rates.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i><span>Exchange Rates</span>
                    <span class="nav-badge new">NEW</span>
                </a>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        {{-- Header --}}
        <header class="admin-header">
            <div class="header-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search...">
            </div>

            <div class="header-actions">
                <button class="icon-btn" id="themeToggle" title="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="icon-btn">
                    <i class="fas fa-bell"></i>
                    <span class="dot">3</span>
                </button>
                <div class="user-menu">
                    <button class="user-menu-btn">
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span>{{ auth()->user()->name }}</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
            </div>
        </header>

        {{-- Page Content --}}
        <div class="admin-content">
            @if(session('success'))
                <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-weight: 500;">
                    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                    {{ session('success') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const html = document.documentElement;
        let currentTheme = html.className || 'dark';
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', () => {
            currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
            html.className = currentTheme + ' admin-panel';
            localStorage.setItem('admin-theme', currentTheme);
            updateThemeIcon(currentTheme);
        });

        function updateThemeIcon(theme) {
            const icon = themeToggle.querySelector('i');
            if (theme === 'dark') icon.className = 'fas fa-moon';
            else icon.className = 'fas fa-sun';
        }
    </script>

    @stack('scripts')
</body>
</html>
