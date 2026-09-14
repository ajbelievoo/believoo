@extends('layouts.app')

@section('title', 'My Domains - Believoo')

@section('content')
<div class="container" style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 700; color: #fff; margin-bottom: 8px;">
                <i class="fas fa-globe" style="margin-right: 12px; color: #00b7ff;"></i>My Domains
            </h1>
            <p style="color: #8b9bb4;">Manage all your domains in one place</p>
        </div>
        <a href="{{ route('client.domains.search') }}" class="btn btn-primary">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>Register New Domain
        </a>
    </div>

    {{-- Stats Cards --}}
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
            <div style="font-size: 0.85rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Total Domains</div>
            <div style="font-size: 2rem; font-weight: 700; color: #fff;">{{ $stats['total'] }}</div>
        </div>
        <div style="padding: 24px; background: linear-gradient(135deg, #1a2f23 0%, #1e3a2f 100%); border: 1px solid rgba(34, 197, 94, 0.2); border-radius: 16px;">
            <div style="font-size: 0.85rem; color: #22c55e; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Active</div>
            <div style="font-size: 2rem; font-weight: 700; color: #22c55e;">{{ $stats['active'] }}</div>
        </div>
        <div style="padding: 24px; background: linear-gradient(135deg, #2f2a1a 0%, #3a351e 100%); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 16px;">
            <div style="font-size: 0.85rem; color: #f59e0b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Expiring Soon</div>
            <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;">{{ $stats['expiring_soon'] }}</div>
        </div>
        <div style="padding: 24px; background: linear-gradient(135deg, #2f1a1a 0%, #3a1e1e 100%); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 16px;">
            <div style="font-size: 0.85rem; color: #ef4444; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Expired</div>
            <div style="font-size: 2rem; font-weight: 700; color: #ef4444;">{{ $stats['expired'] }}</div>
        </div>
    </div>

    {{-- Domains List --}}
    @if($domains->isEmpty())
        <div style="text-align: center; padding: 80px 40px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px;">
            <i class="fas fa-globe" style="font-size: 4rem; color: rgba(139, 155, 180, 0.3); margin-bottom: 24px;"></i>
            <h3 style="font-size: 1.5rem; color: #fff; margin-bottom: 12px;">No Domains Yet</h3>
            <p style="color: #8b9bb4; margin-bottom: 24px; max-width: 400px; margin-left: auto; margin-right: auto;">
                You haven't registered any domains yet. Search for your perfect domain name now!
            </p>
            <a href="{{ route('client.domains.search') }}" class="btn btn-primary" style="padding: 14px 32px;">
                <i class="fas fa-search" style="margin-right: 8px;"></i>Search Domains
            </a>
        </div>
    @else
        <div style="background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: rgba(0,0,0,0.2);">
                        <th style="padding: 16px 20px; text-align: left; font-size: 0.8rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px;">Domain</th>
                        <th style="padding: 16px 20px; text-align: left; font-size: 0.8rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                        <th style="padding: 16px 20px; text-align: left; font-size: 0.8rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px;">Expiry Date</th>
                        <th style="padding: 16px 20px; text-align: left; font-size: 0.8rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px;">Provider</th>
                        <th style="padding: 16px 20px; text-align: center; font-size: 0.8rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px;">Auto-Renew</th>
                        <th style="padding: 16px 20px; text-align: center; font-size: 0.8rem; color: #8b9bb4; text-transform: uppercase; letter-spacing: 0.5px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domains as $domain)
                    <tr style="border-top: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 20px;">
                            <div style="font-size: 1.1rem; font-weight: 600; color: #fff;">
                                {{ $domain->domain_name }}
                                @if($domain->whois_privacy)
                                    <span style="margin-left: 8px; padding: 2px 8px; background: rgba(139, 92, 246, 0.2); color: #8b5cf6; border-radius: 4px; font-size: 0.65rem;">
                                        <i class="fas fa-shield-alt"></i> Privacy
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td style="padding: 20px;">
                            @if($domain->status === 'active')
                                <span style="padding: 6px 12px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Active
                                </span>
                            @elseif($domain->status === 'expired')
                                <span style="padding: 6px 12px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                    <i class="fas fa-circle" style="font-size: 0.5rem; margin-right: 4px;"></i>Expired
                                </span>
                            @else
                                <span style="padding: 6px 12px; background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 20px; font-size: 0.75rem; font-weight: 600;">
                                    {{ ucfirst($domain->status) }}
                                </span>
                            @endif
                        </td>
                        <td style="padding: 20px;">
                            <div style="color: {{ $domain->isExpiringSoon() ? '#f59e0b' : '#fff' }};">
                                {{ $domain->expiry_date->format('M d, Y') }}
                            </div>
                            @if($domain->isExpiringSoon())
                                <small style="color: #f59e0b; font-size: 0.75rem;">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $domain->daysUntilExpiry() }} days left
                                </small>
                            @endif
                        </td>
                        <td style="padding: 20px;">
                            <span style="color: #8b9bb4; font-size: 0.9rem;">
                                {{ $domain->domainProvider?->name ?? 'Unknown' }}
                            </span>
                        </td>
                        <td style="padding: 20px; text-align: center;">
                            <button onclick="toggleAutoRenew({{ $domain->id }})" 
                                    style="width: 44px; height: 24px; border-radius: 12px; border: none; cursor: pointer; transition: all 0.2s; background: {{ $domain->auto_renew ? '#22c55e' : '#6b7280' }}; position: relative;">
                                <span style="position: absolute; top: 2px; {{ $domain->auto_renew ? 'right: 2px;' : 'left: 2px;' }} width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: all 0.2s;"></span>
                            </button>
                        </td>
                        <td style="padding: 20px; text-align: center;">
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <a href="{{ route('client.domains.show', $domain->id) }}" 
                                   style="padding: 8px 14px; background: rgba(0, 183, 255, 0.1); color: #00b7ff; border: 1px solid rgba(0, 183, 255, 0.3); border-radius: 8px; font-size: 0.8rem; text-decoration: none;"
                                   title="Manage DNS & Settings">
                                    <i class="fas fa-cog"></i>
                                </a>
                                @if($domain->isExpiringSoon() || $domain->isExpired())
                                    <button onclick="renewDomain({{ $domain->id }})" 
                                            style="padding: 8px 14px; background: rgba(34, 197, 94, 0.1); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 8px; font-size: 0.8rem; cursor: pointer;"
                                            title="Renew Domain">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
async function toggleAutoRenew(domainId) {
    try {
        const response = await fetch(`/domains/${domainId}/auto-renew`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.reload();
        } else {
            alert('Failed to update auto-renew: ' + data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

function renewDomain(domainId) {
    const years = prompt('How many years would you like to renew for? (1-10)', '1');
    if (!years || isNaN(years) || years < 1 || years > 10) {
        alert('Please enter a valid number between 1 and 10');
        return;
    }
    
    if (confirm(`Renew domain for ${years} year(s)?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/domains/${domainId}/renew`;
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
        form.appendChild(csrfToken);
        
        const yearsInput = document.createElement('input');
        yearsInput.type = 'hidden';
        yearsInput.name = 'years';
        yearsInput.value = years;
        form.appendChild(yearsInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
