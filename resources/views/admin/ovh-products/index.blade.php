@extends('layouts.admin')

@section('title', 'OVH Catalog Products')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">OVH Catalog Products</h1>
        <p class="page-subtitle">All OVH products synced from the cloud catalog</p>
    </div>
</div>

@if(session('success'))
<div style="margin-bottom: 20px; padding: 16px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); border-radius: 12px; color: #22c55e;">
    <i class="fas fa-check-circle" style="margin-right: 8px;"></i>{{ session('success') }}
</div>
@endif

<div style="margin-bottom: 24px; padding: 20px; background: rgba(0, 183, 255, 0.05); border: 1px solid rgba(0, 183, 255, 0.2); border-radius: 12px;">
    <form method="GET" action="{{ route('admin.ovh-products.index') }}" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end;">
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Category</label>
            <select name="category" style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; min-width: 180px;">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 0.8rem; color: #8b9bb4; margin-bottom: 6px;">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Plan code or name..."
                style="background: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 14px; color: #fff; min-width: 220px;">
        </div>
        <button type="submit" class="btn btn-primary" style="height: 42px;">
            <i class="fas fa-filter" style="margin-right: 6px;"></i>Filter
        </button>
        <a href="{{ route('admin.ovh-products.index') }}" class="btn btn-secondary" style="height: 42px;">
            <i class="fas fa-undo" style="margin-right: 6px;"></i>Reset
        </a>
    </form>
    <small style="display: block; margin-top: 12px; color: #6b7280;">
        <i class="fas fa-info-circle"></i> Sync products with <code>ovh:sync-products --type=all</code>
    </small>
</div>

<div class="card">
    <div class="table-header">
        <h3 class="table-title"><i class="fas fa-cloud" style="margin-right: 10px; color: #00b7ff;"></i>Products</h3>
        <span style="font-size: 0.85rem; color: #8b9bb4;">{{ $products->total() }} total</span>
    </div>

    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                <th style="padding: 16px; text-align: left; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Category</th>
                <th style="padding: 16px; text-align: left; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Plan Code</th>
                <th style="padding: 16px; text-align: left; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Name</th>
                <th style="padding: 16px; text-align: left; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Specs</th>
                <th style="padding: 16px; text-align: right; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Monthly</th>
                <th style="padding: 16px; text-align: center; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Status</th>
                <th style="padding: 16px; text-align: center; color: #8b9bb4; font-size: 0.8rem; text-transform: uppercase;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $product)
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.04);">
                <td style="padding: 16px; color: #fff;">{{ $product->category_label }}</td>
                <td style="padding: 16px; color: #8b9bb4; font-family: monospace; font-size: 0.85rem;">{{ $product->plan_code }}</td>
                <td style="padding: 16px; color: #fff;">{{ $product->display_name }}</td>
                <td style="padding: 16px; color: #8b9bb4; font-size: 0.85rem;">
                    @if($product->cpu_cores)
                        {{ $product->cpu_cores }} vCores<br>
                    @endif
                    @if($product->ram_gb)
                        {{ $product->ram_gb }} GB RAM<br>
                    @endif
                    @if($product->disk_gb)
                        {{ $product->disk_gb }} GB {{ $product->disk_type }}
                    @endif
                </td>
                <td style="padding: 16px; text-align: right; color: #00b7ff; font-weight: 600;">₹{{ number_format($product->price_monthly ?? 0, 0) }}</td>
                <td style="padding: 16px; text-align: center;">
                    <span style="padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; {{ $product->is_active ? 'background: rgba(34,197,94,0.2); color: #22c55e;' : 'background: rgba(239,68,68,0.2); color: #ef4444;' }}">
                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td style="padding: 16px; text-align: center;">
                    <a href="{{ route('admin.ovh-products.show', $product) }}" class="btn btn-sm btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">
                        <i class="fas fa-eye" style="margin-right: 4px;"></i>View
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 12px; display: block;"></i>
                    No OVH products found. Run <code>ovh:sync-products --type=all</code>.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="padding: 20px;">
        {{ $products->links() }}
    </div>
</div>
@endsection
