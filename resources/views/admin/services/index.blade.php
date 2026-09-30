@extends('layouts.admin')

@section('title', 'Services')

@section('content')
<div class="page-header">
    <h1 class="page-title">Services</h1>
    <p class="page-subtitle">Manage your service offerings</p>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="table-title">All Services</h3>
        <div class="table-actions">
            <a href="{{ route('admin.services.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Service
            </a>
        </div>
    </div>
    <div class="card-body"><div class="table-responsive">
    <table class="data-table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
            <tr>
                <td>
                    <div style="font-weight: 600;">{{ $service->title }}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">{{ $service->slug }}</div>
                </td>
                <td>{{ ucfirst($service->category ?? '—') }}</td>
                <td style="font-weight: 600; color: #d97706;">
                    {{ $service->price ? $currencySymbol . number_format($service->price, 2) : '—' }}
                </td>
                <td>
                    <span class="badge badge-{{ $service->is_active ? 'success' : 'danger' }}">
                        {{ $service->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <a href="{{ route('admin.services.edit', $service) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('admin.services.destroy', $service) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(239,68,68,0.1); color: #ef4444; border-color: rgba(239,68,68,0.3);" onclick="return confirm('Delete this service?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                    <i class="fas fa-cube" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.3;"></i>
                    <p>No services found</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div></div>

    @if($services->hasPages())
    <div style="padding: 20px 24px; border-top: 1px solid var(--border-color);">
        {{ $services->links() }}
    </div>
    @endif
</div>
@endsection
