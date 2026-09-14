@extends('layouts.app')

@section('title', 'My Licenses - Believoo')

@section('content')
<div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
                <i class="fas fa-key" style="margin-right: 12px; color: #00b7ff;"></i>My Licenses
            </h1>
            <p style="color: #8b9bb4;">Manage control panel and security licenses for your VPS.</p>
        </div>
        <a href="{{ route('client.servers') }}" class="btn btn-secondary" style="padding: 12px 24px; border-radius: 12px;">
            <i class="fas fa-server" style="margin-right: 8px;"></i>My Servers
        </a>
    </div>

    @if($vms->isNotEmpty())
    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px; margin-bottom: 30px;">
        <h3 style="color: #fff; margin-bottom: 16px; font-size: 1.1rem;">Purchase New License</h3>
        <form id="licenseForm" onsubmit="event.preventDefault(); purchaseLicense();">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 12px; align-items: end;">
                <div>
                    <label style="display: block; color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">License Type</label>
                    <select id="license_type" name="license_type" style="width: 100%; background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px;">
                        @foreach($availableLicenses as $key => $license)
                            <option value="{{ $key }}">{{ $license['name'] }} - ₹{{ $license['price_monthly'] }}/mo</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">VPS</label>
                    <select id="vm_id" name="vm_id" style="width: 100%; background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px;">
                        @foreach($vms as $vm)
                            <option value="{{ $vm->id }}">VM {{ $vm->vmid ?? $vm->id }} - {{ $vm->hostname ?? 'Unnamed' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display: block; color: #8b9bb4; font-size: 0.8rem; margin-bottom: 6px;">Billing</label>
                    <select id="billing_cycle" name="billing_cycle" style="width: 100%; background: #1a1a1a; border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px;">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 12px 24px; border-radius: 10px;">
                    <i class="fas fa-shopping-cart" style="margin-right: 8px;"></i>Buy
                </button>
            </div>
        </form>
        <p id="licenseMsg" style="color: #22c55e; font-size: 0.85rem; margin-top: 12px; display: none;"></p>
    </div>
    @endif

    <div class="card" style="background: #111; border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 24px;">
        @if($licenses->isNotEmpty())
        <table style="width: 100%; color: #cbd5e1; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <th style="text-align: left; padding: 12px;">License</th>
                    <th style="text-align: left; padding: 12px;">Key</th>
                    <th style="text-align: left; padding: 12px;">VM</th>
                    <th style="text-align: left; padding: 12px;">Status</th>
                    <th style="text-align: left; padding: 12px;">Expires</th>
                    <th style="text-align: right; padding: 12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($licenses as $license)
                <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <td style="padding: 14px 12px; font-weight: 600; color: #fff;">{{ ucfirst(str_replace('_', ' ', $license->license_type)) }}</td>
                    <td style="padding: 14px 12px; font-family: monospace; font-size: 0.85rem;">{{ $license->license_key }}</td>
                    <td style="padding: 14px 12px;">{{ $license->vm->vmid ?? $license->proxmox_vm_id }}</td>
                    <td style="padding: 14px 12px;"><span class="badge badge-{{ $license->status === 'active' ? 'success' : 'warning' }}">{{ ucfirst($license->status) }}</span></td>
                    <td style="padding: 14px 12px; font-size: 0.85rem; color: #94a3b8;">{{ $license->expires_at?->format('M d, Y') ?? '—' }}</td>
                    <td style="padding: 14px 12px; text-align: right;">
                        @if($license->status !== 'active')
                        <button onclick="activateLicense({{ $license->id }})" class="btn btn-sm btn-secondary" style="padding: 6px 12px; border-radius: 8px; font-size: 0.8rem;">Activate</button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align: center; padding: 60px 20px;">
            <i class="fas fa-key" style="font-size: 3rem; color: #334155; margin-bottom: 16px;"></i>
            <h3 style="color: #fff; margin-bottom: 8px;">No licenses yet</h3>
            <p style="color: #64748b;">Purchase a control panel or security license for your VPS.</p>
        </div>
        @endif
    </div>
</div>

<script>
function purchaseLicense() {
    const data = {
        license_type: document.getElementById('license_type').value,
        vm_id: document.getElementById('vm_id').value,
        billing_cycle: document.getElementById('billing_cycle').value
    };
    fetch('{{ route('client.licenses.purchase') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(data => {
        const msg = document.getElementById('licenseMsg');
        msg.textContent = data.message || 'License purchased.';
        msg.style.color = data.success ? '#22c55e' : '#ef4444';
        msg.style.display = 'block';
        if(data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => alert('Failed to purchase license'));
}
function activateLicense(id) {
    fetch('/licenses/' + id + '/activate', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => alert(data.message)).then(() => location.reload());
}
</script>
@endsection
