@extends('layouts.admin')

@section('title', 'Proxmox VMs')

@section('content')
@php
    // Test Proxmox connection
    $connectionTest = app(\App\Services\ProxmoxApiService::class)->testConnection();
@endphp

<div class="page-header">
    <div>
        <h1 class="page-title">Proxmox Virtual Machines</h1>
        <p class="page-subtitle">
            Node: {{ config('proxmox.node') }} | API: {{ config('proxmox.api_url') }}
            @if($connectionTest['success'])
                <span style="color: #22c55e; margin-left: 12px;">
                    <i class="fas fa-check-circle"></i> Connected ({{ $connectionTest['node'] ?? 'online' }})
                </span>
            @else
                <span style="color: #ef4444; margin-left: 12px;">
                    <i class="fas fa-times-circle"></i> {{ $connectionTest['message'] ?? 'Connection failed' }}
                </span>
            @endif
        </p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="{{ route('admin.proxmox.vms.create') }}" class="btn btn-primary {{ $connectionTest['success'] ? '' : 'disabled' }}" 
           {{ $connectionTest['success'] ? '' : 'onclick="event.preventDefault(); alert(\'Proxmox API not connected. Check settings.\');"' }}>
            <i class="fas fa-plus" style="margin-right: 8px;"></i>Create VM
        </a>
    </div>
</div>

@if(!$connectionTest['success'])
<div style="margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
    <strong>Proxmox API Connection Failed:</strong> {{ $connectionTest['message'] ?? 'Unknown error' }}
    <br><small>Check Settings → Server Management for Proxmox credentials</small>
</div>
@endif

@if(session('success'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; color: #22c55e;">
    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; color: #ef4444;">
    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>{{ session('error') }}
</div>
@endif

<div class="card">
    <div class="table-header">
        <h3 class="table-title"><i class="fas fa-server" style="margin-right: 10px; color: #00b7ff;"></i>Virtual Machines</h3>
        <span style="font-size: 0.85rem; color: #8b9bb4;">{{ count($vms) }} VMs found</span>
    </div>

    @if(count($vms) > 0)
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>VM ID</th>
                <th>Name</th>
                <th>Client</th>
                <th>IP Address</th>
                <th>Status</th>
                <th>CPU</th>
                <th>Memory</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vms as $vm)
            <tr>
                <td>
                    <span style="font-family: monospace; font-size: 1rem; color: #00b7ff; font-weight: 600;">
                        {{ $vm['vmid'] }}
                    </span>
                    @if(!empty($vm['db_only']))
                        <br><small style="color: #eab308; font-size: 0.65rem;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 2px;"></i>DB only
                        </small>
                    @endif
                </td>
                <td>
                    <strong style="color: #fff;">{{ $vm['name'] ?? 'Unnamed' }}</strong>
                    @if(!empty($vm['plan_name']))
                        <br><small style="color: #22c55e; font-size: 0.7rem;">
                            <i class="fas fa-box" style="margin-right: 2px;"></i>{{ $vm['plan_name'] }}
                        </small>
                    @endif
                    @if(!empty($vm['control_panel']))
                        <br><small style="color: #f59e0b; font-size: 0.7rem; text-transform: uppercase;">
                            <i class="fas fa-desktop" style="margin-right: 2px;"></i>
                            {{ str_replace(['cpanel', 'plesk', 'fastpanel', 'aapanel', 'webmin', 'cyberpanel', 'hestiacp'], ['cPanel', 'Plesk', 'FastPanel', 'aaPanel', 'Webmin', 'CyberPanel', 'HestiaCP'], $vm['control_panel']) }}
                        </small>
                    @endif
                </td>
                <td>
                    @if(!empty($vm['user']))
                        <span style="color: #22c55e; font-size: 0.85rem;">
                            <i class="fas fa-user" style="margin-right: 4px;"></i>{{ $vm['user']->name }}
                        </span>
                    @else
                        <span style="color: #6b7280; font-size: 0.85rem;">--</span>
                    @endif
                </td>
                <td>
                    @if(!empty($vm['ip_address']))
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="font-family: monospace; color: #00b7ff; font-size: 0.9rem;">
                                {{ $vm['ip_address'] }}
                            </span>
                            <button onclick="openIpModal({{ $vm['vmid'] }}, '{{ $vm['ip_address'] ?? '' }}', '{{ $vm['gateway'] ?? '' }}')"
                                    style="padding: 3px 6px; background: rgba(0,183,255,0.1); border: 1px solid rgba(0,183,255,0.3); border-radius: 6px; color: #00b7ff; cursor: pointer; font-size: 0.7rem;"
                                    title="Edit IP">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </div>
                        @if(!empty($vm['mac_address']))
                            <br><small style="color: #6b7280; font-size: 0.7rem; font-family: monospace;">{{ $vm['mac_address'] }}</small>
                        @endif
                        @if(!empty($vm['bandwidth']))
                            <br><small style="color: #8b9bb4; font-size: 0.7rem;"><i class="fas fa-network-wired" style="margin-right: 2px;"></i>{{ $vm['bandwidth'] }}</small>
                        @endif
                    @else
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <span style="color: #6b7280; font-size: 0.85rem;">--</span>
                            <button onclick="openIpModal({{ $vm['vmid'] }}, '', '')"
                                    style="padding: 3px 8px; background: rgba(234,179,8,0.15); border: 1px solid rgba(234,179,8,0.4); border-radius: 6px; color: #eab308; cursor: pointer; font-size: 0.7rem; font-weight: 600;"
                                    title="Assign IP">
                                <i class="fas fa-plus mr-1"></i>Assign IP
                            </button>
                        </div>
                    @endif
                </td>
                <td>
                    @if($vm['status'] === 'running')
                        <span style="padding: 6px 12px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Running
                        </span>
                    @else
                        <span style="padding: 6px 12px; background: rgba(139, 155, 180, 0.15); color: #8b9bb4; border: 1px solid rgba(139, 155, 180, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Stopped
                        </span>
                    @endif
                </td>
                <td>
                    <span style="color: #fff; font-size: 0.9rem;">{{ $vm['maxcpu'] ?? 1 }} Core{{ ($vm['maxcpu'] ?? 1) > 1 ? 's' : '' }}</span>
                </td>
                <td>
                    <span style="color: #fff; font-size: 0.9rem;">{{ isset($vm['maxmem']) ? round($vm['maxmem'] / 1024 / 1024 / 1024, 1) . ' GB' : 'N/A' }}</span>
                </td>
                <td>
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        {{-- View Details --}}
                        <a href="{{ route('admin.proxmox.vms.show', $vm['vmid']) }}" 
                           class="btn btn-secondary" 
                           style="padding: 8px 14px; font-size: 0.8rem;"
                           title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>

                        {{-- Start/Stop --}}
                        @if($vm['status'] === 'running')
                            <form action="{{ route('admin.proxmox.vms.stop', $vm['vmid']) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-secondary" 
                                        style="padding: 8px 14px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);"
                                        title="Stop"
                                        onclick="return confirm('Stop VM {{ $vm['vmid'] }}?')">
                                    <i class="fas fa-stop"></i>
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.proxmox.vms.reboot', $vm['vmid']) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-secondary" 
                                        style="padding: 8px 14px; font-size: 0.8rem;"
                                        title="Reboot"
                                        onclick="return confirm('Reboot VM {{ $vm['vmid'] }}?')">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.proxmox.vms.start', $vm['vmid']) }}" method="POST" style="display: inline;">
                                @csrf
                                <button type="submit" 
                                        class="btn btn-secondary" 
                                        style="padding: 8px 14px; font-size: 0.8rem; background: rgba(34, 197, 94, 0.1); color: #22c55e; border-color: rgba(34, 197, 94, 0.3);"
                                        title="Start">
                                    <i class="fas fa-play"></i>
                                </button>
                            </form>
                        @endif

                        {{-- Delete --}}
                        <form action="{{ route('admin.proxmox.vms.destroy', $vm['vmid']) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="btn btn-secondary" 
                                    style="padding: 8px 14px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);"
                                    title="Delete"
                                    onclick="return confirm('DELETE VM {{ $vm['vmid'] }}? This cannot be undone!')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="text-align: center; padding: 60px 20px;">
        <i class="fas fa-server" style="font-size: 3rem; color: rgba(139, 155, 180, 0.3); margin-bottom: 20px;"></i>
        <h3 style="font-size: 1.25rem; color: #fff; margin-bottom: 10px;">No Virtual Machines</h3>
        <p style="color: #8b9bb4; font-size: 0.9rem; margin-bottom: 25px;">Create your first VM to get started</p>
        <a href="{{ route('admin.proxmox.vms.create') }}" class="btn btn-primary">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>Create VM
        </a>
    </div>
    @endif
</div>

{{-- Quick Stats --}}
@if(count($vms) > 0)
<div style="margin-top: 24px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
    @php
        $running = collect($vms)->where('status', 'running')->count();
        $stopped = collect($vms)->where('status', 'stopped')->count();
        $totalCpu = collect($vms)->sum('maxcpu');
        $totalMem = collect($vms)->sum('maxmem') / 1024 / 1024 / 1024;
    @endphp
    
    <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #22c55e;">{{ $running }}</div>
        <div style="font-size: 0.85rem; color: #8b9bb4; margin-top: 4px;">Running VMs</div>
    </div>
    <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #8b9bb4;">{{ $stopped }}</div>
        <div style="font-size: 0.85rem; color: #8b9bb4; margin-top: 4px;">Stopped VMs</div>
    </div>
    <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #00b7ff;">{{ $totalCpu }}</div>
        <div style="font-size: 0.85rem; color: #8b9bb4; margin-top: 4px;">Total vCPUs</div>
    </div>
    <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #eab308;">{{ round($totalMem, 1) }} GB</div>
        <div style="font-size: 0.85rem; color: #8b9bb4; margin-top: 4px;">Total Memory</div>
    </div>
</div>
@endif

{{-- Manual IP Assignment Modal --}}
<div id="ipModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:linear-gradient(145deg,#1e293b,#0f172a); border:1px solid rgba(0,183,255,0.3); border-radius:20px; padding:32px; width:420px; max-width:95vw;">
        <h3 style="font-size:1.1rem; font-weight:800; color:#fff; margin-bottom:6px;">
            <i class="fas fa-network-wired" style="color:#00b7ff; margin-right:8px;"></i>Assign IP Address
        </h3>
        <p style="font-size:0.8rem; color:#8b9bb4; margin-bottom:24px;">VM ID: <span id="modalVmid" style="color:#00b7ff; font-family:monospace;"></span></p>

        <form id="ipForm" method="POST">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="font-size:0.75rem; color:#8b9bb4; display:block; margin-bottom:6px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">IP Address *</label>
                <input type="text" id="modalIp" name="ip_address" placeholder="139.99.122.50"
                       style="width:100%; padding:12px 16px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.15); border-radius:10px; color:#fff; font-size:0.95rem; font-family:monospace;"
                       required>
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:0.75rem; color:#8b9bb4; display:block; margin-bottom:6px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px;">Gateway</label>
                <input type="text" id="modalGateway" name="gateway" placeholder="139.99.122.1"
                       style="width:100%; padding:12px 16px; background:rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.15); border-radius:10px; color:#fff; font-size:0.95rem; font-family:monospace;">
            </div>
            <div style="margin-bottom:24px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="apply_cloud_init" value="1" checked style="width:16px; height:16px;">
                    <span style="font-size:0.85rem; color:#d1d5db;">Apply via cloud-init (updates VM network config)</span>
                </label>
            </div>
            <div style="display:flex; gap:12px;">
                <button type="submit" style="flex:1; padding:14px; background:linear-gradient(90deg,#00b7ff,#0066cc); border:none; border-radius:10px; color:#fff; font-weight:800; font-size:0.9rem; cursor:pointer;">
                    <i class="fas fa-save" style="margin-right:8px;"></i>Save & Apply
                </button>
                <button type="button" onclick="closeIpModal()" style="padding:14px 20px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:10px; color:#8b9bb4; font-weight:600; cursor:pointer;">
                    Cancel
                </button>
            </div>
        </form>
        <div id="ipModalResult" style="display:none; margin-top:16px; padding:12px; border-radius:10px; font-size:0.85rem;"></div>
    </div>
</div>

@push('scripts')
<script>
function openIpModal(vmid, currentIp, currentGateway) {
    document.getElementById('modalVmid').textContent = vmid;
    document.getElementById('modalIp').value = currentIp || '';
    document.getElementById('modalGateway').value = currentGateway || '';
    // Use absolute URL with app base URL to avoid path issues
    document.getElementById('ipForm').action = '{{ url('/admin/proxmox/vms') }}/' + vmid + '/assign-ip';
    document.getElementById('ipModalResult').style.display = 'none';
    document.getElementById('ipModal').style.display = 'flex';
}

function closeIpModal() {
    document.getElementById('ipModal').style.display = 'none';
}

document.getElementById('ipForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') 
                ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                : document.querySelector('input[name="_token"]')?.value || ''
        }
    })
    .then(r => r.json())
    .then(data => {
        const result = document.getElementById('ipModalResult');
        result.style.display = 'block';
        if (data.success) {
            result.style.background = 'rgba(34,197,94,0.15)';
            result.style.border = '1px solid rgba(34,197,94,0.3)';
            result.style.color = '#22c55e';
            result.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + data.message;
            setTimeout(() => { closeIpModal(); window.location.reload(); }, 1500);
        } else {
            result.style.background = 'rgba(239,68,68,0.15)';
            result.style.border = '1px solid rgba(239,68,68,0.3)';
            result.style.color = '#ef4444';
            result.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + (data.error || 'Failed');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save & Apply';
    })
    .catch(e => {
        const result = document.getElementById('ipModalResult');
        result.style.display = 'block';
        result.style.background = 'rgba(239,68,68,0.15)';
        result.style.border = '1px solid rgba(239,68,68,0.3)';
        result.style.color = '#ef4444';
        result.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>Network error: ' + e.message;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save & Apply';
    });
});

// Close on backdrop click
document.getElementById('ipModal').addEventListener('click', function(e) {
    if (e.target === this) closeIpModal();
});
</script>
@endpush

@endsection
