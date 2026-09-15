@extends('layouts.admin')

@section('title', 'OVH Product: ' . $ovhProduct->plan_code)

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">{{ $ovhProduct->display_name }}</h1>
        <p class="page-subtitle">{{ $ovhProduct->plan_code }} &middot; {{ $ovhProduct->category_label }}</p>
    </div>
    <a href="{{ route('admin.ovh-products.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to List
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 20px;"><i class="fas fa-info-circle" style="color: #00b7ff; margin-right: 8px;"></i>Details</h3>
        <table style="width: 100%; color: #d1d5db;">
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Plan Code</td>
                <td style="padding: 12px 0; text-align: right; font-family: monospace;">{{ $ovhProduct->plan_code }}</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Category</td>
                <td style="padding: 12px 0; text-align: right;">{{ $ovhProduct->category_label }}</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Family</td>
                <td style="padding: 12px 0; text-align: right;">{{ $ovhProduct->family ?: 'N/A' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Description</td>
                <td style="padding: 12px 0; text-align: right;">{{ $ovhProduct->description ?: 'N/A' }}</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Status</td>
                <td style="padding: 12px 0; text-align: right;">
                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; {{ $ovhProduct->is_active ? 'background: rgba(34,197,94,0.2); color: #22c55e;' : 'background: rgba(239,68,68,0.2); color: #ef4444;' }}">
                        {{ $ovhProduct->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 20px;"><i class="fas fa-dollar-sign" style="color: #00b7ff; margin-right: 8px;"></i>Pricing</h3>
        <table style="width: 100%; color: #d1d5db;">
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Monthly Price</td>
                <td style="padding: 12px 0; text-align: right; color: #00b7ff; font-weight: 600;">₹{{ number_format($ovhProduct->price_monthly ?? 0, 0) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Cost Price</td>
                <td style="padding: 12px 0; text-align: right;">₹{{ number_format($ovhProduct->cost_price ?? 0, 0) }}</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Commission</td>
                <td style="padding: 12px 0; text-align: right;">{{ $ovhProduct->commission_percent }}%</td>
            </tr>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <td style="padding: 12px 0; color: #8b9bb4;">Currency</td>
                <td style="padding: 12px 0; text-align: right;">{{ $ovhProduct->currency }}</td>
            </tr>
        </table>
    </div>
</div>

<div style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
    <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 20px;"><i class="fas fa-server" style="color: #00b7ff; margin-right: 8px;"></i>Specifications</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
        <div style="padding: 16px; background: rgba(0,183,255,0.08); border-radius: 12px;">
            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase;">CPU Cores</div>
            <div style="font-size: 1.25rem; color: #fff; font-weight: 600;">{{ $ovhProduct->cpu_cores ?? 'N/A' }}</div>
        </div>
        <div style="padding: 16px; background: rgba(139,92,246,0.08); border-radius: 12px;">
            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase;">RAM</div>
            <div style="font-size: 1.25rem; color: #fff; font-weight: 600;">{{ $ovhProduct->ram_gb ? $ovhProduct->ram_gb . ' GB' : 'N/A' }}</div>
        </div>
        <div style="padding: 16px; background: rgba(245,158,11,0.08); border-radius: 12px;">
            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase;">Disk</div>
            <div style="font-size: 1.25rem; color: #fff; font-weight: 600;">{{ $ovhProduct->disk_gb ? $ovhProduct->disk_gb . ' GB' : 'N/A' }}</div>
        </div>
        <div style="padding: 16px; background: rgba(34,197,94,0.08); border-radius: 12px;">
            <div style="font-size: 0.75rem; color: #8b9bb4; text-transform: uppercase;">Bandwidth</div>
            <div style="font-size: 1.25rem; color: #fff; font-weight: 600;">{{ $ovhProduct->bandwidth_mbps ? $ovhProduct->bandwidth_mbps . ' Mbps' : 'N/A' }}</div>
        </div>
    </div>
</div>

<div style="margin-top: 24px; padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
    <h3 style="font-size: 1.1rem; color: #fff; margin-bottom: 20px;"><i class="fas fa-code" style="color: #00b7ff; margin-right: 8px;"></i>OVH Raw Config</h3>
    <pre style="background: #0a0e1a; padding: 16px; border-radius: 12px; color: #8b9bb4; overflow-x: auto; font-size: 0.85rem;">{{ json_encode($ovhProduct->ovh_config, JSON_PRETTY_PRINT) }}</pre>
</div>

<div style="margin-top: 24px; display: flex; gap: 12px;">
    <form action="{{ route('admin.ovh-products.toggle-active', $ovhProduct) }}" method="POST">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn {{ $ovhProduct->is_active ? 'btn-danger' : 'btn-success' }}">
            <i class="fas {{ $ovhProduct->is_active ? 'fa-ban' : 'fa-check' }}" style="margin-right: 8px;"></i>
            {{ $ovhProduct->is_active ? 'Deactivate' : 'Activate' }}
        </button>
    </form>
</div>
@endsection
