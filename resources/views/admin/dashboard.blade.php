@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.bconnect.index') }}" class="btn btn-primary"><i class="fas fa-network-wired"></i>Bmydesk</a>
        <a href="{{ route('admin.ghc.index') }}" class="btn btn-secondary"><i class="fas fa-cloud"></i>GHC</a>
    </div>
</div>

{{-- Resource Alert --}}
@if($resourceAlert)
<div class="card mb-6" style="border-color: rgba(245, 158, 11, 0.4);">
    <div class="card-body" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(239, 68, 68, 0.06));">
        <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:44px;height:44px;border-radius:12px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas {{ $resourceAlert['icon'] }}" style="color:var(--admin-warning);font-size:1.1rem;"></i>
            </div>
            <div style="flex:1;">
                <div style="font-weight:700;color:var(--admin-warning);margin-bottom:2px;">Resource Alert</div>
                <div style="color:var(--admin-text-secondary);font-size:0.9rem;">{{ $resourceAlert['message'] }}</div>
            </div>
            <a href="{{ route('admin.proxmox.vms.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-server"></i>Manage VMs</a>
        </div>
    </div>
</div>
@endif

{{-- Resource Monitoring --}}
<div class="card mb-6">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-server"></i>Resource Monitoring</div>
        <div style="display:flex;align-items:center;gap:12px;font-size:0.8rem;color:var(--admin-text-muted);">
            @if($proxmoxResources['available'])
                <span class="badge badge-success"><i class="fas fa-circle" style="font-size:0.45rem;"></i>Online</span>
            @else
                <span class="badge badge-danger"><i class="fas fa-circle" style="font-size:0.45rem;"></i>Offline</span>
            @endif
            <span><i class="fas fa-network-wired"></i>Node: {{ config('proxmox.node', 'ns548195') }}</span>
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-5" style="grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));">
            <div class="stat-card" style="margin:0;">
                <div class="stat-header">
                    <div class="stat-icon blue"><i class="fas fa-microchip"></i></div>
                </div>
                <div class="stat-label">CPU Usage</div>
                <div class="stat-value">{{ $proxmoxResources['cpu_percent'] }}%</div>
                <div class="stat-change">{{ $proxmoxResources['cpu_cores'] }} Cores</div>
                <div style="width:100%;height:6px;background:var(--admin-border);border-radius:3px;overflow:hidden;margin-top:10px;">
                    <div style="width:{{ $proxmoxResources['cpu_percent'] }}%;height:100%;background:linear-gradient(90deg, var(--admin-accent), var(--admin-accent-strong));border-radius:3px;"></div>
                </div>
            </div>

            <div class="stat-card" style="margin:0;">
                <div class="stat-header">
                    <div class="stat-icon purple"><i class="fas fa-memory"></i></div>
                </div>
                <div class="stat-label">RAM Usage</div>
                <div class="stat-value" style="{{ $proxmoxResources['ram_percent'] > 85 ? 'color:var(--admin-danger);' : '' }}">{{ $proxmoxResources['ram_percent'] }}%</div>
                <div class="stat-change">
                    @if($proxmoxResources['ram_total'] > 0)
                        {{ round($proxmoxResources['ram_used'] / 1024 / 1024 / 1024, 1) }} / {{ round($proxmoxResources['ram_total'] / 1024 / 1024 / 1024, 1) }} GB
                    @else
                        N/A
                    @endif
                </div>
                <div style="width:100%;height:6px;background:var(--admin-border);border-radius:3px;overflow:hidden;margin-top:10px;">
                    <div style="width:{{ min($proxmoxResources['ram_percent'], 100) }}%;height:100%;background:{{ $proxmoxResources['ram_percent'] > 85 ? 'linear-gradient(90deg, var(--admin-danger), #dc2626)' : 'linear-gradient(90deg, var(--admin-purple), #6d28d9)' }};border-radius:3px;"></div>
                </div>
            </div>

            <div class="stat-card" style="margin:0;">
                <div class="stat-header">
                    <div class="stat-icon yellow"><i class="fas fa-hdd"></i></div>
                </div>
                <div class="stat-label">Disk Usage</div>
                <div class="stat-value">{{ $proxmoxResources['disk_percent'] }}%</div>
                <div class="stat-change">
                    @if($proxmoxResources['disk_total'] > 0)
                        {{ round($proxmoxResources['disk_used'] / 1024 / 1024 / 1024, 1) }} / {{ round($proxmoxResources['disk_total'] / 1024 / 1024 / 1024, 1) }} GB
                    @else
                        N/A
                    @endif
                </div>
                <div style="width:100%;height:6px;background:var(--admin-border);border-radius:3px;overflow:hidden;margin-top:10px;">
                    <div style="width:{{ min($proxmoxResources['disk_percent'], 100) }}%;height:100%;background:linear-gradient(90deg, var(--admin-warning), #d97706);border-radius:3px;"></div>
                </div>
            </div>

            <div class="stat-card" style="margin:0;">
                <div class="stat-header">
                    <div class="stat-icon green"><i class="fas fa-globe"></i></div>
                </div>
                <div class="stat-label">Total Domains</div>
                <div class="stat-value">{{ number_format($totalDomains) }}</div>
                <div class="stat-change">Registered</div>
                <a href="{{ route('admin.domain-providers.index') }}" style="display:inline-block;margin-top:10px;font-size:0.75rem;color:var(--admin-success);"><i class="fas fa-arrow-right"></i>Manage</a>
            </div>

            <div class="stat-card" style="margin:0;">
                <div class="stat-header">
                    <div class="stat-icon blue"><i class="fas fa-cloud"></i></div>
                </div>
                <div class="stat-label">Active VPS</div>
                <div class="stat-value">{{ number_format($activeVps) }}</div>
                <div class="stat-change">of {{ number_format($totalVps) }} Total</div>
                <a href="{{ route('admin.proxmox.vms.index') }}" style="display:inline-block;margin-top:10px;font-size:0.75rem;color:var(--admin-accent-strong);"><i class="fas fa-arrow-right"></i>Manage VMs</a>
            </div>
        </div>
    </div>
</div>

{{-- Inventory Status --}}
<div class="card mb-6" style="{{ $inventoryStatus['low_stock'] ? 'border-color: rgba(239, 68, 68, 0.35);' : '' }}">
    <div class="card-header" style="{{ $inventoryStatus['low_stock'] ? 'background: rgba(239, 68, 68, 0.05);' : '' }}">
        <div class="card-title"><i class="fas fa-warehouse" style="{{ $inventoryStatus['low_stock'] ? 'color:var(--admin-danger);' : 'color:var(--admin-success);' }}"></i>Inventory Status</div>
        <div style="font-size:0.8rem;color:var(--admin-text-muted);">
            <i class="fas fa-server"></i>Cloud Server: ns548195
        </div>
    </div>
    <div class="card-body">
        <div class="grid grid-cols-3" style="grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));margin-bottom:24px;">
            <div class="card" style="margin:0;">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--admin-purple);"><i class="fas fa-memory" style="margin-right:4px;"></i>RAM Inventory</div>
                        <span class="text-muted" style="font-size:0.8rem;">{{ $inventoryStatus['total_ram_gb'] }} GB Total</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;">
                        <div style="font-size:1.6rem;font-weight:800;color:var(--admin-text);">{{ $inventoryStatus['remaining_ram_gb'] }} GB</div>
                        <span class="text-muted" style="font-size:0.85rem;">{{ $inventoryStatus['used_ram_gb'] }} GB Used</span>
                    </div>
                    <div style="width:100%;height:8px;background:var(--admin-border);border-radius:4px;overflow:hidden;">
                        <div style="width:{{ min($inventoryStatus['ram_percent'], 100) }}%;height:100%;background:{{ $inventoryStatus['ram_percent'] > 85 ? 'linear-gradient(90deg, var(--admin-danger), #dc2626)' : 'linear-gradient(90deg, var(--admin-purple), #6d28d9)' }};border-radius:4px;"></div>
                    </div>
                    <div class="text-muted" style="font-size:0.75rem;margin-top:6px;text-align:right;">{{ $inventoryStatus['ram_percent'] }}% Used</div>
                </div>
            </div>

            <div class="card" style="margin:0;">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--admin-warning);"><i class="fas fa-hdd" style="margin-right:4px;"></i>Disk Inventory</div>
                        <span class="text-muted" style="font-size:0.8rem;">{{ $inventoryStatus['total_disk_gb'] }} GB Total</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;">
                        <div style="font-size:1.6rem;font-weight:800;color:var(--admin-text);">{{ $inventoryStatus['remaining_disk_gb'] }} GB</div>
                        <span class="text-muted" style="font-size:0.85rem;">{{ $inventoryStatus['used_disk_gb'] }} GB Used</span>
                    </div>
                    <div style="width:100%;height:8px;background:var(--admin-border);border-radius:4px;overflow:hidden;">
                        <div style="width:{{ min($inventoryStatus['disk_percent'], 100) }}%;height:100%;background:linear-gradient(90deg, var(--admin-warning), #d97706);border-radius:4px;"></div>
                    </div>
                    <div class="text-muted" style="font-size:0.75rem;margin-top:6px;text-align:right;">{{ $inventoryStatus['disk_percent'] }}% Used</div>
                </div>
            </div>

            <div class="card" style="margin:0;">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:var(--admin-accent-strong);"><i class="fas fa-network-wired" style="margin-right:4px;"></i>Cloud Bandwidth</div>
                        <span class="text-muted" style="font-size:0.8rem;">{{ $inventoryStatus['total_bandwidth_tb'] }} TiB/Month</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px;">
                        <div style="font-size:1.6rem;font-weight:800;color:var(--admin-text);">{{ number_format($inventoryStatus['total_bandwidth_tb'] - $inventoryStatus['bandwidth_used_tb'], 1) }} TiB</div>
                        <span class="text-muted" style="font-size:0.85rem;">{{ $inventoryStatus['bandwidth_used_tb'] }} TiB Used</span>
                    </div>
                    <div style="width:100%;height:8px;background:var(--admin-border);border-radius:4px;overflow:hidden;">
                        <div style="width:{{ min($inventoryStatus['bandwidth_percent'], 100) }}%;height:100%;background:linear-gradient(90deg, var(--admin-accent), var(--admin-accent-strong));border-radius:4px;"></div>
                    </div>
                    <div class="text-muted" style="font-size:0.75rem;margin-top:6px;text-align:right;">{{ $inventoryStatus['bandwidth_percent'] }}% Used</div>
                </div>
            </div>
        </div>

        <div class="card" style="margin:0;background:var(--admin-surface-2);">
            <div class="card-body">
                <div style="font-size:0.9rem;font-weight:700;color:var(--admin-text);margin-bottom:16px;">
                    <i class="fas fa-boxes" style="margin-right:8px;color:var(--admin-success);"></i>Available Stock (VPS you can sell)
                </div>
                @if(count($inventoryStatus['sellable_plans']) > 0)
                    <div class="grid grid-cols-4" style="grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));">
                        @foreach($inventoryStatus['sellable_plans'] as $item)
                        <div class="card" style="margin:0;border-color:rgba(34,197,94,0.2);">
                            <div class="card-body" style="padding:14px;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div>
                                        <div style="font-size:0.85rem;font-weight:700;color:var(--admin-success);">{{ $item['plan']->name }}</div>
                                        <div class="text-muted" style="font-size:0.7rem;">{{ $item['plan']->memory_gb }}GB RAM | {{ $item['plan']->disk_gb }}GB SSD</div>
                                    </div>
                                    <div style="text-align:center;">
                                        <div style="font-size:1.5rem;font-weight:800;color:var(--admin-success);">{{ $item['can_sell'] }}</div>
                                        <div class="text-muted" style="font-size:0.65rem;text-transform:uppercase;">Available</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="padding:24px;">
                        <i class="fas fa-exclamation-circle" style="color:var(--admin-danger);"></i>
                        <div style="font-weight:600;color:var(--admin-danger);">No VPS plans can fit in remaining resources!</div>
                        <div class="text-muted" style="font-size:0.8rem;margin-top:6px;">Consider upgrading your server or optimizing existing VMs.</div>
                    </div>
                @endif
            </div>
        </div>

        @if($inventoryStatus['low_stock'])
        <div class="alert alert-error mt-4">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Low Stock Alert:</strong> Your server resources are running low.
                @if($inventoryStatus['ram_percent'] > 85) RAM is at {{ $inventoryStatus['ram_percent'] }}%. @endif
                @if($inventoryStatus['disk_percent'] > 85) Disk is at {{ $inventoryStatus['disk_percent'] }}%. @endif
                Consider ordering a new dedicated server soon.
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Quick Stats --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-users"></i></div></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value">{{ number_format($stats['users']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon green"><i class="fas fa-shopping-cart"></i></div></div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value">{{ number_format($stats['orders']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon blue"><i class="fas fa-dollar-sign"></i></div></div>
        <div class="stat-label">Revenue</div>
        <div class="stat-value">{{ $currencySymbol }}{{ number_format($stats['revenue']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon yellow"><i class="fas fa-file-contract"></i></div></div>
        <div class="stat-label">Agreements</div>
        <div class="stat-value">{{ number_format($stats['agreements']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon purple"><i class="fas fa-project-diagram"></i></div></div>
        <div class="stat-label">Projects</div>
        <div class="stat-value">{{ number_format($stats['projects']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-header"><div class="stat-icon red"><i class="fas fa-ticket-alt"></i></div></div>
        <div class="stat-label">Open Tickets</div>
        <div class="stat-value">{{ number_format($stats['tickets']) }}</div>
    </div>
</div>

{{-- Recent Orders --}}
<div class="card mb-6">
    <div class="card-header">
        <div class="card-title"><i class="fas fa-shopping-cart"></i>Recent Orders</div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Order ID</th><th>Customer</th><th>Service</th><th>Amount</th><th>Status</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                    <td>{{ $order->service_name ?? 'N/A' }}</td>
                    <td>{{ $currencySymbol }}{{ number_format($order->amount, 2) }}</td>
                    <td>
                        <span class="badge badge-{{ $order->status === 'paid' ? 'success' : ($order->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </td>
                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="empty-state"><i class="fas fa-inbox"></i><div>No orders yet</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Two Column --}}
<div class="grid grid-cols-2" style="grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));">
    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-user-plus"></i>New Users</div>
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
                <tbody>
                    @forelse($recentUsers as $user)
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg, var(--admin-accent), var(--admin-accent-strong));display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:0.75rem;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                {{ $user->name }}
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-state"><i class="fas fa-inbox"></i><div>No users yet</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><i class="fas fa-ticket-alt"></i>Pending Tickets</div>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr><th>Subject</th><th>User</th><th>Priority</th></tr></thead>
                <tbody>
                    @forelse($pendingTickets as $ticket)
                    <tr>
                        <td>{{ Str::limit($ticket->subject, 30) }}</td>
                        <td>{{ $ticket->user->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'info') }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-state"><i class="fas fa-inbox"></i><div>No pending tickets</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
