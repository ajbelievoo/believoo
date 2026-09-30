@extends('layouts.admin')

@section('title', 'Provider: ' . $provider->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $provider->name }}</h1>
        <p class="page-subtitle">Provider details and statistics</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="{{ route('admin.domain-providers.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back
        </a>
        <a href="{{ route('admin.domain-providers.edit', $provider) }}" class="btn btn-primary">
            <i class="fas fa-edit" style="margin-right: 8px;"></i>Edit
        </a>
        <a href="{{ route('admin.domain-providers.pricing', $provider) }}" class="btn btn-secondary">
            <i class="fas fa-dollar-sign" style="margin-right: 8px;"></i>Pricing
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

{{-- Stats Cards --}}
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: #00b7ff;">{{ $stats['total_domains'] }}</div>
        <div style="font-size: 0.9rem; color: #8b9bb4; margin-top: 8px;">Total Domains</div>
    </div>
    <div style="padding: 24px; background: linear-gradient(135deg, #1a2f23 0%, #1e3a2f 100%); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 16px; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: #22c55e;">{{ $stats['active_domains'] }}</div>
        <div style="font-size: 0.9rem; color: #8b9bb4; margin-top: 8px;">Active</div>
    </div>
    <div style="padding: 24px; background: linear-gradient(135deg, #2f2a1a 0%, #3a351e 100%); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 16px; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: #f59e0b;">{{ $stats['expired_domains'] }}</div>
        <div style="font-size: 0.9rem; color: #8b9bb4; margin-top: 8px;">Expired</div>
    </div>
    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: #8b5cf6;">{{ $stats['total_tlds'] }}</div>
        <div style="font-size: 0.9rem; color: #8b9bb4; margin-top: 8px;">TLDs Supported</div>
    </div>
</div>

{{-- Provider Info --}}
<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 30px;">
    
    {{-- Basic Info --}}
    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 20px;">
            <i class="fas fa-info-circle" style="margin-right: 8px; color: #00b7ff;"></i>Provider Information
        </h4>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Name</label>
            <div style="color: var(--text-primary); font-weight: 600;">{{ $provider->name }}</div>
        </div>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Code</label>
            <div style="color: var(--text-primary); font-family: monospace; background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 6px; display: inline-block;">{{ $provider->code }}</div>
        </div>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Status</label>
            <div>
                @if($provider->is_active)
                    <span style="padding: 6px 12px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                        <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Active
                    </span>
                @else
                    <span style="padding: 6px 12px; background: rgba(107, 114, 128, 0.15); color: #6b7280; border: 1px solid rgba(107, 114, 128, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                        <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Inactive
                    </span>
                @endif
            </div>
        </div>
        
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Priority</label>
            <div style="color: #00b7ff; font-weight: 600;">{{ $provider->priority }}</div>
            <small style="color: #6b7280; font-size: 0.7rem;">Lower = higher priority</small>
        </div>
        
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Last Checked</label>
            <div style="color: var(--text-primary);">
                @if($provider->last_checked_at)
                    {{ $provider->last_checked_at->diffForHumans() }}
                @else
                    Never
                @endif
            </div>
        </div>
    </div>
    
    {{-- Supported TLDs --}}
    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 20px;">
            <i class="fas fa-globe" style="margin-right: 8px; color: #22c55e;"></i>Supported TLDs
        </h4>
        
        @php
            $tlds = $provider->supported_tlds ?? [];
        @endphp
        
        @if(count($tlds) > 0)
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach($tlds as $tld)
                    <span style="padding: 6px 12px; background: rgba(0, 183, 255, 0.1); color: #00b7ff; border: 1px solid rgba(0, 183, 255, 0.3); border-radius: 20px; font-size: 0.8rem; font-weight: 500;">
                        .{{ $tld }}
                    </span>
                @endforeach
            </div>
        @else
            <p style="color: #6b7280; font-style: italic;">No TLDs configured</p>
        @endif
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <h5 style="font-size: 0.9rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">
                <i class="fas fa-server" style="margin-right: 8px; color: #8b5cf6;"></i>Default Nameservers
            </h5>
            @php
                $nameservers = $provider->default_nameservers ?? [];
            @endphp
            
            @if(count($nameservers) > 0)
                <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px;">
                    @foreach($nameservers as $ns)
                        <div style="color: #8b9bb4; font-family: monospace; font-size: 0.85rem; margin-bottom: 4px;">{{ $ns }}</div>
                    @endforeach
                </div>
            @else
                <p style="color: #6b7280; font-style: italic;">No nameservers configured</p>
            @endif
        </div>
    </div>
</div>

{{-- API Configuration --}}
<div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; margin-bottom: 30px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 20px;">
        <i class="fas fa-key" style="margin-right: 8px; color: #eab308;"></i>API Configuration
    </h4>
    
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">API URL</label>
            <div style="color: var(--text-primary); background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; word-break: break-all;">
                {{ $provider->api_url ?? 'Not configured' }}
            </div>
        </div>
        
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">API Key</label>
            <div style="color: var(--text-primary); background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-family: monospace; font-size: 0.85rem;">
                @if($provider->getMetadata('api_key') || $provider->getMetadata('api_token'))
                    ******** (configured)
                @else
                    Not configured
                @endif
            </div>
        </div>
        
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Username / Account ID</label>
            <div style="color: var(--text-primary); background: rgba(0,0,0,0.3); padding: 10px 14px; border-radius: 6px; font-family: monospace; font-size: 0.85rem;">
                {{ $provider->getMetadata('username') ?? $provider->getMetadata('auth_userid') ?? $provider->getMetadata('account_id') ?? 'Not configured' }}
            </div>
        </div>
        
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 4px;">Test Mode</label>
            <div style="color: var(--text-primary);">
                @if($provider->test_mode === '1')
                    <span style="color: #f59e0b;"><i class="fas fa-flask"></i> Enabled (Sandbox)</span>
                @else
                    <span style="color: #22c55e;"><i class="fas fa-check-circle"></i> Disabled (Production)</span>
                @endif
            </div>
        </div>
    </div>
    
    <div style="margin-top: 20px;">
        <button onclick="testConnection()" class="btn btn-secondary">
            <i class="fas fa-plug" style="margin-right: 8px;"></i>Test Connection
        </button>
    </div>
</div>

{{-- Recent Domains --}}
@if($provider->userDomains()->count() > 0)
<div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 20px;">
        <i class="fas fa-list" style="margin-right: 8px; color: #00b7ff;"></i>Recent Domains
    </h4>
    
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                <th style="text-align: left; padding: 12px; color: #8b9bb4; font-size: 0.8rem;">Domain</th>
                <th style="text-align: left; padding: 12px; color: #8b9bb4; font-size: 0.8rem;">User</th>
                <th style="text-align: left; padding: 12px; color: #8b9bb4; font-size: 0.8rem;">Expiry</th>
                <th style="text-align: left; padding: 12px; color: #8b9bb4; font-size: 0.8rem;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($provider->userDomains()->latest()->take(10)->get() as $domain)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px; color: var(--text-primary); font-weight: 500;">{{ $domain->domain_name }}</td>
                <td style="padding: 12px; color: #8b9bb4;">{{ $domain->user->name ?? 'N/A' }}</td>
                <td style="padding: 12px; color: #8b9bb4;">{{ $domain->expiry_date?->format('M d, Y') ?? 'N/A' }}</td>
                <td style="padding: 12px;">
                    @if($domain->status === 'active')
                        <span style="padding: 4px 10px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">Active</span>
                    @elseif($domain->status === 'expired')
                        <span style="padding: 4px 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">Expired</span>
                    @else
                        <span style="padding: 4px 10px; background: rgba(107, 114, 128, 0.15); color: #6b7280; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">{{ ucfirst($domain->status) }}</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<script>
function testConnection() {
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    btn.disabled = true;
    
    fetch('{{ route('admin.domain-providers.test-connection', $provider) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Connection successful!\\n\\n' + data.message);
        } else {
            alert('❌ Connection failed!\\n\\n' + data.message);
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
