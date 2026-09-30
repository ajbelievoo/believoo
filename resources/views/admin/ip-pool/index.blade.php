@extends('layouts.admin')
@section('title', 'IP Pool Management')
@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">IP Pool</h1>
        <p class="page-subtitle">Pre-purchased IPs for automatic VM assignment</p>
    </div>
</div>

@if(session('success'))
<div style="margin-bottom:16px;padding:14px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:10px;color:#22c55e;">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="margin-bottom:16px;padding:14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:10px;color:#ef4444;">
    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
</div>
@endif

{{-- Stats --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
    @foreach(['total'=>['label'=>'Total IPs','color'=>'#00b7ff'],'available'=>['label'=>'Available','color'=>'#22c55e'],'assigned'=>['label'=>'Assigned','color'=>'#f59e0b'],'reserved'=>['label'=>'Reserved','color'=>'#8b5cf6']] as $key=>$info)
    <div style="background:linear-gradient(135deg,#1a1f2e,#252b3d);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:20px;text-align:center;">
        <div style="font-size:2rem;font-weight:800;color:{{ $info['color'] }};">{{ $stats[$key] }}</div>
        <div style="font-size:0.8rem;color:#8b9bb4;margin-top:4px;">{{ $info['label'] }}</div>
    </div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">

    {{-- IP List --}}
    <div class="card">
        <div class="table-header">
            <h3 class="table-title"><i class="fas fa-network-wired mr-2" style="color:#00b7ff;"></i>IP Addresses</h3>
        </div>
        @if($ips->count())
        <div class="card-body"><div class="table-responsive">
        <table class="data-table">
            <thead><tr>
                <th>IP Address</th><th>Gateway</th><th>Node</th><th>Status</th><th>Assigned To</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @foreach($ips as $ip)
            <tr>
                <td><span style="font-family:monospace;color:#00b7ff;">{{ $ip->ip_address }}</span></td>
                <td><span style="font-family:monospace;color:#8b9bb4;font-size:0.85rem;">{{ $ip->gateway ?? '—' }}</span></td>
                <td><span style="font-size:0.85rem;color:#d1d5db;">{{ $ip->node ?? 'Any' }}</span></td>
                <td>
                    @php $colors=['available'=>'#22c55e','assigned'=>'#f59e0b','reserved'=>'#8b5cf6','blocked'=>'#ef4444']; @endphp
                    <span style="padding:4px 10px;border-radius:20px;font-size:0.7rem;font-weight:700;text-transform:uppercase;background:{{ $colors[$ip->status]??'#8b9bb4' }}22;color:{{ $colors[$ip->status]??'#8b9bb4' }};border:1px solid {{ $colors[$ip->status]??'#8b9bb4' }}44;">
                        {{ $ip->status }}
                    </span>
                </td>
                <td>
                    @if($ip->vmid)
                        <span style="font-family:monospace;font-size:0.8rem;color:#f59e0b;">VM #{{ $ip->vmid }}</span>
                    @else
                        <span style="color:#6b7280;font-size:0.8rem;">—</span>
                    @endif
                </td>
                <td>
                    <div style="display:flex;gap:6px;">
                        @if($ip->status === 'assigned')
                        <form action="{{ route('admin.ip-pool.release', $ip) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding:6px 12px;font-size:0.75rem;" onclick="return confirm('Release this IP?')">
                                <i class="fas fa-undo"></i> Release
                            </button>
                        </form>
                        @endif
                        <form action="{{ route('admin.ip-pool.destroy', $ip) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding:6px 12px;font-size:0.75rem;color:#ef4444;" onclick="return confirm('Delete this IP?')">
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
        <div style="text-align:center;padding:40px;color:#8b9bb4;">
            <i class="fas fa-network-wired" style="font-size:2.5rem;opacity:0.3;margin-bottom:12px;"></i>
            <p>No IPs in pool. Add IPs using the form.</p>
        </div>
        @endif
    </div>

    {{-- Add IP Forms --}}
    <div>
        {{-- Single IP --}}
        <div style="background:linear-gradient(145deg,rgba(30,41,59,0.8),rgba(15,23,42,0.9));border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:20px;margin-bottom:16px;">
            <h3 style="font-size:0.9rem;font-weight:700;color:#fff;margin-bottom:16px;"><i class="fas fa-plus mr-2" style="color:#00b7ff;"></i>Add Single IP</h3>
            <form action="{{ route('admin.ip-pool.store') }}" method="POST">
                @csrf
                <div style="margin-bottom:12px;">
                    <label style="font-size:0.75rem;color:#8b9bb4;display:block;margin-bottom:4px;">IP Address *</label>
                    <input type="text" name="ip_address" placeholder="139.99.122.50" required
                           style="width:100%;padding:10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:0.9rem;">
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:0.75rem;color:#8b9bb4;display:block;margin-bottom:4px;">Gateway</label>
                    <input type="text" name="gateway" placeholder="139.99.122.1"
                           style="width:100%;padding:10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:0.9rem;">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="font-size:0.75rem;color:#8b9bb4;display:block;margin-bottom:4px;">Node</label>
                    <select name="node" style="width:100%;padding:10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:0.9rem;">
                        <option value="">Any Node</option>
                        @foreach($nodes as $n) <option value="{{ $n }}">{{ $n }}</option> @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;">
                    <i class="fas fa-plus mr-2"></i>Add IP
                </button>
            </form>
        </div>

        {{-- Bulk IPs --}}
        <div style="background:linear-gradient(145deg,rgba(30,41,59,0.8),rgba(15,23,42,0.9));border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:20px;">
            <h3 style="font-size:0.9rem;font-weight:700;color:#fff;margin-bottom:16px;"><i class="fas fa-list mr-2" style="color:#22c55e;"></i>Bulk Add IPs</h3>
            <form action="{{ route('admin.ip-pool.bulk') }}" method="POST">
                @csrf
                <div style="margin-bottom:12px;">
                    <label style="font-size:0.75rem;color:#8b9bb4;display:block;margin-bottom:4px;">IPs (one per line or comma-separated)</label>
                    <textarea name="ips" rows="5" placeholder="139.99.122.50&#10;139.99.122.51&#10;139.99.122.52"
                              style="width:100%;padding:10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:0.85rem;font-family:monospace;resize:vertical;"></textarea>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:0.75rem;color:#8b9bb4;display:block;margin-bottom:4px;">Gateway (same for all)</label>
                    <input type="text" name="gateway" placeholder="139.99.122.1"
                           style="width:100%;padding:10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:0.9rem;">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="font-size:0.75rem;color:#8b9bb4;display:block;margin-bottom:4px;">Node</label>
                    <select name="node" style="width:100%;padding:10px;background:rgba(0,0,0,0.3);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:0.9rem;">
                        <option value="">Any Node</option>
                        @foreach($nodes as $n) <option value="{{ $n }}">{{ $n }}</option> @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;background:linear-gradient(90deg,#22c55e,#16a34a);">
                    <i class="fas fa-upload mr-2"></i>Bulk Add
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
