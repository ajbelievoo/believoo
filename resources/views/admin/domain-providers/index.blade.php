@extends('layouts.admin')

@section('title', 'Domain Providers')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Domain Providers</h1>
        <p class="page-subtitle">Manage multi-API domain registration providers</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="{{ route('admin.domain-providers.test-all') }}" class="btn btn-secondary">
            <i class="fas fa-vial" style="margin-right: 8px;"></i>Test All Connections
        </a>
    </div>
</div>

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

@if(session('warning'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; color: #f59e0b;">
    <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>{{ session('warning') }}
</div>
@endif

<div class="data-table">
    <div class="table-header">
        <h3 class="table-title"><i class="fas fa-server" style="margin-right: 10px; color: #00b7ff;"></i>Active Providers</h3>
        <span style="font-size: 0.85rem; color: #8b9bb4;">{{ $providers->where('is_active', true)->count() }} active of {{ $providers->count() }} total</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Provider</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Supported TLDs</th>
                <th>Domains</th>
                <th>Last Checked</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($providers as $provider)
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 8px; background: 
                            {{ $provider->is_active ? 'rgba(34, 197, 94, 0.1)' : 'rgba(107, 114, 128, 0.1)' }}; 
                            display: flex; align-items: center; justify-content: center;">
                            <i class="fas {{ $provider->code === 'resellerclub' ? 'fa-globe-asia' : ($provider->code === 'namecheap' ? 'fa-tags' : 'fa-cloud') }}" 
                               style="color: {{ $provider->is_active ? '#22c55e' : '#6b7280' }};"></i>
                        </div>
                        <div>
                            <strong style="color: #fff;">{{ $provider->name }}</strong>
                            <br><small style="color: #6b7280; font-size: 0.75rem;">{{ $provider->code }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    @if($provider->is_active)
                        <span style="padding: 6px 12px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Active
                        </span>
                    @else
                        <span style="padding: 6px 12px; background: rgba(107, 114, 128, 0.15); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase;">
                            <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Inactive
                        </span>
                    @endif
                </td>
                <td>
                    <span style="font-family: monospace; color: #00b7ff; font-weight: 600;">{{ $provider->priority }}</span>
                </td>
                <td>
                    @php
                        $tlds = $provider->supported_tlds ?? [];
                        $tldCount = count($tlds);
                    @endphp
                    @if($tldCount > 0)
                        <span style="color: #8b9bb4; font-size: 0.85rem;">{{ $tldCount }} TLDs</span>
                        <br><small style="color: #6b7280; font-size: 0.7rem;">
                            {{ implode(', ', array_slice($tlds, 0, 3)) }}{{ $tldCount > 3 ? '...' : '' }}
                        </small>
                    @else
                        <span style="color: #6b7280; font-size: 0.85rem;">None configured</span>
                    @endif
                </td>
                <td>
                    <span style="color: #fff; font-weight: 600;">{{ $provider->user_domains_count }}</span>
                    @if($provider->user_domains_count > 0)
                        <br><small style="color: #6b7280; font-size: 0.7rem;">registered domains</small>
                    @endif
                </td>
                <td>
                    @if($provider->last_checked_at)
                        <span style="color: #8b9bb4; font-size: 0.85rem;">{{ $provider->last_checked_at->diffForHumans() }}</span>
                    @else
                        <span style="color: #6b7280; font-size: 0.85rem;">Never</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        <a href="{{ route('admin.domain-providers.show', $provider) }}" 
                           class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.8rem;" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.domain-providers.edit', $provider) }}" 
                           class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.8rem;" title="Edit Settings">
                            <i class="fas fa-cog"></i>
                        </a>
                        <a href="{{ route('admin.domain-providers.pricing', $provider) }}" 
                           class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.8rem;" title="Manage Pricing">
                            <i class="fas fa-dollar-sign"></i>
                        </a>
                        
                        @if($provider->is_active)
                            <form action="{{ route('admin.domain-providers.deactivate', $provider) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary" 
                                        style="padding: 8px 14px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);"
                                        title="Deactivate Provider"
                                        onclick="return confirm('Deactivate {{ $provider->name }}? This will prevent new registrations through this provider.')">
                                    <i class="fas fa-pause"></i>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.domain-providers.activate', $provider) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary" 
                                        style="padding: 8px 14px; font-size: 0.8rem; background: rgba(34, 197, 94, 0.1); color: #22c55e; border-color: rgba(34, 197, 94, 0.3);"
                                        title="Activate Provider"
                                        onclick="return confirm('Activate {{ $provider->name }}? Connection will be tested first.')">
                                    <i class="fas fa-play"></i>
                                </button>
                            </form>
                        @endif
                        
                        <button type="button" onclick="testConnection({{ $provider->id }})" 
                                class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.8rem;" title="Test Connection">
                            <i class="fas fa-plug"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 30px; padding: 24px; background: rgba(0, 183, 255, 0.05); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: #00b7ff; margin-bottom: 16px;">
        <i class="fas fa-info-circle" style="margin-right: 8px;"></i>Multi-API Domain Aggregator
    </h4>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; font-size: 0.85rem; color: #8b9bb4;">
        <div>
            <strong style="color: #fff;">Smart Search:</strong>
            <p style="margin: 4px 0 0 0;">Queries all active providers simultaneously for best availability and pricing.</p>
        </div>
        <div>
            <strong style="color: #fff;">Best Price Logic:</strong>
            <p style="margin: 4px 0 0 0;">Automatically selects cheapest provider after adding your profit margin.</p>
        </div>
        <div>
            <strong style="color: #fff;">Dynamic Routing:</strong>
            <p style="margin: 4px 0 0 0;">.in domains → ResellerClub, .com → cheapest (Namecheap/Cloudflare).</p>
        </div>
        <div>
            <strong style="color: #fff;">Failover Protection:</strong>
            <p style="margin: 4px 0 0 0;">If one API is down, automatically uses next best option.</p>
        </div>
    </div>
</div>

<script>
function testConnection(providerId) {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;
    
    fetch(`/admin/domain-providers/${providerId}/test-connection`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Connection successful: ' + data.message);
        } else {
            alert('❌ Connection failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Test failed: ' + error.message);
    })
    .finally(() => {
        btn.innerHTML = originalHtml;
        btn.disabled = false;
    });
}
</script>
@endsection
