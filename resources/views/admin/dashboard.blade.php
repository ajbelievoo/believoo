@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back! Here's what's happening today.</p>
</div>

{{-- Quick Actions --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 28px;">
    <a href="{{ route('admin.bconnect.index') }}" class="btn btn-primary" style="text-align: center; justify-content: center;"><i class="fas fa-network-wired" style="margin-right: 6px;"></i>B-CONNECT</a>
    <a href="{{ route('admin.ghc.index') }}" class="btn btn-primary" style="text-align: center; justify-content: center;"><i class="fas fa-cloud" style="margin-right: 6px;"></i>GHC</a>
    <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary" style="text-align: center; justify-content: center;"><i class="fas fa-file-invoice" style="margin-right: 6px;"></i>Invoices</a>
    <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary" style="text-align: center; justify-content: center;"><i class="fas fa-shopping-cart" style="margin-right: 6px;"></i>Orders</a>
    <a href="{{ route('admin.proxmox.vms.index') }}" class="btn btn-secondary" style="text-align: center; justify-content: center;"><i class="fas fa-server" style="margin-right: 6px;"></i>VPS</a>
    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary" style="text-align: center; justify-content: center;"><i class="fas fa-cog" style="margin-right: 6px;"></i>Settings</a>
</div>

{{-- Resource Alert Banner --}}
@if($resourceAlert)
<div class="resource-alert" style="margin-bottom: 24px; padding: 16px 20px; background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(239, 68, 68, 0.15) 100%); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 12px; display: flex; align-items: center; gap: 12px;">
    <div style="width: 40px; height: 40px; background: rgba(245, 158, 11, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
        <i class="fas {{ $resourceAlert['icon'] }}" style="color: #f59e0b; font-size: 1.1rem;"></i>
    </div>
    <div style="flex: 1;">
        <div style="font-weight: 600; color: #f59e0b; margin-bottom: 2px;">Resource Alert</div>
        <div style="color: rgba(255,255,255,0.8); font-size: 0.9rem;">{{ $resourceAlert['message'] }}</div>
    </div>
    <a href="{{ route('admin.proxmox.vms.index') }}" class="btn btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;">
        <i class="fas fa-server" style="margin-right: 6px;"></i>Manage VMs
    </a>
</div>
@endif

{{-- Resource Monitoring Dashboard --}}
<div class="data-table" style="margin-bottom: 32px;">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3 class="table-title">
            <i class="fas fa-server" style="margin-right: 10px; color: #00b7ff;"></i>
            Resource Monitoring
            @if($proxmoxResources['available'])
                <span style="font-size: 0.7rem; margin-left: 8px; padding: 4px 10px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border-radius: 20px;">
                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Online
                </span>
            @else
                <span style="font-size: 0.7rem; margin-left: 8px; padding: 4px 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 20px;">
                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Offline
                </span>
            @endif
        </h3>
        <span style="font-size: 0.75rem; color: #8b9bb4;">
            <i class="fas fa-network-wired" style="margin-right: 4px;"></i>
            Node: {{ config('proxmox.node', 'ns548195') }}
        </span>
    </div>
    
    <div style="padding: 24px;">
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px;">
            {{-- CPU Usage --}}
            <div class="resource-metric-card" style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(0, 183, 255, 0.2); border-top: 3px solid #00b7ff;">
                <div style="font-size: 0.75rem; color: #00b7ff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                    <i class="fas fa-microchip" style="margin-right: 4px;"></i>CPU Usage
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #00b7ff; margin-bottom: 8px;">
                    {{ $proxmoxResources['cpu_percent'] }}%
                </div>
                <div style="font-size: 0.8rem; color: #8b9bb4;">
                    {{ $proxmoxResources['cpu_cores'] }} Cores
                </div>
                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin-top: 12px;">
                    <div style="width: {{ $proxmoxResources['cpu_percent'] }}%; height: 100%; background: linear-gradient(90deg, #00b7ff, #0066cc); border-radius: 3px; transition: width 0.5s ease;"></div>
                </div>
            </div>

            {{-- RAM Usage --}}
            <div class="resource-metric-card" style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(139, 92, 246, 0.2); border-top: 3px solid #8b5cf6;">
                <div style="font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                    <i class="fas fa-memory" style="margin-right: 4px;"></i>RAM Usage
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #8b5cf6; margin-bottom: 8px; {{ $proxmoxResources['ram_percent'] > 85 ? 'color: #ef4444;' : '' }}">
                    {{ $proxmoxResources['ram_percent'] }}%
                </div>
                <div style="font-size: 0.8rem; color: #8b9bb4;">
                    @if($proxmoxResources['ram_total'] > 0)
                        {{ round($proxmoxResources['ram_used'] / 1024 / 1024 / 1024, 1) }} / {{ round($proxmoxResources['ram_total'] / 1024 / 1024 / 1024, 1) }} GB
                    @else
                        N/A
                    @endif
                </div>
                {{-- System Overhead Label --}}
                @if($totalVps == 0 && $proxmoxResources['ram_percent'] > 0 && $proxmoxResources['ram_percent'] < 10)
                <div style="font-size: 0.65rem; color: #6b7280; margin-top: 6px;">
                    <i class="fas fa-info-circle" style="color: #f59e0b; margin-right: 2px;"></i>
                    Base System Overhead
                </div>
                @endif
                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin-top: 12px;">
                    <div style="width: {{ $proxmoxResources['ram_percent'] }}%; height: 100%; background: {{ $proxmoxResources['ram_percent'] > 85 ? 'linear-gradient(90deg, #ef4444, #dc2626)' : 'linear-gradient(90deg, #8b5cf6, #6d28d9)' }}; border-radius: 3px; transition: width 0.5s ease;"></div>
                </div>
            </div>

            {{-- Disk Usage --}}
            <div class="resource-metric-card" style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(245, 158, 11, 0.2); border-top: 3px solid #f59e0b;">
                <div style="font-size: 0.75rem; color: #f59e0b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                    <i class="fas fa-hdd" style="margin-right: 4px;"></i>Disk Usage
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #f59e0b; margin-bottom: 8px;">
                    {{ $proxmoxResources['disk_percent'] }}%
                </div>
                <div style="font-size: 0.8rem; color: #8b9bb4;">
                    @if($proxmoxResources['disk_total'] > 0)
                        {{ round($proxmoxResources['disk_used'] / 1024 / 1024 / 1024, 1) }} / {{ round($proxmoxResources['disk_total'] / 1024 / 1024 / 1024, 1) }} GB
                    @else
                        N/A
                    @endif
                </div>
                <div style="width: 100%; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; margin-top: 12px;">
                    <div style="width: {{ $proxmoxResources['disk_percent'] }}%; height: 100%; background: linear-gradient(90deg, #f59e0b, #d97706); border-radius: 3px; transition: width 0.5s ease;"></div>
                </div>
            </div>

            {{-- Total Domains --}}
            <div class="resource-metric-card" style="background: linear-gradient(145deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.05) 100%); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(34, 197, 94, 0.3); border-top: 3px solid #22c55e;">
                <div style="font-size: 0.75rem; color: #22c55e; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                    <i class="fas fa-globe" style="margin-right: 4px;"></i>Total Domains
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #22c55e; margin-bottom: 8px;">
                    {{ number_format($totalDomains) }}
                </div>
                <div style="font-size: 0.8rem; color: #8b9bb4;">
                    Registered
                </div>
                <a href="{{ route('admin.domain-providers.index') }}" style="display: inline-block; margin-top: 12px; font-size: 0.75rem; color: #22c55e; text-decoration: none;">
                    <i class="fas fa-arrow-right" style="margin-right: 4px;"></i>Manage
                </a>
            </div>

            {{-- Active VPS --}}
            <div class="resource-metric-card" style="background: linear-gradient(145deg, rgba(0, 183, 255, 0.15) 0%, rgba(0, 183, 255, 0.05) 100%); border-radius: 16px; padding: 20px; text-align: center; border: 1px solid rgba(0, 183, 255, 0.3); border-top: 3px solid #00b7ff;">
                <div style="font-size: 0.75rem; color: #00b7ff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                    <i class="fas fa-cloud" style="margin-right: 4px;"></i>Active VPS
                </div>
                <div style="font-size: 2.2rem; font-weight: 800; color: #00b7ff; margin-bottom: 8px;">
                    {{ number_format($activeVps) }}
                </div>
                <div style="font-size: 0.8rem; color: #8b9bb4;">
                    of {{ number_format($totalVps) }} Total
                </div>
                <a href="{{ route('admin.proxmox.vms.index') }}" style="display: inline-block; margin-top: 12px; font-size: 0.75rem; color: #00b7ff; text-decoration: none;">
                    <i class="fas fa-arrow-right" style="margin-right: 4px;"></i>Manage VMs
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Inventory Status Widget --}}
<div class="data-table" style="margin-bottom: 32px; {{ $inventoryStatus['low_stock'] ? 'border-color: rgba(239, 68, 68, 0.5);' : 'border-color: rgba(34, 197, 94, 0.3);' }}">
    <div class="table-header" style="display: flex; justify-content: space-between; align-items: center; {{ $inventoryStatus['low_stock'] ? 'background: rgba(239, 68, 68, 0.1);' : 'background: rgba(34, 197, 94, 0.05);' }}">
        <h3 class="table-title">
            <i class="fas fa-warehouse" style="margin-right: 10px; {{ $inventoryStatus['low_stock'] ? 'color: #ef4444;' : 'color: #22c55e;' }}"></i>
            Inventory Status
            @if($inventoryStatus['low_stock'])
                <span style="font-size: 0.7rem; margin-left: 8px; padding: 4px 10px; background: rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 20px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 0.7rem; margin-right: 4px;"></i>Low Stock
                </span>
            @else
                <span style="font-size: 0.7rem; margin-left: 8px; padding: 4px 10px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border-radius: 20px;">
                    <i class="fas fa-check-circle" style="font-size: 0.7rem; margin-right: 4px;"></i>In Stock
                </span>
            @endif
        </h3>
        <span style="font-size: 0.75rem; color: #8b9bb4;">
            <i class="fas fa-server" style="margin-right: 4px;"></i>
            Cloud Server: ns548195
        </span>
    </div>
    
    <div style="padding: 24px;">
        {{-- Resource Meters --}}
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
            {{-- RAM Inventory --}}
            <div style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%); border-radius: 12px; padding: 16px; border: 1px solid rgba(139, 92, 246, 0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; font-weight: 600;">
                        <i class="fas fa-memory" style="margin-right: 4px;"></i>RAM Inventory
                    </div>
                    <div style="font-size: 0.8rem; color: #8b9bb4;">{{ $inventoryStatus['total_ram_gb'] }} GB Total</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #fff;">{{ $inventoryStatus['remaining_ram_gb'] }} GB</div>
                    <div style="font-size: 0.9rem; color: #8b9bb4;">{{ $inventoryStatus['used_ram_gb'] }} GB Used</div>
                </div>
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                    <div style="width: {{ $inventoryStatus['ram_percent'] }}%; height: 100%; background: {{ $inventoryStatus['ram_percent'] > 85 ? 'linear-gradient(90deg, #ef4444, #dc2626)' : 'linear-gradient(90deg, #8b5cf6, #6d28d9)' }}; border-radius: 4px; transition: width 0.5s ease;"></div>
                </div>
                <div style="font-size: 0.75rem; color: #8b9bb4; margin-top: 6px; text-align: right;">{{ $inventoryStatus['ram_percent'] }}% Used</div>
            </div>

            {{-- Disk Inventory --}}
            <div style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%); border-radius: 12px; padding: 16px; border: 1px solid rgba(245, 158, 11, 0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 0.75rem; color: #f59e0b; text-transform: uppercase; font-weight: 600;">
                        <i class="fas fa-hdd" style="margin-right: 4px;"></i>Disk Inventory
                    </div>
                    <div style="font-size: 0.8rem; color: #8b9bb4;">{{ $inventoryStatus['total_disk_gb'] }} GB Total</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #fff;">{{ $inventoryStatus['remaining_disk_gb'] }} GB</div>
                    <div style="font-size: 0.9rem; color: #8b9bb4;">{{ $inventoryStatus['used_disk_gb'] }} GB Used</div>
                </div>
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                    <div style="width: {{ $inventoryStatus['disk_percent'] }}%; height: 100%; background: {{ $inventoryStatus['disk_percent'] > 85 ? 'linear-gradient(90deg, #ef4444, #dc2626)' : 'linear-gradient(90deg, #f59e0b, #d97706)' }}; border-radius: 4px; transition: width 0.5s ease;"></div>
                </div>
                <div style="font-size: 0.75rem; color: #8b9bb4; margin-top: 6px; text-align: right;">{{ $inventoryStatus['disk_percent'] }}% Used</div>
            </div>

            {{-- Cloud Bandwidth Meter --}}
            <div style="background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%); border-radius: 12px; padding: 16px; border: 1px solid rgba(0, 183, 255, 0.2);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 0.75rem; color: #00b7ff; text-transform: uppercase; font-weight: 600;">
                        <i class="fas fa-network-wired" style="margin-right: 4px;"></i>Cloud Bandwidth
                    </div>
                    <div style="font-size: 0.8rem; color: #8b9bb4;">{{ $inventoryStatus['total_bandwidth_tb'] }} TiB/Month</div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
                    <div style="font-size: 1.8rem; font-weight: 700; color: #fff;">{{ number_format($inventoryStatus['total_bandwidth_tb'] - $inventoryStatus['bandwidth_used_tb'], 1) }} TiB</div>
                    <div style="font-size: 0.9rem; color: #8b9bb4;">{{ $inventoryStatus['bandwidth_used_tb'] }} TiB Used</div>
                </div>
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden;">
                    <div style="width: {{ $inventoryStatus['bandwidth_percent'] }}%; height: 100%; background: linear-gradient(90deg, #00b7ff, #0066cc); border-radius: 4px; transition: width 0.5s ease;"></div>
                </div>
                <div style="font-size: 0.75rem; color: #8b9bb4; margin-top: 6px; text-align: right;">{{ $inventoryStatus['bandwidth_percent'] }}% Used</div>
            </div>
        </div>

        {{-- Sellable VPS Plans --}}
        <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 20px; border: 1px solid rgba(255,255,255,0.05);">
            <div style="font-size: 0.9rem; color: #fff; font-weight: 600; margin-bottom: 16px;">
                <i class="fas fa-boxes" style="margin-right: 8px; color: #22c55e;"></i>
                Available Stock (How many more VPS you can sell)
            </div>
            
            @if(count($inventoryStatus['sellable_plans']) > 0)
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">
                    @foreach($inventoryStatus['sellable_plans'] as $item)
                    <div style="background: linear-gradient(145deg, rgba(34, 197, 94, 0.1) 0%, rgba(34, 197, 94, 0.05) 100%); border-radius: 10px; padding: 14px; border: 1px solid rgba(34, 197, 94, 0.2); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-size: 0.85rem; color: #22c55e; font-weight: 600;">{{ $item['plan']->name }}</div>
                            <div style="font-size: 0.7rem; color: #8b9bb4;">{{ $item['plan']->memory_gb }}GB RAM | {{ $item['plan']->disk_gb }}GB SSD</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 1.6rem; font-weight: 800; color: #22c55e;">{{ $item['can_sell'] }}</div>
                            <div style="font-size: 0.65rem; color: #8b9bb4; text-transform: uppercase;">Available</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="text-align: center; padding: 30px; color: #ef4444;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <div style="font-size: 0.9rem;">No VPS plans can fit in remaining resources!</div>
                    <div style="font-size: 0.75rem; color: #8b9bb4; margin-top: 8px;">Consider upgrading your server or optimizing existing VMs.</div>
                </div>
            @endif
        </div>

        {{-- Low Stock Warning --}}
        @if($inventoryStatus['low_stock'])
        <div style="margin-top: 16px; padding: 12px 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-exclamation-triangle" style="color: #ef4444; font-size: 1.1rem;"></i>
            <div style="color: rgba(255,255,255,0.9); font-size: 0.85rem;">
                <strong>Low Stock Alert:</strong> Your server resources are running low. 
                @if($inventoryStatus['ram_percent'] > 85)
                    RAM is at {{ $inventoryStatus['ram_percent'] }}%. 
                @endif
                @if($inventoryStatus['disk_percent'] > 85)
                    Disk is at {{ $inventoryStatus['disk_percent'] }}%. 
                @endif
                Consider ordering a new dedicated server soon.
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Stats Grid --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="stat-label">Total Users</div>
        <div class="stat-value">{{ number_format($stats['users']) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon green">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value">{{ number_format($stats['orders']) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-label">Revenue</div>
        <div class="stat-value">{{ $currencySymbol }}{{ number_format($stats['revenue']) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon yellow">
                <i class="fas fa-file-contract"></i>
            </div>
        </div>
        <div class="stat-label">Agreements</div>
        <div class="stat-value">{{ number_format($stats['agreements']) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon blue">
                <i class="fas fa-project-diagram"></i>
            </div>
        </div>
        <div class="stat-label">Projects</div>
        <div class="stat-value">{{ number_format($stats['projects']) }}</div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon red">
                <i class="fas fa-ticket-alt"></i>
            </div>
        </div>
        <div class="stat-label">Open Tickets</div>
        <div class="stat-value">{{ number_format($stats['tickets']) }}</div>
    </div>
</div>

{{-- Recent Orders Table --}}
<div class="data-table" style="margin-bottom: 32px;">
    <div class="table-header">
        <h3 class="table-title">Recent Orders</h3>
        <div class="table-actions">
            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">View All</a>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Service</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
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
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                    No orders yet
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Two Column Layout --}}
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    {{-- Recent Users --}}
    <div class="data-table">
        <div class="table-header">
            <h3 class="table-title">New Users</h3>
            <div class="table-actions">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">View All</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Joined</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentUsers as $user)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #00b7ff, #0099ff); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            {{ $user->name }}
                        </div>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->created_at->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        No users yet
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pending Tickets --}}
    <div class="data-table">
        <div class="table-header">
            <h3 class="table-title">Pending Tickets</h3>
            <div class="table-actions">
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">View All</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>User</th>
                    <th>Priority</th>
                </tr>
            </thead>
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
                <tr>
                    <td colspan="3" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                        No pending tickets
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
