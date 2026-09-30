@extends('layouts.admin')

@section('title', "VM #{$vmid} Details")

@section('styles')
<style>
    .vm-status-banner {
        margin-bottom: 24px;
        padding: 24px;
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    .vm-status-banner.running {
        background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.05) 100%);
        border: 1px solid rgba(34, 197, 94, 0.4);
        box-shadow: 0 4px 20px rgba(34, 197, 94, 0.1);
    }
    .vm-status-banner.stopped {
        background: linear-gradient(135deg, rgba(139, 155, 180, 0.15) 0%, rgba(139, 155, 180, 0.05) 100%);
        border: 1px solid rgba(139, 155, 180, 0.4);
    }
    .metric-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    .metric-card.cpu { border-top: 3px solid #00b7ff; }
    .metric-card.memory { border-top: 3px solid #8b5cf6; }
    .metric-card.disk { border-top: 3px solid #f59e0b; }
    .metric-value {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 8px;
        background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .metric-card.cpu .metric-value {
        background: linear-gradient(135deg, #00b7ff 0%, #0066cc 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .metric-card.memory .metric-value {
        background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .metric-card.disk .metric-value {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    .progress-bar-bg {
        width: 100%;
        height: 6px;
        background: rgba(255,255,255,0.1);
        border-radius: 3px;
        overflow: hidden;
        margin-top: 12px;
    }
    .progress-bar-fill {
        height: 100%;
        border-radius: 3px;
        transition: width 0.5s ease;
    }
    .progress-bar-fill.cpu { background: linear-gradient(90deg, #00b7ff, #0066cc); }
    .progress-bar-fill.memory { background: linear-gradient(90deg, #8b5cf6, #6d28d9); }
    .progress-bar-fill.disk { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .info-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.8) 100%);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid rgba(255,255,255,0.05);
    }
    .info-card.highlight-green {
        background: linear-gradient(145deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.05) 100%);
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
    .info-card.highlight-blue {
        background: linear-gradient(145deg, rgba(0, 183, 255, 0.15) 0%, rgba(0, 183, 255, 0.05) 100%);
        border: 1px solid rgba(0, 183, 255, 0.3);
    }
    .info-card.highlight-purple {
        background: linear-gradient(145deg, rgba(139, 92, 246, 0.15) 0%, rgba(139, 92, 246, 0.05) 100%);
        border: 1px solid rgba(139, 92, 246, 0.3);
    }
    .info-card.highlight-orange {
        background: linear-gradient(145deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
        border: 1px solid rgba(245, 158, 11, 0.3);
    }
    .network-stat-card {
        background: linear-gradient(145deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.9) 100%);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid rgba(255,255,255,0.08);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .network-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .network-icon.inbound {
        background: linear-gradient(135deg, rgba(0, 183, 255, 0.2) 0%, rgba(0, 183, 255, 0.1) 100%);
        color: #00b7ff;
    }
    .network-icon.outbound {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(139, 92, 246, 0.1) 100%);
        color: #8b5cf6;
    }
    .pulse-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .pulse-dot.running {
        background: #22c55e;
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.75rem;
        color: #8b9bb4;
        padding: 4px 12px;
        background: rgba(0,0,0,0.3);
        border-radius: 20px;
    }
    .live-indicator::before {
        content: '';
        width: 6px;
        height: 6px;
        background: #22c55e;
        border-radius: 50%;
        animation: blink 1.5s infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }
</style>
@endsection

@section('content')

@if(session('warning'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 12px; color: #eab308;">
    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>{{ session('warning') }}
</div>
@endif

@if(session('error'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
</div>
@endif

@if(session('success'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; color: #22c55e;">
    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
</div>
@endif

<div class="page-header">
    <div>
        <h1 class="page-title">{{ $status['name'] ?? ($vm->name ?? 'VM #' . $vmid) }}</h1>
        <p class="page-subtitle">
            VM ID: <span style="font-family: monospace; color: #00b7ff;">{{ $vmid }}</span> | 
            Node: <span style="color: #8b5cf6;">{{ config('proxmox.node') }}</span>
            @if($vm && $vm->hostname)
                | Hostname: <span style="color: #22c55e;">{{ $vm->hostname }}</span>
            @endif
        </p>
    </div>
    <div style="display: flex; gap: 12px; align-items: center;">
        <span class="live-indicator">Live Updates</span>
        <a href="{{ route('admin.proxmox.vms.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back
        </a>
    </div>
</div>

{{-- Status Banner --}}
@if($status)
<div class="vm-status-banner {{ $status['status'] === 'running' ? 'running' : 'stopped' }}" id="status-banner">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                {{ $status['status'] === 'running' 
                    ? 'background: linear-gradient(135deg, rgba(34, 197, 94, 0.3) 0%, rgba(34, 197, 94, 0.1) 100%); border: 2px solid rgba(34, 197, 94, 0.5);' 
                    : 'background: linear-gradient(135deg, rgba(139, 155, 180, 0.3) 0%, rgba(139, 155, 180, 0.1) 100%); border: 2px solid rgba(139, 155, 180, 0.5);' }}">
                <i class="fas fa-power-off" style="font-size: 1.75rem; {{ $status['status'] === 'running' ? 'color: #22c55e;' : 'color: #8b9bb4;' }}"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: #8b9bb4; margin-bottom: 4px;">
                    <span class="pulse-dot {{ $status['status'] === 'running' ? 'running' : '' }}"></span>Current Status
                </div>
                <div style="font-size: 2rem; font-weight: 800; {{ $status['status'] === 'running' ? 'color: #22c55e;' : 'color: #8b9bb4;' }}" id="vm-status-text">
                    {{ ucfirst($status['status']) }}
                </div>
                @if($status['status'] === 'running')
                    <div style="font-size: 0.9rem; color: #8b9bb4; margin-top: 4px;" id="uptime-display">
                        <i class="fas fa-clock" style="margin-right: 6px; color: #00b7ff;"></i>Uptime: {{ $status['uptime'] ?? 'N/A' }}
                    </div>
                @endif
            </div>
        </div>
        
        <div style="display: flex; gap: 10px;">
            @if($status['status'] === 'running')
                <form action="{{ route('admin.proxmox.vms.shutdown', $vmid) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding: 12px 24px;"
                            onclick="return confirm('Gracefully shutdown VM {{ $vmid }}?')">
                        <i class="fas fa-stop-circle" style="margin-right: 8px;"></i>Shutdown
                    </button>
                </form>
                
                <form action="{{ route('admin.proxmox.vms.stop', $vmid) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" 
                            style="padding: 12px 24px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-color: rgba(239, 68, 68, 0.4);"
                            onclick="return confirm('Force stop VM {{ $vmid }}?')">
                        <i class="fas fa-stop" style="margin-right: 8px;"></i>Stop
                    </button>
                </form>
                
                <form action="{{ route('admin.proxmox.vms.reboot', $vmid) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="padding: 12px 24px;"
                            onclick="return confirm('Reboot VM {{ $vmid }}?')">
                        <i class="fas fa-sync-alt" style="margin-right: 8px;"></i>Reboot
                    </button>
                </form>
            @else
                <form action="{{ route('admin.proxmox.vms.start', $vmid) }}" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="padding: 14px 32px; font-size: 1rem;">
                        <i class="fas fa-play" style="margin-right: 8px;"></i>Start VM
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endif

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    {{-- Left Column: Resource Usage --}}
    <div>
        {{-- Resource Usage with Progress Bars --}}
        <div class="card" style="margin-bottom: 24px;">
            <div class="table-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="table-title"><i class="fas fa-chart-line" style="margin-right: 10px; color: #00b7ff;"></i>Real-Time Resource Usage</h3>
                <span style="font-size: 0.75rem; color: #8b9bb4; background: rgba(0,0,0,0.3); padding: 4px 10px; border-radius: 12px;">
                    <i class="fas fa-sync-alt" style="margin-right: 4px;"></i>Auto-refresh: 5s
                </span>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                    {{-- CPU Card --}}
                    <div class="metric-card cpu">
                        <div style="font-size: 0.8rem; color: #00b7ff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                            <i class="fas fa-microchip" style="margin-right: 6px;"></i>CPU Usage
                        </div>
                        <div class="metric-value" id="cpu-value">
                            {{ round(($status['cpu'] ?? 0) * 100, 1) }}%
                        </div>
                        <div style="font-size: 0.85rem; color: #94a3b8;">
                            <span id="cpu-cores">{{ $status['cpus'] ?? $status['maxcpu'] ?? 1 }}</span> Core{{ ($status['maxcpu'] ?? 1) > 1 ? 's' : '' }}
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill cpu" id="cpu-bar" style="width: {{ round(($status['cpu'] ?? 0) * 100, 1) }}%"></div>
                        </div>
                    </div>

                    {{-- Memory Card --}}
                    <div class="metric-card memory">
                        <div style="font-size: 0.8rem; color: #8b5cf6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                            <i class="fas fa-memory" style="margin-right: 6px;"></i>Memory
                        </div>
                        <div class="metric-value" id="memory-value">
                            @if(isset($status['mem']) && isset($status['maxmem']))
                                {{ round(($status['mem'] / $status['maxmem']) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div style="font-size: 0.85rem; color: #94a3b8;" id="memory-text">
                            @if(isset($status['mem']) && isset($status['maxmem']))
                                {{ round($status['mem'] / 1024 / 1024 / 1024, 1) }} / {{ round($status['maxmem'] / 1024 / 1024 / 1024, 1) }} GB
                            @else
                                {{ isset($status['maxmem']) ? round($status['maxmem'] / 1024 / 1024 / 1024, 1) . ' GB' : 'N/A' }}
                            @endif
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill memory" id="memory-bar" 
                                 style="width: {{ isset($status['mem']) && isset($status['maxmem']) ? round(($status['mem'] / $status['maxmem']) * 100, 1) : 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Disk Card --}}
                    <div class="metric-card disk">
                        <div style="font-size: 0.8rem; color: #f59e0b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 12px; font-weight: 600;">
                            <i class="fas fa-hdd" style="margin-right: 6px;"></i>Disk Usage
                        </div>
                        <div class="metric-value" id="disk-value">
                            @if(isset($status['disk']) && isset($status['maxdisk']))
                                {{ round(($status['disk'] / $status['maxdisk']) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <div style="font-size: 0.85rem; color: #94a3b8;" id="disk-text">
                            @if(isset($status['disk']) && isset($status['maxdisk']))
                                {{ round($status['disk'] / 1024 / 1024 / 1024, 1) }} / {{ round($status['maxdisk'] / 1024 / 1024 / 1024, 1) }} GB
                            @else
                                {{ isset($status['maxdisk']) ? round($status['maxdisk'] / 1024 / 1024 / 1024, 1) . ' GB' : 'N/A' }}
                            @endif
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill disk" id="disk-bar" 
                                 style="width: {{ isset($status['disk']) && isset($status['maxdisk']) ? round(($status['disk'] / $status['maxdisk']) * 100, 1) : 0 }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Network Stats --}}
                @if(isset($status['netin']) || isset($status['netout']))
                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 16px;">
                        <i class="fas fa-network-wired" style="margin-right: 8px; color: #00b7ff;"></i>Network I/O (Total)
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div class="network-stat-card">
                            <div class="network-icon inbound">
                                <i class="fas fa-arrow-down"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #00b7ff; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Inbound</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: #fff;" id="netin-value">
                                    @php
                                        $netin = $status['netin'] ?? 0;
                                        if ($netin > 1099511627776) {
                                            echo round($netin / 1099511627776, 2) . ' TB';
                                        } elseif ($netin > 1073741824) {
                                            echo round($netin / 1073741824, 2) . ' GB';
                                        } elseif ($netin > 1048576) {
                                            echo round($netin / 1048576, 2) . ' MB';
                                        } else {
                                            echo round($netin / 1024, 2) . ' KB';
                                        }
                                    @endphp
                                </div>
                            </div>
                        </div>
                        <div class="network-stat-card">
                            <div class="network-icon outbound">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">Outbound</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: #fff;" id="netout-value">
                                    @php
                                        $netout = $status['netout'] ?? 0;
                                        if ($netout > 1099511627776) {
                                            echo round($netout / 1099511627776, 2) . ' TB';
                                        } elseif ($netout > 1073741824) {
                                            echo round($netout / 1073741824, 2) . ' GB';
                                        } elseif ($netout > 1048576) {
                                            echo round($netout / 1048576, 2) . ' MB';
                                        } else {
                                            echo round($netout / 1024, 2) . ' KB';
                                        }
                                    @endphp
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- System Information --}}
        <div class="card" style="margin-bottom: 24px;">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-server" style="margin-right: 10px; color: #22c55e;"></i>System Information</h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">
                            <i class="fas fa-desktop" style="margin-right: 4px;"></i>Operating System
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 500;">
                            @if($vm && $vm->iso)
                                {{ str_replace(['.iso', '-'], ['', ' '], $vm->iso) }}
                            @else
                                Linux (Unknown)
                            @endif
                        </div>
                    </div>
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">
                            <i class="fas fa-network-wired" style="margin-right: 4px;"></i>Network Bridge
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 500; font-family: monospace;">
                            {{ $vm->bridge ?? config('proxmox.bridge', 'vmbr0') }}
                        </div>
                    </div>
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">
                            <i class="fas fa-database" style="margin-right: 4px;"></i>Storage
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 500;">
                            {{ $vm->storage ?? 'local-lvm' }}
                        </div>
                    </div>
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">
                            <i class="fas fa-fingerprint" style="margin-right: 4px;"></i>VMID
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 500; font-family: monospace;">{{ $vmid }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuration Details --}}
        @if($config)
        <div class="data-table">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-cog" style="margin-right: 10px; color: #eab308;"></i>VM Configuration</h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">Boot Order</div>
                        <div style="font-size: 0.95rem; color: #fff; font-weight: 500; font-family: monospace;">{{ $config['boot'] ?? 'N/A' }}</div>
                    </div>
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">BIOS</div>
                        <div style="font-size: 0.95rem; color: #fff; font-weight: 500;">{{ $config['bios'] ?? 'seabios' }}</div>
                    </div>
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">SCSI Hardware</div>
                        <div style="font-size: 0.95rem; color: #fff; font-weight: 500;">{{ $config['scsihw'] ?? 'virtio-scsi-single' }}</div>
                    </div>
                    <div class="info-card">
                        <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 6px;">QEMU Agent</div>
                        <div style="font-size: 0.95rem; color: #fff; font-weight: 500;">
                            @if(isset($config['agent']))
                                <span style="color: #22c55e;"><i class="fas fa-check-circle" style="margin-right: 4px;"></i>Enabled</span>
                            @else
                                <span style="color: #ef4444;"><i class="fas fa-times-circle" style="margin-right: 4px;"></i>Disabled</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if(isset($config['ide2']))
                <div style="margin-top: 16px; padding: 16px; background: linear-gradient(145deg, rgba(0, 183, 255, 0.1) 0%, rgba(0, 183, 255, 0.05) 100%); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px;">
                    <div style="font-size: 0.75rem; color: #00b7ff; text-transform: uppercase; margin-bottom: 6px;">
                        <i class="fas fa-compact-disc" style="margin-right: 4px;"></i>ISO / CD-ROM
                    </div>
                    <div style="font-size: 0.9rem; color: #fff; font-weight: 500; font-family: monospace; word-break: break-all;">{{ $config['ide2'] }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Right Column: Quick Actions & Info --}}
    <div>
        {{-- Quick Info --}}
        <div class="card" style="margin-bottom: 24px;">
            <div class="table-header">
                <h3 class="table-title"><i class="fas fa-info-circle" style="margin-right: 10px; color: #8b5cf6;"></i>Quick Info</h3>
            </div>
            <div style="padding: 24px;">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    
                    {{-- Plan Name --}}
                    @if($vm && $vm->plan_name)
                    <div class="info-card highlight-green">
                        <div style="font-size: 0.7rem; color: #22c55e; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">
                            <i class="fas fa-box" style="margin-right: 4px;"></i>Plan
                        </div>
                        <div style="font-size: 1.1rem; color: #fff; font-weight: 700;">{{ $vm->plan_name }}</div>
                        @if($vm->bandwidth)
                            <small style="color: #8b9bb4; font-size: 0.7rem;"><i class="fas fa-tachometer-alt" style="margin-right: 4px;"></i>{{ $vm->bandwidth }}</small>
                        @endif
                    </div>
                    @endif
                    
                    {{-- Control Panel --}}
                    @if($vm && $vm->control_panel)
                    <div class="info-card highlight-orange">
                        <div style="font-size: 0.7rem; color: #f59e0b; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">
                            <i class="fas fa-desktop" style="margin-right: 4px;"></i>Control Panel
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 700; text-transform: uppercase;">
                            {{ str_replace(['cpanel', 'plesk', 'fastpanel', 'aapanel', 'webmin', 'cyberpanel', 'hestiacp'], ['cPanel', 'Plesk', 'FastPanel', 'aaPanel', 'Webmin', 'CyberPanel', 'HestiaCP'], $vm->control_panel) }}
                        </div>
                    </div>
                    @endif
                    
                    {{-- IP Address from Database --}}
                    @if($vm && $vm->ip_address)
                    <div class="info-card highlight-green">
                        <div style="font-size: 0.7rem; color: #22c55e; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">
                            <i class="fas fa-globe" style="margin-right: 4px;"></i>IPv4 Address
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 600; font-family: monospace; word-break: break-all;">{{ $vm->ip_address }}</div>
                    </div>
                    @endif
                    
                    {{-- MAC Address from Database --}}
                    @if($vm && $vm->mac_address)
                    <div class="info-card highlight-blue">
                        <div style="font-size: 0.7rem; color: #00b7ff; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">
                            <i class="fas fa-fingerprint" style="margin-right: 4px;"></i>Virtual MAC
                        </div>
                        <div style="font-size: 0.9rem; color: #fff; font-weight: 500; font-family: monospace;">{{ $vm->mac_address }}</div>
                    </div>
                    @endif
                    
                    {{-- Assigned Client --}}
                    @if($vm && $vm->user)
                    <div class="info-card highlight-purple">
                        <div style="font-size: 0.7rem; color: #8b5cf6; text-transform: uppercase; margin-bottom: 4px; font-weight: 600;">
                            <i class="fas fa-user" style="margin-right: 4px;"></i>Assigned Client
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 600;">{{ $vm->user->name }}</div>
                        <div style="font-size: 0.75rem; color: #8b9bb4;">{{ $vm->user->email }}</div>
                    </div>
                    @endif
                    
                    <div class="info-card">
                        <div style="font-size: 0.7rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 4px;">
                            <i class="fas fa-hashtag" style="margin-right: 4px;"></i>VM ID
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-family: monospace; font-weight: 600;">{{ $vmid }}</div>
                    </div>
                    
                    <div class="info-card">
                        <div style="font-size: 0.7rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 4px;">
                            <i class="fas fa-tag" style="margin-right: 4px;"></i>Process ID
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-family: monospace;">{{ $status['pid'] ?? 'N/A' }}</div>
                    </div>
                    
                    @if(isset($status['qmpstatus']))
                    <div class="info-card">
                        <div style="font-size: 0.7rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 4px;">
                            <i class="fas fa-plug" style="margin-right: 4px;"></i>QMP Status
                        </div>
                        <div style="font-size: 1rem; color: #fff; font-weight: 600;">{{ ucfirst($status['qmpstatus']) }}</div>
                    </div>
                    @endif
                </div>
            </div>
            
            {{-- Panel Login Credentials --}}
            @if($vm && ($vm->control_panel || $vm->panel_login_url))
            <div class="data-table" style="margin-bottom: 24px; border-color: rgba(139, 92, 246, 0.3);">
                <div class="table-header" style="background: rgba(139, 92, 246, 0.1);">
                    <h3 class="table-title" style="color: #8b5cf6;">
                        <i class="fas fa-desktop" style="margin-right: 10px;"></i>
                        Panel Access
                        @if($vm->panel_status === 'installing')
                            <span style="font-size: 0.7rem; margin-left: 8px; padding: 4px 8px; background: rgba(245, 158, 11, 0.2); color: #f59e0b; border-radius: 4px;">
                                <i class="fas fa-spinner fa-spin"></i> Installing...
                            </span>
                        @elseif($vm->panel_status === 'installed')
                            <span style="font-size: 0.7rem; margin-left: 8px; padding: 4px 8px; background: rgba(34, 197, 94, 0.2); color: #22c55e; border-radius: 4px;">
                                <i class="fas fa-check"></i> Installed
                            </span>
                        @endif
                    </h3>
                </div>
                <div style="padding: 24px;">
                    @if($vm->panel_login_url)
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; margin-bottom: 8px;">
                            <i class="fas fa-link" style="margin-right: 4px;"></i>Login URL
                        </div>
                        <a href="{{ $vm->panel_login_url }}" target="_blank" 
                           style="font-size: 1rem; color: #00b7ff; font-family: monospace; word-break: break-all; text-decoration: none;">
                            {{ $vm->panel_login_url }} <i class="fas fa-external-link-alt" style="font-size: 0.75rem;"></i>
                        </a>
                    </div>
                    @endif
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                        @if($vm->panel_username)
                        <div style="padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <div style="font-size: 0.7rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 4px;">Username</div>
                            <div style="font-size: 1rem; color: #fff; font-family: monospace;">{{ $vm->panel_username }}</div>
                        </div>
                        @endif
                        
                        @if($vm->panel_password)
                        <div style="padding: 12px; background: rgba(0,0,0,0.2); border-radius: 8px;">
                            <div style="font-size: 0.7rem; color: #8b9bb4; text-transform: uppercase; margin-bottom: 4px;">
                                Password
                                <button type="button" onclick="fetchPanelPassword()" style="background: none; border: none; color: #8b5cf6; cursor: pointer; font-size: 0.7rem;">
                                    <i class="fas fa-eye"></i> Show
                                </button>
                                <button type="button" onclick="copyPanelPassword()" style="background: none; border: none; color: #8b5cf6; cursor: pointer; font-size: 0.7rem; margin-left: 8px;">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <div id="panel-password" style="font-size: 1rem; color: #fff; font-family: monospace; filter: blur(4px);" data-password-shown="false">
                                ••••••••••••••
                            </div>
                        </div>

                        <script>
                            let panelPassword = '';
                            function fetchPanelPassword() {
                                fetch('{{ route('admin.proxmox.vms.panel-password', $vm->vmid) }}')
                                    .then(r => r.json())
                                    .then(data => {
                                        if (data.success && data.panel_password) {
                                            panelPassword = data.panel_password;
                                            const el = document.getElementById('panel-password');
                                            el.textContent = panelPassword;
                                            el.style.filter = 'none';
                                            el.setAttribute('data-password-shown', 'true');
                                        } else {
                                            alert(data.error || 'Could not fetch panel password');
                                        }
                                    })
                                    .catch(e => alert('Failed: ' + e.message));
                            }
                            function copyPanelPassword() {
                                if (!panelPassword) {
                                    fetch('{{ route('admin.proxmox.vms.panel-password', $vm->vmid) }}')
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.success && data.panel_password) {
                                                navigator.clipboard.writeText(data.panel_password);
                                            } else {
                                                alert(data.error || 'Could not fetch panel password');
                                            }
                                        });
                                } else {
                                    navigator.clipboard.writeText(panelPassword);
                                }
                            }
                        </script>
                        @endif
                    </div>
                    
                    @if($vm->panel_status === 'installing')
                    <div style="padding: 12px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px;">
                        <div style="font-size: 0.8rem; color: #f59e0b;">
                            <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                            Panel is being installed. This may take 5-15 minutes. 
                            Please wait for the installation to complete before accessing the panel.
                        </div>
                    </div>
                    @endif
                    
                    @if($vm->cloud_init_status)
                    <div style="margin-top: 12px; font-size: 0.75rem; color: #6b7280;">
                        <i class="fas fa-cloud" style="margin-right: 4px; color: #00b7ff;"></i>
                        Cloud-init Status: <span style="color: #8b9bb4; text-transform: capitalize;">{{ $vm->cloud_init_status }}</span>
                    </div>
                    @endif
                </div>
            </div>
            
            <script>
                function togglePassword() {
                    const pwd = document.getElementById('panel-password');
                    const btn = event.target.closest('button');
                    if (pwd.style.filter === 'blur(4px)') {
                        pwd.style.filter = 'none';
                        btn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
                    } else {
                        pwd.style.filter = 'blur(4px)';
                        btn.innerHTML = '<i class="fas fa-eye"></i> Show';
                    }
                }
            </script>
            @endif
        </div>

        {{-- Danger Zone --}}
        <div class="data-table" style="border-color: rgba(239, 68, 68, 0.3);">
            <div class="table-header" style="background: rgba(239, 68, 68, 0.1);">
                <h3 class="table-title" style="color: #ef4444;"><i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i>Danger Zone</h3>
            </div>
            <div style="padding: 24px;">
                <p style="color: #8b9bb4; font-size: 0.9rem; margin-bottom: 20px;">
                    These actions are irreversible. Please be certain.
                </p>
                
                <form action="{{ route('admin.proxmox.vms.destroy', $vmid) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="btn btn-secondary" 
                            style="width: 100%; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.5); justify-content: center;"
                            onclick="return confirm('PERMANENTLY DELETE VM {{ $vmid }}?\n\nThis will:\n- Delete all VM data\n- Remove all disks\n- Cannot be undone\n\nAre you absolutely sure?')">
                        <i class="fas fa-trash-alt" style="margin-right: 8px;"></i>Delete VM
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Real-time metrics update
let refreshInterval;
const vmid = {{ $vmid }};

function formatBytes(bytes) {
    if (bytes > 1099511627776) {
        return (bytes / 1099511627776).toFixed(2) + ' TB';
    } else if (bytes > 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes > 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
}

function updateMetrics() {
    fetch(`/admin/proxmox/vms/${vmid}/api-status`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const status = data.data;
                
                // Update CPU
                const cpuPercent = Math.round((status.cpu || 0) * 100);
                document.getElementById('cpu-value').textContent = cpuPercent + '%';
                document.getElementById('cpu-bar').style.width = cpuPercent + '%';
                if (status.cpus || status.maxcpu) {
                    document.getElementById('cpu-cores').textContent = status.cpus || status.maxcpu;
                }
                
                // Update Memory
                if (status.mem && status.maxmem) {
                    const memPercent = Math.round((status.mem / status.maxmem) * 100);
                    const memUsed = (status.mem / 1024 / 1024 / 1024).toFixed(1);
                    const memTotal = (status.maxmem / 1024 / 1024 / 1024).toFixed(1);
                    document.getElementById('memory-value').textContent = memPercent + '%';
                    document.getElementById('memory-text').textContent = `${memUsed} / ${memTotal} GB`;
                    document.getElementById('memory-bar').style.width = memPercent + '%';
                }
                
                // Update Disk
                if (status.disk && status.maxdisk) {
                    const diskPercent = Math.round((status.disk / status.maxdisk) * 100);
                    const diskUsed = (status.disk / 1024 / 1024 / 1024).toFixed(1);
                    const diskTotal = (status.maxdisk / 1024 / 1024 / 1024).toFixed(1);
                    document.getElementById('disk-value').textContent = diskPercent + '%';
                    document.getElementById('disk-text').textContent = `${diskUsed} / ${diskTotal} GB`;
                    document.getElementById('disk-bar').style.width = diskPercent + '%';
                }
                
                // Update Network
                if (status.netin !== undefined) {
                    document.getElementById('netin-value').textContent = formatBytes(status.netin);
                }
                if (status.netout !== undefined) {
                    document.getElementById('netout-value').textContent = formatBytes(status.netout);
                }
                
                // Update Status
                const statusText = document.getElementById('vm-status-text');
                if (statusText && status.status) {
                    statusText.textContent = status.status.charAt(0).toUpperCase() + status.status.slice(1);
                }
                
                // Update Uptime
                const uptimeEl = document.getElementById('uptime-display');
                if (uptimeEl && status.uptime) {
                    uptimeEl.innerHTML = `<i class="fas fa-clock" style="margin-right: 6px; color: #00b7ff;"></i>Uptime: ${status.uptime}`;
                }
            }
        })
        .catch(error => console.error('Error fetching VM status:', error));
}

// Start real-time updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    refreshInterval = setInterval(updateMetrics, 5000);
});

// Stop updates when page is hidden
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        refreshInterval = setInterval(updateMetrics, 5000);
        updateMetrics();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
@endsection
