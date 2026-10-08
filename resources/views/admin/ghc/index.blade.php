@extends('layouts.admin')
@section('title', 'GHC Admin Panel')
@section('content')
<style>
    .ghc-hero {
        background: linear-gradient(135deg, #0b1220 0%, #111827 40%, #0b1220 100%);
        border: 1px solid rgba(0,183,255,0.15);
        border-radius: 18px;
        padding: 24px 28px;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .ghc-hero::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(0,183,255,0.15) 0%, transparent 70%);
        border-radius: 50%;
    }
    .ghc-hero h1 { font-size: 1.8rem; font-weight: 700; color: #f8fafc; margin: 0; }
    .ghc-hero p { color: #94a3b8; margin: 6px 0 0; font-size: 0.9rem; }
    .ghc-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 24px;
        padding: 6px;
        background: rgba(148,163,184,0.06);
        border-radius: 14px;
        border: 1px solid rgba(148,163,184,0.12);
    }
    .ghc-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .ghc-tab:hover { color: #e2e8f0; background: rgba(255,255,255,0.04); }
    .ghc-tab.active {
        background: linear-gradient(135deg, rgba(0,183,255,0.2), rgba(0,183,255,0.05));
        color: #00b7ff;
        border-color: rgba(0,183,255,0.3);
    }
    .ghc-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    }
    .ghc-card-header {
        padding: 18px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ghc-card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: var(--text-primary); }
    .ghc-stat {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s;
    }
    .ghc-stat:hover { transform: translateY(-2px); }
    .ghc-stat-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
    }
    .ghc-table { width: 100%; border-collapse: collapse; }
    .ghc-table th {
        text-align: left;
        padding: 14px 16px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        border-bottom: 1px solid var(--border-color);
        background: rgba(148,163,184,0.03);
    }
    .ghc-table td {
        padding: 14px 16px;
        font-size: 0.85rem;
        color: var(--text-primary);
        border-bottom: 1px solid rgba(148,163,184,0.08);
    }
    .ghc-table tr:hover td { background: rgba(0,183,255,0.03); }
    .ghc-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .ghc-badge-pending { background: rgba(245,158,11,0.15); color: #f59e0b; }
    .ghc-badge-paid, .ghc-badge-active, .ghc-badge-completed { background: rgba(34,197,94,0.15); color: #22c55e; }
    .ghc-badge-failed, .ghc-badge-suspended { background: rgba(239,68,68,0.15); color: #ef4444; }
    .ghc-badge-provisioning { background: rgba(0,183,255,0.15); color: #00b7ff; }
    .ghc-input {
        width: 100%;
        background: var(--bg-tertiary);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 10px 14px;
        color: var(--text-primary);
        font-size: 0.85rem;
    }
    .ghc-btn {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    .ghc-btn-primary {
        background: linear-gradient(135deg, #00b7ff, #0099ff);
        color: white;
    }
    .ghc-btn-secondary {
        background: rgba(148,163,184,0.1);
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }
    .ghc-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .ghc-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    @media (max-width: 900px) { .ghc-grid-4 { grid-template-columns: repeat(2,1fr); } .ghc-grid-2 { grid-template-columns: 1fr; } }
</style>

<div class="ghc-hero">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
        <div>
            <h1><i class="fas fa-cloud" style="color:#00b7ff; margin-right:12px;"></i>GHC Control Panel</h1>
            <p>Hosting, cloud, domains &amp; billing — all managed inside Believoo.</p>
        </div>
        <a href="https://ghc.believoo.com" target="_blank" class="ghc-btn ghc-btn-primary" style="text-decoration:none;">
            <i class="fas fa-external-link-alt"></i> Open GHC Site
        </a>
    </div>
</div>

{{-- GHC Tab Navigation --}}
<div class="ghc-tabs">
    @php $tabs = [
        'overview' => ['Overview','fa-chart-pie'],
        'orders' => ['Orders','fa-shopping-cart'],
        'subscriptions' => ['Subscriptions','fa-server'],
        'domains' => ['Domains','fa-globe'],
        'domain-tlds' => ['Domain TLDs','fa-tags'],
        'catalog' => ['Catalog','fa-box'],
        'margins' => ['Margins','fa-percent'],
        'users' => ['Users','fa-users'],
        'support' => ['Support','fa-life-ring'],
        'settings' => ['Settings','fa-cog'],
        'credentials' => ['Credentials','fa-key'],
        'logs' => ['Logs','fa-list-alt'],
        'system' => ['System Health','fa-heartbeat'],
    ]; @endphp
    @foreach($tabs as $key => $item)
        <a href="{{ route('admin.ghc.index', ['tab' => $key]) }}" class="ghc-tab {{ $tab === $key ? 'active' : '' }}">
            <i class="fas {{ $item[1] }}"></i>{{ $item[0] }}
        </a>
    @endforeach
</div>

@if(session('success'))
    <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #22c55e; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 500;">
        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #ef4444; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: 500;">
        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
    </div>
@endif

{{-- ===== OVERVIEW ===== --}}
@if($tab === 'overview')
<div class="ghc-grid-4" style="margin-bottom:24px;">
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(0,183,255,0.12);color:#00b7ff;"><i class="fas fa-shopping-cart"></i></div>
        <div><div style="font-size:1.8rem;font-weight:800;color:#f8fafc;">{{ $stats['total_orders'] }}</div><div style="font-size:0.8rem;color:#64748b;">Orders</div></div>
    </div>
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e;"><i class="fas fa-server"></i></div>
        <div><div style="font-size:1.8rem;font-weight:800;color:#f8fafc;">{{ $stats['total_subs'] }}</div><div style="font-size:0.8rem;color:#64748b;">Subscriptions</div></div>
    </div>
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(168,85,247,0.12);color:#a855f7;"><i class="fas fa-globe"></i></div>
        <div><div style="font-size:1.8rem;font-weight:800;color:#f8fafc;">{{ $stats['total_domains'] }}</div><div style="font-size:0.8rem;color:#64748b;">Domains</div></div>
    </div>
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><i class="fas fa-life-ring"></i></div>
        <div><div style="font-size:1.8rem;font-weight:800;color:#f8fafc;">{{ $stats['total_tickets'] }}</div><div style="font-size:0.8rem;color:#64748b;">Tickets</div></div>
    </div>
</div>

<div class="ghc-card" style="margin-bottom:24px;">
    <div class="ghc-card-header"><h3><i class="fas fa-clock" style="color:#00b7ff;margin-right:8px;"></i>Recent Orders</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>ID</th><th>Plan</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($orders->take(10) as $o)
                <tr>
                    <td><span style="font-family:monospace;">{{ substr($o->id, 0, 8) }}</span></td>
                    <td>{{ $o->plan_code }}</td>
                    <td>{{ $o->user_id }}</td>
                    <td style="font-weight:700;color:#22c55e;">{{ number_format($o->customer_amount, 2) }} {{ $o->currency }}</td>
                    <td><span class="ghc-badge ghc-badge-{{ strtolower($o->status) }}">{{ $o->status }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== ORDERS ===== --}}
@if($tab === 'orders')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-shopping-cart" style="color:#00b7ff;margin-right:8px;"></i>Customer Orders</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>ID</th><th>Plan</th><th>Category</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($orders as $o)
                <tr>
                    <td><span style="font-family:monospace;">{{ substr($o->id, 0, 8) }}</span></td>
                    <td>{{ $o->plan_code }}</td>
                    <td>{{ $o->category }}</td>
                    <td>{{ $o->user_id }}</td>
                    <td style="font-weight:700;color:#22c55e;">{{ number_format($o->customer_amount, 2) }} {{ $o->currency }}</td>
                    <td><span class="ghc-badge ghc-badge-{{ strtolower($o->status) }}">{{ $o->status }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($o->created_at)->format('d M Y') }}</td>
                    <td>
                        @if($o->status === 'failed' || $o->status === 'provisioning_failed')
                            <form method="POST" action="{{ route('admin.ghc.orders.retry', $o->id) }}">
                                @csrf
                                <button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Retry Provision</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== SUBSCRIPTIONS ===== --}}
@if($tab === 'subscriptions')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-server" style="color:#00b7ff;margin-right:8px;"></i>Active Subscriptions</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Server</th><th>Plan</th><th>IP</th><th>Status</th><th>Next Bill</th><th>Price</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($subscriptions as $s)
                <tr>
                    <td style="font-weight:600;">{{ $s->display_name ?? $s->service_name ?? substr($s->id, 0, 8) }}</td>
                    <td>{{ $s->plan_code }}</td>
                    <td>{{ $s->ip_address ?? '—' }}</td>
                    <td><span class="ghc-badge ghc-badge-{{ strtolower($s->status) }}">{{ $s->status }}</span></td>
                    <td>{{ $s->next_bill_date ? \Carbon\Carbon::parse($s->next_bill_date)->format('d M Y') : '—' }}</td>
                    <td style="font-weight:700;color:#22c55e;">{{ number_format($s->price_amount, 2) }} {{ $s->currency }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.ghc.subscriptions.action', $s->id) }}" style="display:inline;">
                            @csrf
                            <select name="action" class="ghc-input" style="width:auto;padding:6px 10px;font-size:0.75rem;display:inline-block;">
                                <option value="reboot">Reboot</option>
                                <option value="start">Start</option>
                                <option value="stop">Stop</option>
                                <option value="suspend">Suspend</option>
                            </select>
                            <button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Run</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== DOMAINS ===== --}}
@if($tab === 'domains')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-globe" style="color:#00b7ff;margin-right:8px;"></i>Domain Registrations</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Domain</th><th>User</th><th>Status</th><th>Expiry</th><th>Auto-renew</th><th>Price</th><th>Created</th></tr></thead>
            <tbody>
                @foreach($domains as $d)
                <tr>
                    <td style="font-weight:600;color:#00b7ff;">{{ $d->domain_name }}{{ $d->tld }}</td>
                    <td>{{ $d->user_id }}</td>
                    <td><span class="ghc-badge ghc-badge-{{ strtolower($d->status) }}">{{ $d->status }}</span></td>
                    <td>{{ $d->expires_at ? \Carbon\Carbon::parse($d->expires_at)->format('d M Y') : '—' }}</td>
                    <td>{{ $d->auto_renew ? '<span style="color:#22c55e;">ON</span>' : '<span style="color:#64748b;">OFF</span>' }}</td>
                    <td style="font-weight:700;color:#22c55e;">{{ number_format($d->price_amount + $d->tax_amount, 2) }} {{ $d->currency }}</td>
                    <td>{{ \Carbon\Carbon::parse($d->created_at)->format('d M Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== DOMAIN TLDS ===== --}}
@if($tab === 'domain-tlds')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-tags" style="color:#00b7ff;margin-right:8px;"></i>Domain TLD Pricing</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>TLD</th><th>Base Cost</th><th>Margin %</th><th>Final Price</th><th>Active</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($tlds as $t)
                <tr>
                    <form method="POST" action="{{ route('admin.ghc.tld.update') }}">
                        @csrf
                        <td><input type="hidden" name="tld" value="{{ $t['tld'] }}"><strong>{{ $t['tld'] }}</strong></td>
                        <td><input type="number" step="0.01" name="baseCost" value="{{ $t['baseCost'] }}" class="ghc-input" style="width:100px;"></td>
                        <td><input type="number" step="0.01" name="marginPercent" value="{{ $t['marginPercent'] }}" class="ghc-input" style="width:90px;"></td>
                        <td style="font-weight:700;color:#22c55e;">{{ number_format($t['finalPrice'], 2) }} {{ $t['currency'] ?? 'USD' }}</td>
                        <td><input type="checkbox" name="isActive" value="1" {{ $t['isActive'] ? 'checked' : '' }}></td>
                        <td><button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Save</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== CATALOG ===== --}}
@if($tab === 'catalog')
<div class="ghc-card">
    <div class="ghc-card-header">
        <h3><i class="fas fa-box" style="color:#00b7ff;margin-right:8px;"></i>Plan Catalog</h3>
        <form method="POST" action="{{ route('admin.ghc.sync') }}">
            @csrf
            <button type="submit" class="ghc-btn ghc-btn-primary" style="padding:8px 16px;">
                <i class="fas fa-sync-alt"></i> Sync Provider Plans
            </button>
        </form>
    </div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Override Price</th><th>Override Margin</th><th>Active</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($catalog as $p)
                <tr>
                    <form method="POST" action="{{ route('admin.ghc.plan.update', $p['planCode']) }}">
                        @csrf
                        <td><span style="font-family:monospace;font-size:0.8rem;">{{ $p['planCode'] }}</span></td>
                        <td>{{ $p['invoiceName'] }}</td>
                        <td>{{ $p['category'] }}</td>
                        <td><input type="number" step="0.01" name="overridePrice" value="{{ $p['overridePrice'] }}" class="ghc-input" style="width:100px;"></td>
                        <td><input type="number" step="0.01" name="overrideMargin" value="{{ $p['overrideMargin'] }}" class="ghc-input" style="width:90px;"></td>
                        <td>{{ $p['isActive'] ? '<span style="color:#22c55e;">Yes</span>' : '<span style="color:#64748b;">No</span>' }}</td>
                        <td><button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Save</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== MARGINS ===== --}}
@if($tab === 'margins')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-percent" style="color:#00b7ff;margin-right:8px;"></i>Category Margins</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Category</th><th>Margin %</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($margins as $cat => $percent)
                <tr>
                    <form method="POST" action="{{ route('admin.ghc.margin.update') }}">
                        @csrf
                        <td><input type="hidden" name="category" value="{{ $cat }}"><strong>{{ $cat }}</strong></td>
                        <td><input type="number" step="0.01" name="percent" value="{{ $percent }}" class="ghc-input" style="width:100px;"></td>
                        <td><button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Save</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== USERS ===== --}}
@if($tab === 'users')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-users" style="color:#00b7ff;margin-right:8px;"></i>GHC Users</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Email</th><th>Name</th><th>Role</th><th>Suspended</th><th>Joined</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($users as $u)
                <tr>
                    <form method="POST" action="{{ route('admin.ghc.user.update', $u->id) }}">
                        @csrf
                        <td>{{ $u->email }}</td>
                        <td>{{ $u->name }}</td>
                        <td>
                            <select name="role" class="ghc-input" style="width:auto;padding:6px 10px;font-size:0.75rem;display:inline-block;">
                                <option value="customer" {{ $u->role == 'customer' ? 'selected' : '' }}>Customer</option>
                                <option value="admin" {{ $u->role == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                        </td>
                        <td><input type="checkbox" name="is_suspended" value="1" {{ $u->is_suspended ? 'checked' : '' }}></td>
                        <td>{{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}</td>
                        <td><button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Save</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== SUPPORT ===== --}}
@if($tab === 'support')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-life-ring" style="color:#00b7ff;margin-right:8px;"></i>GHC Support Tickets</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>ID</th><th>Subject</th><th>Category</th><th>Customer</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($tickets as $t)
                <tr>
                    <form method="POST" action="{{ route('admin.ghc.ticket.update', $t->id) }}">
                        @csrf
                        <td><span style="font-family:monospace;">{{ substr($t->id, 0, 8) }}</span></td>
                        <td>{{ $t->subject }}</td>
                        <td>{{ $t->category }}</td>
                        <td>{{ $t->name }} ({{ $t->email }})</td>
                        <td>
                            <select name="status" class="ghc-input" style="width:auto;padding:6px 10px;font-size:0.75rem;display:inline-block;">
                                <option value="OPEN" {{ $t->status == 'OPEN' ? 'selected' : '' }}>Open</option>
                                <option value="IN_PROGRESS" {{ $t->status == 'IN_PROGRESS' ? 'selected' : '' }}>In Progress</option>
                                <option value="CLOSED" {{ $t->status == 'CLOSED' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}</td>
                        <td><button type="submit" class="ghc-btn ghc-btn-primary" style="padding:6px 12px;font-size:0.75rem;">Save</button></td>
                    </form>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== SETTINGS ===== --}}
@if($tab === 'settings')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-cog" style="color:#00b7ff;margin-right:8px;"></i>GHC Settings</h3></div>
    <div style="padding:20px;">
        <form method="POST" action="{{ route('admin.ghc.settings.update') }}">
            @csrf
            <div class="ghc-grid-2">
                @foreach($settings as $key => $value)
                @if(is_scalar($value))
                <div>
                    <label style="display:block;font-size:0.75rem;color:#64748b;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.4px;">{{ $key }}</label>
                    <input type="text" name="{{ $key }}" value="{{ $value }}" class="ghc-input">
                </div>
                @endif
                @endforeach
            </div>
            <button type="submit" class="ghc-btn ghc-btn-primary" style="margin-top:20px;padding:10px 24px;">Save Settings</button>
        </form>
    </div>
</div>
@endif

{{-- ===== CREDENTIALS ===== --}}
@if($tab === 'credentials')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-key" style="color:#00b7ff;margin-right:8px;"></i>GHC Provider & Gateway Credentials</h3></div>
    <div style="padding:20px;">
        <form method="POST" action="{{ route('admin.ghc.credentials.update') }}">
            @csrf
            <h4 style="font-size:0.85rem;font-weight:700;color:#00b7ff;margin-bottom:16px;text-transform:uppercase;letter-spacing:0.5px;">Provider (Cloud)</h4>
            <div class="ghc-grid-2" style="margin-bottom:24px;">
                <div>
                    <label style="display:block;font-size:0.75rem;color:#64748b;margin-bottom:6px;">Application Key</label>
                    <input type="text" name="provider_app_key" value="{{ $credentials['credentials']['provider_app_key'] ?? '' }}" class="ghc-input">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;color:#64748b;margin-bottom:6px;">Application Secret</label>
                    <input type="text" name="provider_app_secret" value="{{ $credentials['credentials']['provider_app_secret'] ?? '' }}" class="ghc-input">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;color:#64748b;margin-bottom:6px;">Consumer Key</label>
                    <input type="text" name="provider_consumer_key" value="{{ $credentials['credentials']['provider_consumer_key'] ?? '' }}" class="ghc-input">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;color:#64748b;margin-bottom:6px;">Endpoint</label>
                    <input type="text" name="provider_endpoint" value="{{ $credentials['credentials']['ovh_endpoint'] ?? 'ovh-ca' }}" class="ghc-input">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;color:#64748b;margin-bottom:6px;">Subsidiary</label>
                    <input type="text" name="provider_subsidiary" value="{{ $credentials['credentials']['ovh_subsidiary'] ?? 'CA' }}" class="ghc-input">
                </div>
            </div>

            <h4 style="font-size:0.85rem;font-weight:700;color:#00b7ff;margin-bottom:16px;text-transform:uppercase;letter-spacing:0.5px;">Payment Gateways</h4>
            <div class="ghc-grid-2" style="margin-bottom:24px;">
                @php
                    $gwFields = [
                        'razorpay' => ['keyId' => 'Key ID', 'keySecret' => 'Key Secret', 'webhookSecret' => 'Webhook Secret'],
                        'cashfree' => ['keyId' => 'App ID', 'keySecret' => 'Secret Key'],
                        'paypal'   => ['keyId' => 'Client ID', 'keySecret' => 'Client Secret'],
                        'payu'     => ['keyId' => 'Merchant Key', 'keySecret' => 'Merchant Salt', 'merchantId' => 'Merchant ID'],
                        'stripe'   => ['keyId' => 'Publishable Key', 'keySecret' => 'Secret Key', 'webhookSecret' => 'Webhook Secret'],
                        'paytm'    => ['keyId' => 'Merchant ID', 'keySecret' => 'Merchant Key'],
                    ];
                    $gwWithEnv = ['cashfree', 'paypal', 'payu', 'stripe', 'paytm'];
                @endphp
                @foreach($credentials['gateways'] ?? [] as $gw)
                @php $fields = $gwFields[$gw['name']] ?? null; @endphp
                <div style="border:1px solid var(--border-color);border-radius:12px;padding:16px;background:rgba(148,163,184,0.03);">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;">
                        <strong style="color:var(--text-primary);">{{ ucfirst($gw['name']) }}</strong>
                        <div style="display:flex;align-items:center;gap:10px;">
                            @if(in_array($gw['name'], $gwWithEnv))
                            <select name="gateways[{{ $gw['name'] }}][env]" class="ghc-input" style="padding:4px 8px;font-size:0.75rem;width:auto;">
                                <option value="sandbox" {{ ($gw['config']['env'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Test</option>
                                <option value="production" {{ ($gw['config']['env'] ?? '') === 'production' ? 'selected' : '' }}>Live</option>
                            </select>
                            @endif
                            <label style="display:flex;align-items:center;gap:6px;font-size:0.8rem;color:#64748b;">
                                <input type="checkbox" name="gateways[{{ $gw['name'] }}][isActive]" value="1" {{ $gw['isActive'] ? 'checked' : '' }}> Active
                            </label>
                        </div>
                    </div>
                    @if($fields)
                        @foreach($fields as $cfgKey => $label)
                        <input type="text" name="gateways[{{ $gw['name'] }}][{{ $cfgKey }}]" placeholder="{{ $label }}" value="{{ $gw['config'][$cfgKey] ?? '' }}" class="ghc-input" style="margin-bottom:8px;">
                        @endforeach
                    @else
                        <p style="font-size:0.75rem;color:#64748b;margin:0;">No keys required — enable/disable only.</p>
                    @endif
                </div>
                @endforeach
            </div>

            <button type="submit" class="ghc-btn ghc-btn-primary" style="padding:10px 24px;">Save Credentials</button>
        </form>
    </div>
</div>
@endif

{{-- ===== LOGS ===== --}}
@if($tab === 'logs')
<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-list-alt" style="color:#00b7ff;margin-right:8px;"></i>GHC System Logs</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Type</th><th>Message</th><th>Date</th></tr></thead>
            <tbody>
                @foreach($logs as $l)
                <tr>
                    <td><span class="ghc-badge ghc-badge-{{ strtolower($l->type) }}">{{ $l->type }}</span></td>
                    <td>{{ $l->message }}</td>
                    <td>{{ \Carbon\Carbon::parse($l->created_at)->format('d M Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- ===== SYSTEM HEALTH ===== --}}
@if($tab === 'system')
<div class="ghc-grid-4" style="margin-bottom:24px;">
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(34,197,94,0.12);color:#22c55e;"><i class="fas fa-check-circle"></i></div>
        <div><div style="font-size:1.4rem;font-weight:800;color:#f8fafc;">{{ ucfirst($system['overall'] ?? 'unknown') }}</div><div style="font-size:0.8rem;color:#64748b;">Overall Status</div></div>
    </div>
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(0,183,255,0.12);color:#00b7ff;"><i class="fas fa-server"></i></div>
        <div><div style="font-size:1.4rem;font-weight:800;color:#f8fafc;">{{ count($system['services'] ?? []) }}</div><div style="font-size:0.8rem;color:#64748b;">Monitored Services</div></div>
    </div>
    <div class="ghc-stat">
        <div class="ghc-stat-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;"><i class="fas fa-clock"></i></div>
        <div><div style="font-size:1.4rem;font-weight:800;color:#f8fafc;">{{ isset($system['updatedAt']) ? \Carbon\Carbon::parse($system['updatedAt'])->format('H:i:s') : '—' }}</div><div style="font-size:0.8rem;color:#64748b;">Last Checked</div></div>
    </div>
</div>

<div class="ghc-card">
    <div class="ghc-card-header"><h3><i class="fas fa-heartbeat" style="color:#00b7ff;margin-right:8px;"></i>Public Services</h3></div>
    <div style="overflow-x:auto;">
        <table class="ghc-table">
            <thead><tr><th>Service</th><th>Status</th></tr></thead>
            <tbody>
                @foreach($system['services'] ?? [] as $s)
                <tr>
                    <td style="font-weight:600;">{{ $s['name'] }}</td>
                    <td>
                        @if($s['status'] === 'operational')
                            <span class="ghc-badge ghc-badge-active">Operational</span>
                        @elseif($s['status'] === 'degraded')
                            <span class="ghc-badge ghc-badge-pending">Degraded</span>
                        @else
                            <span class="ghc-badge ghc-badge-failed">Outage</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
