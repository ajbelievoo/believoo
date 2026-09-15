@extends('layouts.admin')

@section('title', 'VPS Plans Management')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">VPS Plans Management</h1>
        <p class="page-subtitle">Manage Cloud-style VPS plans with 2.5x pricing</p>
    </div>
    <div style="display: flex; gap: 12px;">
        <a href="{{ route('admin.vps-plans.create') }}" class="btn btn-primary">
            <i class="fas fa-plus" style="margin-right: 8px;"></i>Add New Plan
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

{{-- Category Stats --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
    @foreach($categories as $key => $category)
    <div style="padding: 24px; background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <i class="fas {{ $category['icon'] }}" style="font-size: 1.5rem; color: #00b7ff;"></i>
            <span style="padding: 4px 12px; background: rgba(0, 183, 255, 0.2); color: #00b7ff; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                {{ $category['count'] }} plans
            </span>
        </div>
        <h4 style="font-size: 1rem; font-weight: 600; color: #fff; margin-bottom: 4px;">{{ $category['name'] }}</h4>
        <p style="font-size: 0.8rem; color: #8b9bb4;">{{ Str::limit($category['description'], 60) }}</p>
    </div>
    @endforeach
</div>

{{-- Bulk Price Update --}}
<div style="margin-bottom: 30px; padding: 24px; background: rgba(245, 158, 11, 0.05); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 12px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: #f59e0b; margin-bottom: 16px;">
        <i class="fas fa-calculator" style="margin-right: 8px;"></i>Bulk Price Update (2.5x Cloud Pricing)
    </h4>
    <form action="{{ route('admin.vps-plans.bulk-price-update') }}" method="POST" style="display: flex; gap: 12px; align-items: flex-end;">
        @csrf
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Price Multiplier</label>
            <input type="number" name="multiplier" value="2.5" step="0.1" min="1" max="10" required
                style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; width: 150px;">
        </div>
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Category (optional)</label>
            <select name="category" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; width: 200px;">
                <option value="">All Categories</option>
                @foreach($categories as $key => $cat)
                <option value="{{ $key }}">{{ $cat['name'] }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="background: #f59e0b; border-color: #f59e0b;">
            <i class="fas fa-sync-alt" style="margin-right: 6px;"></i>Update Prices
        </button>
    </form>
    <small style="display: block; margin-top: 12px; color: #6b7280;">
        <i class="fas fa-info-circle"></i> This will update all plan prices by multiplying cost_price with the multiplier
    </small>
</div>

{{-- Plans Table --}}
<div class="data-table">
    <div class="table-header">
        <h3 class="table-title"><i class="fas fa-server" style="margin-right: 10px; color: #00b7ff;"></i>All VPS Plans</h3>
        <span style="font-size: 0.85rem; color: #8b9bb4;">{{ $plans->count() }} total plans</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Plan</th>
                <th>Category</th>
                <th>Specs</th>
                <th>Cost Price</th>
                <th>Selling Price</th>
                <th>Profit</th>
                <th>Status</th>
                <th style="text-align: center;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($plans as $plan)
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 8px; background: 
                            {{ $plan->is_active ? 'rgba(34, 197, 94, 0.1)' : 'rgba(107, 114, 128, 0.1)' }}; 
                            display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-server" style="color: {{ $plan->is_active ? '#22c55e' : '#6b7280' }};"></i>
                        </div>
                        <div>
                            <strong style="color: #fff;">{{ $plan->name }}</strong>
                            @if($plan->is_recommended)
                                <span style="margin-left: 8px; padding: 2px 8px; background: rgba(0, 183, 255, 0.3); color: #00b7ff; border-radius: 4px; font-size: 0.65rem;">RECOMMENDED</span>
                            @endif
                            <br><small style="color: #6b7280; font-size: 0.75rem;">{{ $plan->display_name }}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="color: #8b9bb4; font-size: 0.85rem;">
                        {{ $categories[$plan->category]['name'] ?? $plan->category }}
                    </span>
                </td>
                <td>
                    <div style="font-size: 0.8rem; color: #8b9bb4; line-height: 1.6;">
                        {{ $plan->cpu_cores }} vCores<br>
                        {{ $plan->memory_gb }} GB RAM<br>
                        {{ $plan->disk_gb }} GB {{ $plan->disk_type }}
                    </div>
                </td>
                <td>
                    <span style="color: #6b7280; font-family: monospace;">₹{{ number_format($plan->cost_price ?? 0, 0) }}</span>
                </td>
                <td>
                    <span style="color: #fff; font-weight: 600; font-family: monospace;">₹{{ number_format($plan->price_monthly, 0) }}</span>
                    <br><small style="color: #6b7280; font-size: 0.7rem;">/month</small>
                </td>
                <td>
                    @if($plan->cost_price)
                        <span style="color: #22c55e; font-weight: 600;">+₹{{ number_format($plan->profit_margin, 0) }}</span>
                        <br><small style="color: #6b7280; font-size: 0.7rem;">{{ number_format($plan->profit_percentage, 1) }}%</small>
                    @else
                        <span style="color: #6b7280;">-</span>
                    @endif
                </td>
                <td>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        @if($plan->is_active)
                            <span style="padding: 4px 10px; background: rgba(34, 197, 94, 0.15); color: #22c55e; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-align: center;">Active</span>
                        @else
                            <span style="padding: 4px 10px; background: rgba(107, 114, 128, 0.15); color: #6b7280; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-align: center;">Inactive</span>
                        @endif
                        @if($plan->is_sold_out)
                            <span style="padding: 4px 10px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 20px; font-size: 0.7rem; font-weight: 600; text-align: center;">SOLD OUT</span>
                        @endif
                    </div>
                </td>
                <td>
                    <div style="display: flex; gap: 8px; justify-content: center;">
                        <a href="{{ route('admin.vps-plans.edit', $plan) }}" class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.8rem;" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <form action="{{ route('admin.vps-plans.toggle-sold-out', $plan) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-secondary" 
                                    style="padding: 8px 14px; font-size: 0.8rem; 
                                           background: {{ $plan->is_sold_out ? 'rgba(34, 197, 94, 0.1)' : 'rgba(239, 68, 68, 0.1)' }}; 
                                           color: {{ $plan->is_sold_out ? '#22c55e' : '#ef4444' }}; 
                                           border-color: {{ $plan->is_sold_out ? 'rgba(34, 197, 94, 0.3)' : 'rgba(239, 68, 68, 0.3)' }};"
                                    title="{{ $plan->is_sold_out ? 'Mark Available' : 'Mark Sold Out' }}">
                                <i class="fas {{ $plan->is_sold_out ? 'fa-check' : 'fa-ban' }}"></i>
                            </button>
                        </form>

                        <form action="{{ route('admin.vps-plans.destroy', $plan) }}" method="POST" style="display: inline;" onsubmit="return confirm('Delete this plan?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 8px 14px; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 30px; padding: 24px; background: rgba(0, 183, 255, 0.05); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px;">
    <h4 style="font-size: 1rem; font-weight: 600; color: #00b7ff; margin-bottom: 16px;">
        <i class="fas fa-info-circle" style="margin-right: 8px;"></i>Cloud-Style VPS Plans
    </h4>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; font-size: 0.85rem; color: #8b9bb4;">
        <div>
            <strong style="color: #fff;">Pricing Strategy:</strong>
            <p style="margin: 4px 0 0 0;">Cost Price = Cloud actual price, Selling Price = 2.5× for profit margin</p>
        </div>
        <div>
            <strong style="color: #fff;">Categories:</strong>
            <p style="margin: 4px 0 0 0;">VPS 2026, n8n VPS, Plesk VPS, cPanel VPS, WordPress VPS, Apps/OS</p>
        </div>
        <div>
            <strong style="color: #fff;">Features:</strong>
            <p style="margin: 4px 0 0 0;">All plans include daily backup, unlimited traffic, DDoS protection</p>
        </div>
        <div>
            <strong style="color: #fff;">Sold Out:</strong>
            <p style="margin: 4px 0 0 0;">Mark plans as sold out to show as unavailable (like Cloud does)</p>
        </div>
    </div>
</div>
@endsection
