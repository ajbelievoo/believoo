@extends('layouts.admin')

@section('title', 'Datacenter Management - BelieVoo')

@section('content')
<style>
    .datacenter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }
    .node-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 24px;
        transition: all 0.2s;
        position: relative;
        overflow: hidden;
    }
    .node-card:hover {
        border-color: rgba(0, 183, 255, 0.3);
        transform: translateY(-2px);
    }
    .node-card.active {
        border-left: 4px solid #22c55e;
    }
    .node-card.coming_soon {
        border-left: 4px solid #8b9bb4;
        opacity: 0.8;
    }
    .node-card.maintenance {
        border-left: 4px solid #f59e0b;
    }
    .node-card.offline {
        border-left: 4px solid #ef4444;
    }
    .node-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .node-flag {
        font-size: 2rem;
    }
    .node-title {
        flex: 1;
    }
    .node-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
    }
    .node-location {
        font-size: 0.8rem;
        color: #8b9bb4;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-badge.active {
        background: rgba(34, 197, 94, 0.2);
        color: #22c55e;
    }
    .status-badge.coming_soon {
        background: rgba(139, 155, 180, 0.2);
        color: #8b9bb4;
    }
    .status-badge.maintenance {
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
    }
    .status-badge.offline {
        background: rgba(239, 68, 68, 0.2);
        color: #ef4444;
    }
    .node-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
        margin: 16px 0;
        padding: 16px;
        background: rgba(0,0,0,0.2);
        border-radius: 12px;
    }
    .stat-item {
        text-align: center;
    }
    .stat-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #00b7ff;
    }
    .stat-label {
        font-size: 0.7rem;
        color: #8b9bb4;
        text-transform: uppercase;
    }
    .capacity-bar {
        width: 100%;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 4px;
    }
    .capacity-fill {
        height: 100%;
        background: linear-gradient(90deg, #22c55e, #16a34a);
        border-radius: 3px;
        transition: width 0.3s;
    }
    .capacity-fill.high {
        background: linear-gradient(90deg, #f59e0b, #d97706);
    }
    .capacity-fill.full {
        background: linear-gradient(90deg, #ef4444, #dc2626);
    }
    .node-actions {
        display: flex;
        gap: 8px;
        margin-top: 16px;
    }
    .btn-action {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        text-decoration: none;
    }
    .btn-edit {
        background: rgba(0, 183, 255, 0.15);
        color: #00b7ff;
    }
    .btn-edit:hover {
        background: rgba(0, 183, 255, 0.25);
    }
    .btn-activate {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
    }
    .btn-activate:hover {
        background: rgba(34, 197, 94, 0.25);
    }
    .stats-overview {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        padding: 20px;
        text-align: center;
    }
    .stat-card-icon {
        font-size: 2rem;
        margin-bottom: 8px;
    }
    .stat-card-value {
        font-size: 2rem;
        font-weight: 800;
        color: #fff;
    }
    .stat-card-label {
        font-size: 0.8rem;
        color: #8b9bb4;
        margin-top: 4px;
    }
    .default-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 4px 10px;
        background: linear-gradient(135deg, #00b7ff, #0066cc);
        color: #fff;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-globe" style="margin-right: 12px; color: #00b7ff;"></i>
        Datacenter Management
    </h1>
    <div class="page-actions">
        <a href="{{ route('admin.datacenter.create') }}" class="btn btn-primary">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>
            Add New Node
        </a>
    </div>
</div>

<!-- Stats Overview -->
<div class="stats-overview">
    <div class="stat-card">
        <div class="stat-card-icon">🌍</div>
        <div class="stat-card-value">{{ $stats['total_nodes'] }}</div>
        <div class="stat-card-label">Total Nodes</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">🟢</div>
        <div class="stat-card-value" style="color: #22c55e;">{{ $stats['active_nodes'] }}</div>
        <div class="stat-card-label">Active</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">⏳</div>
        <div class="stat-card-value" style="color: #8b9bb4;">{{ $stats['coming_soon'] }}</div>
        <div class="stat-card-label">Coming Soon</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">🖥️</div>
        <div class="stat-card-value">{{ $stats['total_vms'] }}</div>
        <div class="stat-card-label">Total VMs</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon">📊</div>
        <div class="stat-card-value" style="color: #00b7ff;">{{ $stats['available_slots'] }}</div>
        <div class="stat-card-label">Available Slots</div>
    </div>
</div>

<!-- Bulk Actions -->
@if($stats['coming_soon'] > 0)
<div style="background: rgba(0, 183, 255, 0.1); border: 1px solid rgba(0, 183, 255, 0.3); border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
    <i class="fas fa-info-circle" style="color: #00b7ff; font-size: 1.5rem;"></i>
    <div style="flex: 1;">
        <div style="font-weight: 600; color: #fff;">{{ $stats['coming_soon'] }} nodes are "Coming Soon"</div>
        <div style="font-size: 0.85rem; color: #8b9bb4;">Activate them when your servers are ready</div>
    </div>
    <form action="{{ route('admin.datacenter.bulk-activate') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-success" style="padding: 10px 20px;">
            <i class="fas fa-rocket" style="margin-right: 6px;"></i>
            Activate All
        </button>
    </form>
</div>
@endif

<!-- Nodes Grid -->
<div class="datacenter-grid">
    @forelse($nodes as $node)
    <div class="node-card {{ $node->status }}">
        @if($node->is_default)
        <div class="default-badge">
            <i class="fas fa-star" style="margin-right: 4px;"></i>
            DEFAULT
        </div>
        @endif
        
        <div class="node-header">
            <div class="node-flag">{{ $node->flag_emoji ?? '🌍' }}</div>
            <div class="node-title">
                <div class="node-name">{{ $node->display_name }}</div>
                <div class="node-location">
                    {{ $node->city }}, {{ $node->country_code }} 
                    @if($node->latency_hint)
                        • {{ $node->latency_hint }}
                    @endif
                </div>
            </div>
            <div class="status-badge {{ $node->status }}">
                {{ str_replace('_', ' ', $node->status) }}
            </div>
        </div>
        
        <div class="node-stats">
            <div class="stat-item">
                <div class="stat-value">{{ $node->current_vms }}</div>
                <div class="stat-label">VMs</div>
                <div class="capacity-bar">
                    @php
                        $percent = $node->max_vms > 0 ? ($node->current_vms / $node->max_vms) * 100 : 0;
                        $fillClass = $percent > 90 ? 'full' : ($percent > 70 ? 'high' : '');
                    @endphp
                    <div class="capacity-fill {{ $fillClass }}" style="width: {{ $percent }}%"></div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $node->total_cpu_cores }}</div>
                <div class="stat-label">CPU Cores</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ number_format($node->total_memory_bytes / 1024 / 1024 / 1024) }}</div>
                <div class="stat-label">GB RAM</div>
            </div>
        </div>
        
        <div style="font-size: 0.8rem; color: #8b9bb4; margin-bottom: 8px;">
            <i class="fas fa-network-wired" style="margin-right: 6px;"></i>
            {{ $node->hostname }}:{{ $node->port }}
        </div>
        
        @if($node->last_synced_at)
        <div style="font-size: 0.75rem; color: #6b7280; margin-bottom: 12px;">
            <i class="fas fa-sync" style="margin-right: 4px;"></i>
            Last synced: {{ $node->last_synced_at->diffForHumans() }}
        </div>
        @endif
        
        <div class="node-actions">
            <a href="{{ route('admin.datacenter.edit', $node) }}" class="btn-action btn-edit">
                <i class="fas fa-edit" style="margin-right: 4px;"></i>
                Edit
            </a>
            @if($node->status === 'coming_soon')
            <form action="{{ route('admin.datacenter.update', $node) }}" method="POST" style="flex: 1;">
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
                <button type="submit" class="btn-action btn-activate" style="width: 100%;">
                    <i class="fas fa-rocket" style="margin-right: 4px;"></i>
                    Activate
                </button>
            </form>
            @else
            <a href="{{ route('admin.datacenter.show', $node) }}" class="btn-action btn-edit">
                <i class="fas fa-eye" style="margin-right: 4px;"></i>
                View
            </a>
            @endif
        </div>
    </div>
    @empty
    <div style="grid-column: 1 / -1; text-align: center; padding: 60px; color: #8b9bb4;">
        <i class="fas fa-server" style="font-size: 4rem; margin-bottom: 20px; color: #4b5563;"></i>
        <h3 style="margin-bottom: 10px;">No datacenter nodes configured</h3>
        <p>Add your first Proxmox node to get started</p>
        <a href="{{ route('admin.datacenter.create') }}" class="btn btn-primary" style="margin-top: 20px;">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>
            Add Node
        </a>
    </div>
    @endforelse
</div>

<div style="margin-top: 30px;">
    {{ $nodes->links() }}
</div>
@endsection
