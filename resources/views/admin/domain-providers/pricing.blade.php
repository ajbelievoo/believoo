@extends('layouts.admin')

@section('title', 'Pricing: ' . $provider->name)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $provider->name }} - Pricing</h1>
        <p class="page-subtitle">Manage domain pricing for this provider</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="{{ route('admin.domain-providers.show', $provider) }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Provider
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

{{-- Add New Pricing Form --}}
<div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; margin-bottom: 30px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 20px;">
        <i class="fas fa-plus" style="margin-right: 8px; color: #22c55e;"></i>Add/Update Pricing
    </h4>
    
    <form action="{{ route('admin.domain-providers.pricing.update', $provider) }}" method="POST">
        @csrf
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">TLD <span style="color: #ef4444;">*</span></label>
                <input type="text" name="tld" required placeholder="com"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
                <small style="color: #6b7280; font-size: 0.7rem;">Without the dot</small>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Years <span style="color: #ef4444;">*</span></label>
                <input type="number" name="years" value="1" required min="1" max="10"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Cost Price (Register)</label>
                <input type="number" name="provider_register_price" step="0.01" min="0" placeholder="0.00"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
                <small style="color: #6b7280; font-size: 0.7rem;">What you pay</small>
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Selling Price (Register) <span style="color: #ef4444;">*</span></label>
                <input type="number" name="selling_register_price" required step="0.01" min="0" placeholder="0.00"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
                <small style="color: #6b7280; font-size: 0.7rem;">Customer pays (suggested: 2.5x cost)</small>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Cost Price (Renew)</label>
                <input type="number" name="provider_renew_price" step="0.01" min="0" placeholder="0.00"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Selling Price (Renew) <span style="color: #ef4444;">*</span></label>
                <input type="number" name="selling_renew_price" required step="0.01" min="0" placeholder="0.00"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Cost Price (Transfer)</label>
                <input type="number" name="provider_transfer_price" step="0.01" min="0" placeholder="0.00"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
            </div>
            
            <div>
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Selling Price (Transfer)</label>
                <input type="number" name="selling_transfer_price" step="0.01" min="0" placeholder="0.00"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
            </div>
        </div>
        
        <div style="display: flex; gap: 16px; align-items: center;">
            <div style="flex: 0 0 150px;">
                <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Priority</label>
                <input type="number" name="priority" value="100" min="1" max="999"
                    style="width: 100%; background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; font-size: 0.9rem;">
            </div>
            
            <div style="flex: 0 0 120px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 20px;">
                    <input type="checkbox" name="is_active" value="1" checked style="width: 18px; height: 18px; accent-color: #22c55e;">
                    <span style="color: #8b9bb4; font-size: 0.9rem;">Active</span>
                </label>
            </div>
            
            <div style="flex: 1;">
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-save" style="margin-right: 8px;"></i>Save Pricing
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Pricing List --}}
<div class="data-table">
    <div class="table-header">
        <h3 class="table-title"><i class="fas fa-dollar-sign" style="margin-right: 10px; color: #00b7ff;"></i>Pricing List</h3>
        <span style="font-size: 0.85rem; color: #8b9bb4;">{{ $pricing->count() }} TLDs configured</span>
    </div>

    @if($pricing->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>TLD</th>
                    <th>Years</th>
                    <th>Cost (Register)</th>
                    <th>Selling (Register)</th>
                    <th>Profit</th>
                    <th>Cost (Renew)</th>
                    <th>Selling (Renew)</th>
                    <th>Priority</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pricing as $price)
                <tr>
                    <td>
                        <span style="font-weight: 600; color: #fff;">.{{ $price->tld }}</span>
                    </td>
                    <td>
                        <span style="color: #8b9bb4;">{{ $price->years }}</span>
                    </td>
                    <td>
                        <span style="color: #6b7280; font-family: monospace;">${{ number_format($price->provider_register_price, 2) }}</span>
                    </td>
                    <td>
                        <span style="color: #22c55e; font-family: monospace; font-weight: 600;">${{ number_format($price->selling_register_price, 2) }}</span>
                    </td>
                    <td>
                        @php
                            $profit = $price->selling_register_price - $price->provider_register_price;
                            $percent = $price->provider_register_price > 0 ? ($profit / $price->provider_register_price) * 100 : 0;
                        @endphp
                        <span style="color: {{ $profit > 0 ? '#22c55e' : '#ef4444' }}; font-family: monospace;">
                            +${{ number_format($profit, 2) }}
                        </span>
                        <br>
                        <small style="color: #6b7280; font-size: 0.7rem;">{{ number_format($percent, 1) }}%</small>
                    </td>
                    <td>
                        <span style="color: #6b7280; font-family: monospace;">${{ number_format($price->provider_renew_price, 2) }}</span>
                    </td>
                    <td>
                        <span style="color: #8b9bb4; font-family: monospace;">${{ number_format($price->selling_renew_price, 2) }}</span>
                    </td>
                    <td>
                        <span style="color: #00b7ff; font-family: monospace;">{{ $price->priority }}</span>
                    </td>
                    <td>
                        @if($price->is_active)
                            <span style="padding: 4px 10px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">Active</span>
                        @else
                            <span style="padding: 4px 10px; background: rgba(107, 114, 128, 0.15); color: #6b7280; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">Inactive</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="padding: 16px 20px;">
            {{ $pricing->links() }}
        </div>
    @else
        <div style="padding: 60px 40px; text-align: center;">
            <i class="fas fa-dollar-sign" style="font-size: 4rem; color: rgba(139, 155, 180, 0.3); margin-bottom: 20px;"></i>
            <h4 style="font-size: 1.2rem; color: #8b9bb4; margin-bottom: 8px;">No Pricing Configured</h4>
            <p style="color: #6b7280; font-size: 0.9rem;">Add pricing for TLDs using the form above.</p>
        </div>
    @endif
</div>

<div style="margin-top: 30px; padding: 24px; background: rgba(0, 183, 255, 0.05); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: #00b7ff; margin-bottom: 16px;">
        <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>Pricing Tips
    </h4>
    <ul style="margin: 0; padding-left: 20px; color: #8b9bb4; font-size: 0.9rem; line-height: 1.8;">
        <li>Cost Price = What you pay to the provider</li>
        <li>Selling Price = What customer pays you (suggested: 2.5× cost price)</li>
        <li>Profit = Selling Price − Cost Price</li>
        <li>Lower priority number = Higher preference in search results</li>
        <li>Common TLDs: com, net, org, in, co.in, io, app, dev</li>
    </ul>
</div>
@endsection
