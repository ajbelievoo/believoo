@extends('layouts.admin')

@section('title', 'Node Details - ' . $node->display_name)

@section('content')
<style>
    .node-detail-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-top: 24px;
    }
    .detail-card {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        font-size: 0.85rem;
        color: #8b9bb4;
    }
    .info-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }
    .status-badge-large {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status-active {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
    }
    .status-coming-soon {
        background: rgba(139, 155, 180, 0.2);
        color: #8b9bb4;
    }
    .resource-bar {
        width: 100%;
        height: 8px;
        background: rgba(255,255,255,0.1);
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }
    .resource-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s;
    }
    .vm-list {
        list-style: none;
        padding: 0;
    }
    .vm-item {
        display: flex;
        align-items: center;
        padding: 12px;
        background: rgba(255,255,255,0.03);
        border-radius: 10px;
        margin-bottom: 8px;
    }
    .vm-status {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 12px;
    }
    .vm-status.running { background: #22c55e; }
    .vm-status.stopped { background: #ef4444; }
    .vm-info {
        flex: 1;
    }
    .vm-name {
        font-weight: 600;
        color: #fff;
    }
    .vm-specs {
        font-size: 0.8rem;
        color: #8b9bb4;
    }
    .action-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 8px;
    }
    .btn-sync {
        background: linear-gradient(135deg, #00b7ff, #0066cc);
        color: white;
    }
    .btn-edit {
        background: rgba(255,255,255,0.1);
        color: #fff;
    }
    .btn-delete {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-box {
        background: rgba(255,255,255,0.03);
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .stat-number {
        font-size: 2rem;
        font-weight: 800;
        color: #00b7ff;
    }
    .stat-label-sm {
        font-size: 0.75rem;
        color: #8b9bb4;
        text-transform: uppercase;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <span style="font-size: 2rem; margin-right: 12px;">{{ $node->flag_emoji }}</span>
        {{ $node->display_name }}
        <span class="status-badge-large {{ $node->status === 'active' ? 'status-active' : 'status-coming-soon' }}" style="margin-left: 16px;">
            {{ $node->status === 'active' ? '🟢 Active' : '⏳ Coming Soon' }}
        </span>
        @if($apiStats['online'] ?? false)
            <span class="status-badge-large status-active" style="margin-left: 8px; background: rgba(34, 197, 94, 0.2);">
                🌐 Online
            </span>
        @else
            <span class="status-badge-large" style="margin-left: 8px; background: rgba(239, 68, 68, 0.2); color: #ef4444;">
                🔴 Offline
            </span>
        @endif
    </h1>
    <div class="page-actions">
        {{-- FORCE SYNC BUTTON - Prominent --}}
        <form action="{{ route('admin.datacenter.sync', $node) }}" method="POST" style="display: inline;" id="force-sync-form">
            @csrf
            <button type="submit" class="action-btn btn-sync" id="force-sync-btn" style="
                background: linear-gradient(135deg, #22c55e, #16a34a);
                box-shadow: 0 4px 14px rgba(34, 197, 94, 0.4);
                font-size: 0.9rem;
                padding: 12px 24px;
            " title="Force fetch real hardware specs from Proxmox API">
                <i class="fas fa-bolt" style="margin-right: 8px;"></i>
                <strong>FORCE SYNC</strong>
                @if($node->last_synced_at)
                    <span style="font-size: 0.7rem; opacity: 0.9; display: block; margin-top: 2px; font-weight: 400;">
                        Last: {{ $node->last_synced_at->diffForHumans() }}
                    </span>
                @else
                    <span style="font-size: 0.7rem; opacity: 0.9; display: block; margin-top: 2px; font-weight: 400;">
                        Never synced - Click to fetch real specs!
                    </span>
                @endif
            </button>
        </form>
        <a href="{{ route('admin.datacenter.edit', $node) }}" class="action-btn btn-edit">
            <i class="fas fa-edit" style="margin-right: 6px;"></i>
            Edit
        </a>
        <form action="{{ route('admin.datacenter.destroy', $node) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this node?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="action-btn btn-delete">
                <i class="fas fa-trash" style="margin-right: 6px;"></i>
                Delete
            </button>
        </form>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-box">
        <div class="stat-number">{{ $node->current_vms }}</div>
        <div class="stat-label-sm">Active VMs</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">{{ $node->availableSlots() }}</div>
        <div class="stat-label-sm">Available Slots</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">{{ $apiStats['active_vms'] ?? 0 }}</div>
        <div class="stat-label-sm">Running VMs</div>
    </div>
</div>

<div class="node-detail-grid">
    <div class="left-column">
        <div class="detail-card">
            <div class="card-title">
                <i class="fas fa-info-circle" style="margin-right: 8px; color: #00b7ff;"></i>
                Node Information
            </div>
            <div class="info-row">
                <span class="info-label">Node ID</span>
                <span class="info-value">{{ $node->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Display Name</span>
                <span class="info-value">{{ $node->display_name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Hostname</span>
                <span class="info-value">{{ $node->hostname }}:{{ $node->port }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Location</span>
                <span class="info-value">{{ $node->city }}, {{ $node->country_code }} {{ $node->flag_emoji }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Region</span>
                <span class="info-value">{{ $node->region }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Provider</span>
                <span class="info-value">{{ $node->provider_name ?? 'Custom' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Latency Hint</span>
                <span class="info-value">{{ $node->latency_hint ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Last Synced</span>
                <span class="info-value" style="color: {{ $apiStats['online'] ? '#22c55e' : '#ef4444' }};">
                    @if($apiStats['online'])
                        <i class="fas fa-satellite-dish" style="margin-right: 4px; animation: pulse 2s infinite;"></i>
                        <strong>Live Now</strong>
                        <span style="font-size: 0.75rem; color: #22c55e; margin-left: 8px;">(Real-time API)</span>
                    @elseif($node->last_synced_at)
                        <i class="fas fa-history" style="margin-right: 4px;"></i>
                        {{ $node->last_synced_at->diffForHumans() }}
                        <span style="font-size: 0.75rem; color: #f59e0b; margin-left: 8px;">(Stale data)</span>
                    @else
                        <i class="fas fa-exclamation-triangle" style="margin-right: 4px;"></i>
                        <strong>Never synced</strong>
                        <span style="font-size: 0.75rem; color: #ef4444; margin-left: 8px;">Click FORCE SYNC!</span>
                    @endif
                </span>
            </div>
            @if($apiStats['uptime'] > 0)
            <div class="info-row">
                <span class="info-label">Node Uptime</span>
                <span class="info-value">{{ floor($apiStats['uptime'] / 86400) }} days {{ floor(($apiStats['uptime'] % 86400) / 3600) }} hours</span>
            </div>
            @endif
        </div>

        <div class="detail-card">
            <div class="card-title">
                <i class="fas fa-server" style="margin-right: 8px; color: #8b5cf6;"></i>
                Hardware Resources
                @if($apiStats['online'])
                    <span style="float: right; font-size: 0.7rem; background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 4px 10px; border-radius: 12px;">
                        <i class="fas fa-satellite-dish" style="margin-right: 4px;"></i>Live from API
                    </span>
                @endif
            </div>
            <div class="info-row">
                <span class="info-label">Total CPU Cores</span>
                <span class="info-value">
                    {{ $apiStats['cpu_cores'] ?? $node->total_cpu_cores }} cores
                    @if($apiStats['online'] && $apiStats['cpu_cores'] != $node->total_cpu_cores)
                        <span style="font-size: 0.75rem; color: #22c55e; margin-left: 4px;">(Updated)</span>
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Memory</span>
                <span class="info-value">
                    @if(!empty($apiStats['ram_total']))
                        {{ number_format($apiStats['ram_total'] / 1024 / 1024 / 1024) }} GB
                        @if($apiStats['online'] && $apiStats['ram_total'] != $node->total_memory_bytes)
                            <span style="font-size: 0.75rem; color: #22c55e; margin-left: 4px;">(Updated)</span>
                        @endif
                    @else
                        {{ number_format($node->total_memory_bytes / 1024 / 1024 / 1024) }} GB
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Disk</span>
                <span class="info-value">
                    @if(!empty($apiStats['disk_total']))
                        {{ number_format($apiStats['disk_total'] / 1024 / 1024 / 1024 / 1024, 2) }} TB
                        @if($apiStats['online'] && $apiStats['disk_total'] != $node->total_disk_bytes)
                            <span style="font-size: 0.75rem; color: #22c55e; margin-left: 4px;">(Updated)</span>
                        @endif
                    @else
                        {{ number_format($node->total_disk_bytes / 1024 / 1024 / 1024 / 1024, 2) }} TB
                    @endif
                </span>
            </div>
            <div class="info-row">
                <span class="info-label">Max VMs</span>
                <span class="info-value">{{ $node->max_vms }}</span>
            </div>
        </div>

        <div class="detail-card">
            <div class="card-title">
                <i class="fas fa-chart-pie" style="margin-right: 8px; color: #22c55e;"></i>
                Real-Time Usage
                @if($apiStats['online'])
                    <span style="float: right; font-size: 0.7rem; background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 4px 10px; border-radius: 12px; animation: pulse 2s infinite;">
                        ● Live
                    </span>
                @endif
            </div>
            <div class="info-row">
                <span class="info-label">CPU Usage</span>
                <span class="info-value" style="color: {{ $usage['cpu_percent'] > 80 ? '#ef4444' : ($usage['cpu_percent'] > 60 ? '#f59e0b' : '#22c55e') }}">
                    {{ number_format($usage['cpu_percent'], 1) }}%
                </span>
            </div>
            <div class="resource-bar">
                <div class="resource-fill" style="width: {{ min($usage['cpu_percent'], 100) }}%; background: linear-gradient(90deg, {{ $usage['cpu_percent'] > 80 ? '#ef4444' : ($usage['cpu_percent'] > 60 ? '#f59e0b' : '#22c55e') }}, {{ $usage['cpu_percent'] > 80 ? '#dc2626' : ($usage['cpu_percent'] > 60 ? '#d97706' : '#16a34a') }});"></div>
            </div>
            <div class="info-row" style="margin-top: 16px;">
                <span class="info-label">Memory Usage</span>
                <span class="info-value" style="color: {{ $usage['ram_percent'] > 80 ? '#ef4444' : ($usage['ram_percent'] > 60 ? '#f59e0b' : '#22c55e') }}">
                    {{ number_format($usage['ram_percent'], 1) }}%
                    @if(!empty($apiStats['ram_used']))
                        <span style="font-size: 0.8rem; color: #8b9bb4;">({{ number_format($apiStats['ram_used'] / 1024 / 1024 / 1024, 1) }} GB used)</span>
                    @endif
                </span>
            </div>
            <div class="resource-bar">
                <div class="resource-fill" style="width: {{ min($usage['ram_percent'], 100) }}%; background: linear-gradient(90deg, {{ $usage['ram_percent'] > 80 ? '#ef4444' : ($usage['ram_percent'] > 60 ? '#f59e0b' : '#8b5cf6') }}, {{ $usage['ram_percent'] > 80 ? '#dc2626' : ($usage['ram_percent'] > 60 ? '#d97706' : '#7c3aed') }});"></div>
            </div>
            <div class="info-row" style="margin-top: 16px;">
                <span class="info-label">Disk Usage</span>
                <span class="info-value" style="color: {{ $usage['disk_percent'] > 80 ? '#ef4444' : ($usage['disk_percent'] > 60 ? '#f59e0b' : '#22c55e') }}">
                    {{ number_format($usage['disk_percent'], 1) }}%
                    @if(!empty($apiStats['disk_used']))
                        <span style="font-size: 0.8rem; color: #8b9bb4;">({{ number_format($apiStats['disk_used'] / 1024 / 1024 / 1024 / 1024, 2) }} TB used)</span>
                    @endif
                </span>
            </div>
            <div class="resource-bar">
                <div class="resource-fill" style="width: {{ min($usage['disk_percent'], 100) }}%; background: linear-gradient(90deg, {{ $usage['disk_percent'] > 80 ? '#ef4444' : ($usage['disk_percent'] > 60 ? '#f59e0b' : '#f59e0b') }}, {{ $usage['disk_percent'] > 80 ? '#dc2626' : ($usage['disk_percent'] > 60 ? '#d97706' : '#d97706') }});"></div>
            </div>
            @if(!empty($apiStats['loadavg']))
            <div class="info-row" style="margin-top: 16px;">
                <span class="info-label">Load Average</span>
                <span class="info-value" style="font-size: 0.8rem;">
                    {{ number_format($apiStats['loadavg'][0] ?? 0, 2) }} / {{ number_format($apiStats['loadavg'][1] ?? 0, 2) }} / {{ number_format($apiStats['loadavg'][2] ?? 0, 2) }}
                </span>
            </div>
            @endif
        </div>
    </div>

    <div class="right-column">
        <div class="detail-card">
            <div class="card-title">
                <i class="fas fa-cloud" style="margin-right: 8px; color: #00b7ff;"></i>
                VMs on This Node
            </div>
            @if($node->vms && $node->vms->count() > 0)
                <ul class="vm-list">
                    @foreach($node->vms as $vm)
                    <li class="vm-item">
                        <div class="vm-status {{ $vm->status }}"></div>
                        <div class="vm-info">
                            <div class="vm-name">{{ $vm->name ?? 'VM-' . $vm->vmid }}</div>
                            <div class="vm-specs">{{ $vm->cpu_cores }} CPU • {{ $vm->memory_mb }} MB RAM</div>
                        </div>
                        <span style="font-size: 0.75rem; color: #8b9bb4;">#{{ $vm->vmid }}</span>
                    </li>
                    @endforeach
                </ul>
            @else
                <div style="text-align: center; padding: 40px; color: #8b9bb4;">
                    <i class="fas fa-server" style="font-size: 2rem; margin-bottom: 12px; opacity: 0.5;"></i>
                    <p>No VMs on this node yet</p>
                </div>
            @endif
        </div>

        <div class="detail-card">
            <div class="card-title">
                <i class="fas fa-network-wired" style="margin-right: 8px; color: #ec4899;"></i>
                Network Configuration
            </div>
            <div class="info-row">
                <span class="info-label">Gateway</span>
                <span class="info-value">{{ $node->network_gateway ?? 'Not configured' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Subnet</span>
                <span class="info-value">{{ $node->network_subnet ?? 'Not configured' }}</span>
            </div>
        </div>

        @if($node->status === 'coming_soon')
        <div class="detail-card" style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3);">
            <div class="card-title" style="color: #f59e0b;">
                <i class="fas fa-rocket" style="margin-right: 8px;"></i>
                Launch Status
            </div>
            <p style="color: #8b9bb4; margin-bottom: 16px;">
                This datacenter is scheduled for launch. Activate it when your physical server is ready.
            </p>
            <form action="{{ route('admin.datacenter.update', $node) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="display_name" value="{{ $node->display_name }}">
                <input type="hidden" name="hostname" value="{{ $node->hostname }}">
                <input type="hidden" name="port" value="{{ $node->port }}">
                <input type="hidden" name="status" value="active">
                <input type="hidden" name="max_vms" value="{{ $node->max_vms }}">
                <input type="hidden" name="total_cpu_cores" value="{{ $node->total_cpu_cores }}">
                <input type="hidden" name="total_memory_gb" value="{{ $node->total_memory_bytes / 1024 / 1024 / 1024 }}">
                <input type="hidden" name="total_disk_gb" value="{{ $node->total_disk_bytes / 1024 / 1024 / 1024 }}">
                <button type="submit" class="action-btn btn-sync" style="width: 100%;">
                    <i class="fas fa-rocket" style="margin-right: 6px;"></i>
                    Activate Node Now
                </button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
