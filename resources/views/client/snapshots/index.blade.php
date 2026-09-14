@extends('layouts.app')

@section('title', 'My VPS Snapshots - Believoo')

@section('content')
<div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
                <i class="fas fa-camera" style="margin-right: 12px; color: #00b7ff;"></i>My Snapshots
            </h1>
            <p style="color: #8b9bb4;">Backup and restore your VPS instances instantly.</p>
        </div>
        <a href="{{ route('client.servers') }}" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 12px;">
            <i class="fas fa-server" style="margin-right: 8px;"></i>My Servers
        </a>
    </div>

    @if($vms->isNotEmpty())
    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; margin-bottom: 30px;">
        <h3 style="color: #fff; margin-bottom: 16px; font-size: 1.1rem;">Create Quick Snapshot</h3>
        <form id="quickSnapshotForm" onsubmit="event.preventDefault(); createQuickSnapshot();">
            <div style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 220px;">
                    <label style="display: block; color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">Select VPS</label>
                    <select id="vm_id" name="vm_id" style="width: 100%; background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px;">
                        @foreach($vms as $vm)
                            <option value="{{ $vm->id }}">VM {{ $vm->vmid ?? $vm->id }} - {{ $vm->hostname ?? 'Unnamed' }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px; border-radius: 10px;">
                    <i class="fas fa-bolt" style="margin-right: 8px;"></i>Quick Snapshot
                </button>
            </div>
        </form>
        <p id="snapshotMsg" style="color: #22c55e; font-size: 0.85rem; margin-top: 12px; display: none;"></p>
    </div>
    @endif

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
        @if($snapshots->isNotEmpty())
        <table style="width: 100%; color: #cbd5e1; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <th style="text-align: left; padding: 12px;">Name</th>
                    <th style="text-align: left; padding: 12px;">VM</th>
                    <th style="text-align: left; padding: 12px;">Status</th>
                    <th style="text-align: left; padding: 12px;">Created</th>
                    <th style="text-align: right; padding: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($snapshots as $snapshot)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 14px 12px; font-weight: 600; color: #fff;">{{ $snapshot->name }}</td>
                    <td style="padding: 14px 12px;">{{ $snapshot->vm->vmid ?? $snapshot->proxmox_vm_id }}</td>
                    <td style="padding: 14px 12px;"><span class="badge badge-{{ $snapshot->status === 'restored' ? 'warning' : 'success' }}">{{ ucfirst($snapshot->status) }}</span></td>
                    <td style="padding: 14px 12px; font-size: 0.85rem; color: #94a3b8;">{{ $snapshot->created_at->format('M d, Y H:i') }}</td>
                    <td style="padding: 14px 12px; text-align: right;">
                        <button onclick="restoreSnapshot({{ $snapshot->id }})" class="btn btn-sm btn-secondary" style="margin-right: 6px; padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;">Restore</button>
                        <button onclick="deleteSnapshot({{ $snapshot->id }})" class="btn btn-sm" style="padding: 6px 12px; border-radius: 8px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);">Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($snapshots->hasPages())
        <div style="margin-top: 20px;">{{ $snapshots->links() }}</div>
        @endif
        @else
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-camera" style="font-size: 3rem; color: #334155; margin-bottom: 16px;"></i>
            <h3 style="color: #fff; margin-bottom: 8px;">No snapshots yet</h3>
            <p style="color: #64748b;">Create your first snapshot to protect your VPS data.</p>
        </div>
        @endif
    </div>
</div>

<script>
function createQuickSnapshot() {
    const vmId = document.getElementById('vm_id').value;
    fetch('{{ route('client.snapshots.quick') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ vm_id: vmId })
    })
    .then(r => r.json())
    .then(data => {
        const msg = document.getElementById('snapshotMsg');
        msg.textContent = data.message || 'Snapshot created.';
        msg.style.color = data.success ? '#22c55e' : '#ef4444';
        msg.style.display = 'block';
        if(data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => alert('Failed to create snapshot'));
}
function restoreSnapshot(id) {
    if(!confirm('Restore this snapshot? VM will restart.')) return;
    fetch('/snapshots/' + id + '/restore', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => alert(data.message)).then(() => location.reload());
}
function deleteSnapshot(id) {
    if(!confirm('Delete this snapshot?')) return;
    fetch('/snapshots/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => alert(data.message)).then(() => location.reload());
}
</script>
@endsection
